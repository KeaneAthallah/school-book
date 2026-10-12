<?php

namespace App\Http\Controllers;

use App\Enums\InvoicePaymentStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Shipment;
use App\Services\InvoiceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'invoices.view'), 403);

        $search = $request->string('search')->toString();
        $paymentStatus = $request->string('payment_status')->toString();

        $invoices = Invoice::query()
            ->with(['school:id,name'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $builder) use ($search): void {
                $builder->where('number', 'like', '%'.$search.'%')
                    ->orWhereHas('school', fn (Builder $school) => $school->where('name', 'like', '%'.$search.'%'));
            }))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Invoice $invoice): array => [
                'id' => $invoice->getKey(),
                'number' => $invoice->number,
                'invoice_date' => $invoice->invoice_date->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'school' => $invoice->school?->name,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'outstanding_amount' => $invoice->outstandingAmount(),
                'payment_status' => $invoice->paymentStatus()->value,
                'payment_status_label' => $invoice->paymentStatus()->label(),
            ]);

        if ($paymentStatus !== '') {
            $invoices->setCollection(
                $invoices->getCollection()->filter(fn (array $row): bool => $row['payment_status'] === $paymentStatus)->values(),
            );
        }

        return view('finance.invoices.index', [
            'invoices' => $invoices,
            'filters' => ['search' => $search, 'payment_status' => $paymentStatus],
            'paymentStatuses' => collect(InvoicePaymentStatus::cases())
                ->map(fn ($case): array => ['value' => $case->value, 'label' => $case->label()])->all(),
        ]);
    }

    public function show(Request $request, Invoice $invoice): View
    {
        abort_unless($this->userCan($request, 'invoices.view'), 403);

        $invoice->load(['school:id,name,npsn,address', 'order:id,number', 'shipment:id,number', 'items.product:id,code,title', 'allocations.payment']);

        return view('finance.invoices.show', [
            'invoice' => [
                'id' => $invoice->getKey(),
                'number' => $invoice->number,
                'invoice_date' => $invoice->invoice_date->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'school' => $invoice->school?->name,
                'npsn' => $invoice->school?->npsn,
                'address' => $invoice->school?->address,
                'order' => $invoice->order?->number,
                'shipment' => $invoice->shipment?->number,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'subtotal' => $invoice->subtotal,
                'discount_amount' => $invoice->discount_amount,
                'tax_amount' => $invoice->tax_amount,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'outstanding_amount' => $invoice->outstandingAmount(),
                'payment_status' => $invoice->paymentStatus()->value,
                'payment_status_label' => $invoice->paymentStatus()->label(),
                'items' => $invoice->items->map(fn ($item): array => [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ])->all(),
                'payments' => $invoice->allocations->map(fn ($allocation): array => [
                    'number' => $allocation->payment?->number,
                    'date' => $allocation->payment?->payment_date?->toDateString(),
                    'amount' => $allocation->amount,
                    'status' => $allocation->payment?->status->value,
                    'status_label' => $allocation->payment?->status->label(),
                ])->all(),
                'attachments' => $invoice->attachments()->orderByDesc('id')->get()->map(fn ($attachment): array => [
                    'id' => $attachment->getKey(),
                    'name' => $attachment->original_name,
                    'url' => Storage::url($attachment->path),
                    'time' => $attachment->created_at?->toDateTimeString(),
                ])->all(),
            ],
            'can' => [
                'cancel' => ($this->userCan($request, 'invoices.manage')) && $invoice->paid_amount === '0.00',
                'upload_proof' => $this->userCan($request, 'invoices.manage'),
                'manage_attachments' => $this->userCan($request, 'invoices.manage'),
            ],
        ]);
    }

    public function store(Request $request, InvoiceService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'invoices.issue'), 403);

        $validated = $request->validate([
            'shipment_id' => ['nullable', 'integer', 'exists:shipments,id', 'required_without:order_id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id', 'required_without:shipment_id'],
        ]);

        if ($request->filled('shipment_id')) {
            $shipment = Shipment::query()->findOrFail((int) $validated['shipment_id']);
            $invoice = $service->generateForShipment($shipment, $request->user());
        } else {
            $order = Order::query()->findOrFail((int) $validated['order_id']);
            $invoice = $service->generateForOrder($order, $request->user());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice '.$invoice->number.' diterbitkan.');
    }

    public function cancel(Request $request, Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'invoices.manage'), 403);

        $service->cancel($invoice, $request->user());

        return back()->with('success', 'Invoice '.$invoice->number.' dibatalkan.');
    }

    public function storePaymentProof(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($this->userCan($request, 'invoices.manage'), 403);

        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'photo.required' => 'Bukti foto pembayaran wajib dilampirkan.',
            'photo.image' => 'Bukti pembayaran harus berupa gambar (JPEG, PNG, atau WebP).',
            'photo.mimes' => 'Bukti pembayaran harus berupa gambar (JPEG, PNG, atau WebP).',
            'photo.max' => 'Bukti pembayaran maksimal 5 MB.',
        ]);

        $photo = $request->file('photo');
        $path = $photo->store('invoices/'.$invoice->id, 'public');

        $invoice->attachments()->create([
            'original_name' => $photo->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $photo->getMimeType(),
            'size' => $photo->getSize(),
            'uploaded_by' => $request->user()?->getKey(),
        ]);

        return back()->with('success', 'Bukti foto pembayaran ditambahkan.');
    }

    public function print(Request $request, Invoice $invoice): View
    {
        abort_unless($this->userCan($request, 'invoices.view'), 403);

        $invoice->load(['school', 'items.product', 'order']);

        return view('print.invoice', [
            'invoice' => $invoice,
            'company' => [
                'name' => Setting::value('company.name', config('app.name')),
                'address' => Setting::value('company.address', ''),
                'phone' => Setting::value('company.phone', ''),
                'email' => Setting::value('company.email', ''),
            ],
        ]);
    }
}
