<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly InventoryService $inventory,
        private readonly OrderStatusResolver $statusResolver,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Order $order, array $data, User $user): Shipment
    {
        return DB::transaction(function () use ($order, $data, $user): Shipment {
            $locked = Order::query()->lockForUpdate()->findOrFail((int) $order->getKey());

            if ($locked->status === OrderStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan belum dikonfirmasi sehingga belum dapat dikirim.',
                ]);
            }

            $locked->load('items');

            $lines = $this->resolveLines($locked, $data['items'] ?? []);

            if ($lines === []) {
                throw ValidationException::withMessages([
                    'items' => 'Tidak ada kuantitas yang siap dikirim.',
                ]);
            }

            $firstItem = $locked->items->firstWhere('id', $lines[0]['order_item_id']);
            $warehouseId = $firstItem instanceof OrderItem
                ? (int) $firstItem->warehouse_id
                : (int) Warehouse::default()->getKey();

            $shipment = Shipment::query()->create([
                'number' => $this->numbers->generate('SHP', 'shipments'),
                'delivery_note_number' => $this->numbers->generate('SJ', 'shipments', column: 'delivery_note_number'),
                'order_id' => $locked->getKey(),
                'warehouse_id' => $warehouseId,
                'courier_id' => $data['courier_id'] ?? null,
                'shipping_method_id' => $data['shipping_method_id'] ?? null,
                'ship_date' => $data['ship_date'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => ShipmentStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->getKey(),
            ]);

            foreach ($lines as $line) {
                $shipment->items()->create($line);
            }

            $this->audit->log('shipment.created', $shipment, 'Pembuatan pengiriman '.$shipment->number, user: $user);

            return $shipment->refresh();
        });
    }

    /**
     * Confirm a shipment: deduct stock, advance fulfilment quantities,
     * recompute the parent order status and persist the proof photo.
     */
    public function confirm(Shipment $shipment, User $user, ?UploadedFile $proofPhoto = null): Shipment
    {
        return DB::transaction(function () use ($shipment, $user, $proofPhoto): Shipment {
            $locked = Shipment::query()->lockForUpdate()->findOrFail((int) $shipment->getKey());

            if ($locked->isConfirmed()) {
                throw ValidationException::withMessages([
                    'status' => 'Pengiriman ini sudah dikonfirmasi.',
                ]);
            }

            $locked->load('items');

            if ($locked->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Pengiriman tidak memiliki item.',
                ]);
            }

            foreach ($locked->items as $shipmentItem) {
                $item = OrderItem::query()->lockForUpdate()->findOrFail((int) $shipmentItem->order_item_id);

                if ($shipmentItem->quantity > $item->reserved_quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Jumlah kirim melebihi stok yang direservasi untuk barang #{$item->product_id}.",
                    ]);
                }

                $this->inventory->ship($item, $shipmentItem->quantity, $user, 'Pengiriman '.$locked->number);

                $item->reserved_quantity -= $shipmentItem->quantity;
                $item->shipped_quantity += $shipmentItem->quantity;
                $item->status = $this->statusResolver->resolveItem(
                    $item->quantity,
                    $item->reserved_quantity,
                    $item->shipped_quantity,
                );
                $item->save();
            }

            $locked->forceFill([
                'status' => ShipmentStatus::Shipped,
                'ship_date' => $locked->ship_date ?? now()->toDateString(),
                'confirmed_at' => now(),
            ])->save();

            if ($proofPhoto !== null) {
                $path = $proofPhoto->store('shipments/'.((int) $locked->getKey()), 'public');

                $locked->attachments()->create([
                    'original_name' => $proofPhoto->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $proofPhoto->getMimeType(),
                    'size' => $proofPhoto->getSize(),
                    'uploaded_by' => $user->getKey(),
                ]);
            }

            $order = Order::query()->findOrFail((int) $locked->order_id);
            $this->statusResolver->apply($order);

            $this->audit->log('shipment.confirmed', $locked, 'Konfirmasi pengiriman '.$locked->number, user: $user);

            return $locked->refresh();
        });
    }

    public function markDelivered(Shipment $shipment, User $user): Shipment
    {
        if ($shipment->status !== ShipmentStatus::Shipped) {
            throw ValidationException::withMessages([
                'status' => 'Hanya pengiriman yang sudah dikirim yang dapat ditandai diterima.',
            ]);
        }

        $shipment->forceFill(['status' => ShipmentStatus::Delivered])->save();

        $this->audit->log('shipment.delivered', $shipment, 'Pengiriman diterima '.$shipment->number, user: $user);

        return $shipment;
    }

    /**
     * Quantity of an order line that can still be added to a draft shipment.
     */
    public function shippableQuantity(OrderItem $item): int
    {
        $committed = (int) ShipmentItem::query()
            ->where('order_item_id', $item->getKey())
            ->whereHas('shipment', fn ($query) => $query->whereNotIn('status', [
                ShipmentStatus::Shipped->value,
                ShipmentStatus::Delivered->value,
            ]))
            ->sum('quantity');

        return max(0, $item->reserved_quantity - $committed);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, int>>
     */
    private function resolveLines(Order $order, array $items): array
    {
        $lines = [];

        foreach ($items as $entry) {
            $quantity = (int) ($entry['quantity'] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            $orderItem = $order->items->firstWhere('id', (int) ($entry['order_item_id'] ?? 0));

            if ($orderItem === null) {
                throw ValidationException::withMessages([
                    'items' => 'Item pesanan tidak ditemukan pada pesanan ini.',
                ]);
            }

            $available = $this->shippableQuantity($orderItem);

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'items' => "Jumlah kirim untuk barang #{$orderItem->product_id} melebihi sisa yang siap dikirim ({$available}).",
                ]);
            }

            $lines[] = [
                'order_item_id' => (int) $orderItem->getKey(),
                'product_id' => (int) $orderItem->product_id,
                'quantity' => $quantity,
            ];
        }

        return $lines;
    }
}
