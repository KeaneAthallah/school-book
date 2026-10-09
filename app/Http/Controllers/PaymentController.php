<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\School;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'payments.view'), 403);

        $search = $request->string('search')->toString();

        $payments = Payment::query()
            ->with(['school:id,name', 'paymentMethod:id,name'])
            ->withSum('allocations', 'amount')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $builder) use ($search): void {
                $builder->where('number', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('school', fn (Builder $school) => $school->where('name', 'like', '%'.$search.'%'));
            }))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Payment $payment): array => [
                'id' => $payment->getKey(),
                'number' => $payment->number,
                'payment_date' => $payment->payment_date->toDateString(),
                'school' => $payment->school->name,
                'method' => $payment->paymentMethod?->name,
                'reference' => $payment->reference,
                'amount' => $payment->amount,
                'allocated_amount' => (string) ($payment->getAttribute('allocations_sum_amount') ?? '0'),
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
            ]);

        return view('finance.payments.index', [
            'payments' => $payments,
            'filters' => ['search' => $search],
            'schools' => School::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (School $school): array => ['value' => $school->getKey(), 'label' => $school->name])->all(),
            'methods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (PaymentMethod $method): array => ['value' => $method->getKey(), 'label' => $method->name])->all(),
            'openInvoices' => Invoice::query()
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->whereColumn('paid_amount', '<', 'total_amount')
                ->with('school:id,name')
                ->orderBy('due_date')
                ->limit(200)
                ->get()
                ->map(fn (Invoice $invoice): array => [
                    'value' => $invoice->getKey(),
                    'label' => $invoice->number.' - '.$invoice->school->name,
                    'school_id' => $invoice->school_id,
                    'outstanding' => $invoice->outstandingAmount(),
                ])->all(),
            'can' => [
                'manage' => $this->userCan($request, 'payments.manage'),
                'verify' => $this->userCan($request, 'payments.verify'),
            ],
        ]);
    }

    public function store(Request $request, PaymentService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'payments.manage'), 403);

        $validated = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required_with:allocations', 'integer', 'exists:invoices,id'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01'],
        ]);

        $payment = $service->record($validated, $request->user());

        return back()->with('success', 'Pembayaran '.$payment->number.' dicatat.');
    }

    public function verify(Request $request, Payment $payment, PaymentService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'payments.verify'), 403);

        $service->verify($payment, $request->user());

        return back()->with('success', 'Pembayaran '.$payment->number.' diverifikasi.');
    }

    public function reverse(Request $request, Payment $payment, PaymentService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'payments.verify'), 403);

        $service->reverse($payment, $request->user());

        return back()->with('success', 'Pembayaran '.$payment->number.' dibalik.');
    }
}
