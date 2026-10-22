<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\School;
use App\Services\PaymentService;
use App\Support\Money;
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
        $invoiceId = $request->integer('invoice_id');
        $selectedInvoice = $invoiceId > 0
            ? Invoice::query()->with('school:id,name')->find($invoiceId)
            : null;

        $payments = Payment::query()
            ->with(['school:id,name', 'paymentMethod:id,name'])
            ->withSum(
                ['allocations as allocations_sum_amount' => fn (Builder $query): Builder => $query->whereHas(
                    'payment',
                    fn (Builder $payment): Builder => $payment->where('status', '!=', PaymentStatus::Reversed->value),
                )],
                'amount',
            )
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
                'remaining_amount' => (string) (Money::sub((string) $payment->amount, (string) ($payment->getAttribute('allocations_sum_amount') ?? '0'))),
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
            ]);

        return view('finance.payments.index', [
            'payments' => $payments,
            'filters' => ['search' => $search, 'invoice_id' => $invoiceId],
            'schools' => School::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (School $school): array => ['value' => $school->getKey(), 'label' => $school->name])->all(),
            'methods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (PaymentMethod $method): array => ['value' => $method->getKey(), 'label' => $method->name])->all(),
            'openInvoices' => $this->openInvoices($selectedInvoice),
            'selectedInvoiceId' => $selectedInvoice instanceof Invoice ? (int) $selectedInvoice->getKey() : 0,
            'selectedSchoolId' => $selectedInvoice instanceof Invoice ? (int) $selectedInvoice->school_id : 0,
            'selectedOutstanding' => $selectedInvoice instanceof Invoice ? $selectedInvoice->outstandingAmount() : null,
            'can' => [
                'manage' => $this->userCan($request, 'payments.manage'),
                'verify' => $this->userCan($request, 'payments.verify'),
            ],
        ]);
    }

    /**
     * Invoice yang masih outstanding untuk dipilih pada form pembayaran.
     * Invoice yang dipilih lewat parameter invoice_id selalu disertakan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function openInvoices(?Invoice $selectedInvoice): array
    {
        $invoices = Invoice::query()
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->with('school:id,name')
            ->orderBy('due_date')
            ->limit(200)
            ->get();

        if ($selectedInvoice !== null && ! $invoices->contains('id', $selectedInvoice->getKey())) {
            $invoices->prepend($selectedInvoice);
        }

        return $invoices->map(fn (Invoice $invoice): array => [
            'value' => $invoice->getKey(),
            'label' => $invoice->number.' - '.$invoice->school->name,
            'school_id' => $invoice->school_id,
            'outstanding' => $invoice->outstandingAmount(),
        ])->all();
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
        ]);

        // Jumlah alokasi default = jumlah pembayaran bila tidak diisi.
        if (! empty($validated['allocations'])) {
            foreach ($validated['allocations'] as $i => $entry) {
                if (empty($entry['amount']) && ! empty($entry['invoice_id'])) {
                    $validated['allocations'][$i]['amount'] = $validated['amount'];
                }
            }
        }

        $payment = $service->record($validated, $request->user());

        return back()->with('success', 'Pembayaran '.$payment->number.' dicatat.');
    }

    public function edit(Request $request, Payment $payment): View
    {
        abort_unless($this->userCan($request, 'payments.manage'), 403);
        abort_unless($payment->status === PaymentStatus::Draft, 403);

        $payment->load('allocations:id,invoice_id,amount');

        $allocatedInvoiceId = (int) ($payment->allocations->first()->invoice_id ?? 0);
        $allocatedInvoice = $allocatedInvoiceId > 0
            ? Invoice::query()->with('school:id,name')->find($allocatedInvoiceId)
            : null;

        return view('finance.payments.form', [
            'payment' => [
                'id' => $payment->getKey(),
                'number' => $payment->number,
                'school_id' => $payment->school_id,
                'payment_date' => $payment->payment_date->toDateString(),
                'amount' => $payment->amount,
                'payment_method_id' => $payment->payment_method_id,
                'reference' => $payment->reference,
                'notes' => $payment->notes,
                'invoice_id' => $allocatedInvoiceId,
            ],
            'schools' => School::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (School $school): array => ['value' => $school->getKey(), 'label' => $school->name])->all(),
            'methods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (PaymentMethod $method): array => ['value' => $method->getKey(), 'label' => $method->name])->all(),
            'openInvoices' => $this->openInvoices($allocatedInvoice),
            'selectedOutstanding' => $allocatedInvoice?->outstandingAmount(),
        ]);
    }

    public function update(Request $request, Payment $payment, PaymentService $service): RedirectResponse
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
        ]);

        if (! empty($validated['allocations'])) {
            foreach ($validated['allocations'] as $i => $entry) {
                if (empty($entry['amount']) && ! empty($entry['invoice_id'])) {
                    $validated['allocations'][$i]['amount'] = $validated['amount'];
                }
            }
        }

        $service->update($payment, $validated, $request->user());

        return redirect()->route('payments.index')->with('success', 'Pembayaran '.$payment->number.' diperbarui.');
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
