<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\ShipmentStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Generate an invoice for the not-yet-invoiced quantities of a shipment.
     */
    public function generateForShipment(Shipment $shipment, User $user): Invoice
    {
        return DB::transaction(function () use ($shipment, $user): Invoice {
            $locked = Shipment::query()->lockForUpdate()->findOrFail((int) $shipment->getKey());

            if ($locked->status === ShipmentStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Pengiriman belum dikonfirmasi sehingga belum dapat diinvoice.',
                ]);
            }

            $locked->load('items');

            return $this->build(Order::query()->find((int) $locked->order_id), $locked, $user);
        });
    }

    /**
     * Consolidated invoicing: invoice every confirmed shipment of an order
     * that still has unbilled quantities.
     */
    public function generateForOrder(Order $order, User $user): Invoice
    {
        return DB::transaction(function () use ($order, $user): Invoice {
            $locked = Order::query()->lockForUpdate()->findOrFail((int) $order->getKey());

            $shipments = $locked->shipments()
                ->whereIn('status', [ShipmentStatus::Shipped->value, ShipmentStatus::Delivered->value])
                ->with('items')
                ->orderBy('id')
                ->get();

            if ($shipments->isEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'Belum ada pengiriman terkonfirmasi untuk diinvoice.',
                ]);
            }

            return $this->build($locked, null, $user, $shipments->all());
        });
    }

    public function cancel(Invoice $invoice, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice, $user): Invoice {
            $locked = Invoice::query()->lockForUpdate()->findOrFail((int) $invoice->getKey());

            if (Money::compare($locked->paid_amount, '0') > 0) {
                throw ValidationException::withMessages([
                    'status' => 'Invoice yang sudah memiliki pembayaran tidak dapat dibatalkan.',
                ]);
            }

            $locked->forceFill(['status' => InvoiceStatus::Cancelled])->save();

            $this->audit->log('invoice.cancelled', $locked, 'Pembatalan invoice '.$locked->number, user: $user);

            return $locked->refresh();
        });
    }

    /**
     * @param  array<int, Shipment>|null  $shipments
     */
    private function build(?Order $order, ?Shipment $shipment, User $user, ?array $shipments = null): Invoice
    {
        $shipments ??= $shipment === null ? [] : [$shipment];

        $lines = $this->resolveLines($shipments);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'items' => 'Tidak ada kuantitas yang dapat diinvoice (semua sudah pernah diinvoice).',
            ]);
        }

        $schoolId = $order !== null ? $order->school_id : $shipment?->order->school_id;

        if ($schoolId === null) {
            throw ValidationException::withMessages([
                'school_id' => 'Invoice membutuhkan data sekolah.',
            ]);
        }

        $subtotal = '0.00';

        foreach ($lines as $line) {
            $subtotal = Money::add($subtotal, $line['subtotal']);
        }

        $taxRate = (string) Setting::value('finance.tax_rate', '0');
        $tax = Money::percentage($subtotal, $taxRate);
        $dueDays = (int) Setting::value('finance.invoice_due_days', '30');
        $invoiceDate = now()->toDateString();

        $invoice = Invoice::query()->create([
            'number' => $this->numbers->generate('INV', 'invoices'),
            'order_id' => $order?->getKey() ?? $shipment?->order_id,
            'shipment_id' => $shipment?->getKey(),
            'school_id' => (int) $schoolId,
            'invoice_date' => $invoiceDate,
            'due_date' => now()->addDays($dueDays)->toDateString(),
            'status' => InvoiceStatus::Issued,
            'subtotal' => $subtotal,
            'discount_amount' => '0.00',
            'tax_amount' => $tax,
            'total_amount' => Money::add($subtotal, $tax),
            'paid_amount' => '0.00',
            'created_by' => $user->getKey(),
        ]);

        foreach ($lines as $line) {
            $invoice->items()->create($line);
        }

        $this->audit->log('invoice.issued', $invoice, 'Penerbitan invoice '.$invoice->number, newValues: [
            'total_amount' => $invoice->total_amount,
        ], user: $user);

        return $invoice->refresh();
    }

    /**
     * @param  array<int, Shipment>  $shipments
     * @return array<int, array<string, mixed>>
     */
    private function resolveLines(array $shipments): array
    {
        $lines = [];

        foreach ($shipments as $shipment) {
            foreach ($shipment->items as $shipmentItem) {
                $billable = $this->billableQuantity($shipmentItem);

                if ($billable <= 0) {
                    continue;
                }

                $orderItem = $shipmentItem->orderItem;
                $unitPrice = (string) $orderItem->unit_price;
                $subtotal = Money::mul($unitPrice, (string) $billable);

                $lines[] = [
                    'order_item_id' => $shipmentItem->order_item_id,
                    'shipment_item_id' => $shipmentItem->getKey(),
                    'product_id' => $shipmentItem->product_id,
                    'description' => $orderItem->product->title,
                    'quantity' => $billable,
                    'unit_price' => $unitPrice,
                    'discount_amount' => '0.00',
                    'subtotal' => $subtotal,
                ];
            }
        }

        return $lines;
    }

    private function billableQuantity(ShipmentItem $shipmentItem): int
    {
        $invoiced = (int) InvoiceItem::query()
            ->where('shipment_item_id', $shipmentItem->getKey())
            ->whereHas('invoice', fn ($query) => $query->whereNotIn('status', [InvoiceStatus::Cancelled->value]))
            ->sum('quantity');

        return max(0, $shipmentItem->quantity - $invoiced);
    }
}
