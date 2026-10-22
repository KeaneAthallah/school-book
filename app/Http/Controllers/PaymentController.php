<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentMethod;
use App\Models\School;
use App\Models\Setting;
use App\Services\PaymentService;
use App\Support\Money;
use App\Support\NumberToWords;
use App\Support\QrCode;
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
            ->with(['school:id,name', 'paymentMethod:id,name', 'allocations' => fn ($q) => $q->select('id', 'payment_id', 'invoice_id', 'amount', 'receipt_number')->with('invoice:id,number')])
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
                'allocations' => $payment->allocations->map(fn (PaymentAllocation $allocation): array => [
                    'id' => $allocation->getKey(),
                    'invoice_id' => $allocation->invoice_id,
                    'invoice_number' => $allocation->invoice?->number,
                    'receipt_number' => $allocation->receipt_number,
                ])->all(),
                'invoice_id' => $payment->allocations->first()?->invoice_id,
                'invoice' => $payment->allocations->first()?->invoice?->number,
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

    /**
     * Cetak kwitansi untuk satu alokasi pembayaran pada sebuah invoice.
     *
     * Satu invoice dapat memiliki beberapa kwitansi bila sekolah melakukan
     * pembayaran secara bertahap (cicilan).
     */
    public function receipt(Request $request, Payment $payment, PaymentAllocation $allocation): View
    {
        abort_unless($this->userCan($request, 'payments.view'), 403);
        abort_unless($allocation->payment_id === $payment->getKey(), 404);

        $payment->load(['school', 'allocations']);
        $invoice = $allocation->invoice;
        abort_if($invoice === null, 404);

        $format = static fn (mixed $value): string => number_format((float) $value, 0, ',', '.');

        // Sisa hutang dihitung sampai dengan pembayaran ini, sehingga kwitansi
        // setiap cicilan tetap menampilkan sisa hutang pada saat kwitansi itu dibuat.
        $cumulative = $this->cumulativePaid($invoice, $allocation);
        $remaining = Money::sub($invoice->total_amount, $cumulative);

        $school = $payment->school ?? $invoice->school;

        $receiptNumber = $allocation->receipt_number ?? $payment->number;

        return view('print.kwitansi', [
            'payment' => $payment,
            'allocation' => $allocation,
            'invoice' => $invoice,
            'school' => $school,
            'remaining' => $remaining,
            'format' => $format,
            'amountInWords' => NumberToWords::rupiah($allocation->amount),
            'payerName' => $this->payerName($school),
            'purpose' => $this->purpose($invoice),
            'place' => (string) Setting::value('print.receipt_city', ''),
            'financeName' => (string) Setting::value('print.receipt_finance_name', ''),
            'financeNip' => (string) Setting::value('print.receipt_finance_nip', ''),
            'representativeName' => (string) Setting::value('print.receipt_representative_name', ''),
            'representativeNik' => (string) Setting::value('print.receipt_representative_nik', ''),
            'qrCode' => QrCode::svg(url('/kwitansi/'.$receiptNumber), 4, 1),
        ]);
    }

    /**
     * Total pembayaran terverifikasi pada sebuah invoice sampai dengan alokasi
     * tertentu. Alokasi setelah ini tidak ikut dihitung, sehingga kwitansi
     * setiap cicilan menampilkan sisa hutang pada saat kwitansi itu dibuat.
     */
    private function cumulativePaid(Invoice $invoice, PaymentAllocation $allocation): string
    {
        $allocations = PaymentAllocation::query()
            ->where('invoice_id', $invoice->getKey())
            ->whereHas('payment', fn (Builder $query): Builder => $query->where('status', PaymentStatus::Verified->value))
            ->orderBy('payment_id')
            ->orderBy('id')
            ->get(['id', 'amount', 'payment_id']);

        $paid = '0';

        foreach ($allocations as $item) {
            $paid = Money::add($paid, $item->amount);

            if ($item->getKey() === $allocation->getKey()) {
                break;
            }
        }

        return $paid;
    }

    /**
     * Nama penanda tangan penerimaan, mengikuti kepala sekolah.
     */
    private function payerName(?School $school): string
    {
        if ($school === null) {
            return '-';
        }

        $principal = trim((string) $school->principal_name);

        if ($principal !== '') {
            return strtoupper($principal);
        }

        return strtoupper('Kepala Sekolah '.$school->name);
    }

    /**
     * Keterangan "Untuk Pembayaran" pada kwitansi.
     */
    private function purpose(Invoice $invoice): string
    {
        $items = $invoice->items()->pluck('description')->filter()->unique()->take(3)->all();

        if ($items === []) {
            return 'Pembayaran Invoice '.$invoice->number;
        }

        $description = implode(', ', $items);

        if ($invoice->items()->count() > count($items)) {
            $description .= ', dan lainnya';
        }

        return strtoupper($description);
    }
}
