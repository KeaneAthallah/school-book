<?php

namespace App\Services;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly InventoryService $inventory,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): GoodsReceipt
    {
        return DB::transaction(function () use ($data, $user): GoodsReceipt {
            $receipt = GoodsReceipt::query()->create([
                'number' => $this->numbers->generate('GR', 'goods_receipts', $data['receipt_date'] ?? null),
                'publisher_id' => (int) $data['publisher_id'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'receipt_date' => $data['receipt_date'],
                'status' => GoodsReceiptStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->getKey(),
            ]);

            $this->syncItems($receipt, $data['items']);

            $this->audit->log('goods_receipt.created', $receipt, 'Pembuatan penerimaan '.$receipt->number, user: $user);

            return $receipt->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GoodsReceipt $receipt, array $data, User $user): GoodsReceipt
    {
        if (! $receipt->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Penerimaan yang sudah dikonfirmasi tidak dapat diubah.',
            ]);
        }

        return DB::transaction(function () use ($receipt, $data, $user): GoodsReceipt {
            $receipt->fill([
                'publisher_id' => (int) $data['publisher_id'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'receipt_date' => $data['receipt_date'],
                'notes' => $data['notes'] ?? null,
            ])->save();

            $this->syncItems($receipt, $data['items']);

            $this->audit->log('goods_receipt.updated', $receipt, 'Perubahan penerimaan '.$receipt->number, user: $user);

            return $receipt->refresh();
        });
    }

    public function confirm(GoodsReceipt $receipt, User $user): GoodsReceipt
    {
        return $this->inventory->receive($receipt, $user);
    }

    public function delete(GoodsReceipt $receipt, User $user): void
    {
        if (! $receipt->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Penerimaan yang sudah dikonfirmasi tidak dapat dihapus.',
            ]);
        }

        $this->audit->log('goods_receipt.deleted', $receipt, 'Penghapusan penerimaan '.$receipt->number, user: $user);

        $receipt->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(GoodsReceipt $receipt, array $items): void
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Penerimaan harus memiliki minimal satu item.',
            ]);
        }

        $receipt->items()->delete();

        foreach ($items as $line) {
            $receipt->items()->create([
                'book_id' => (int) $line['book_id'],
                'quantity' => (int) $line['quantity'],
                'unit_cost' => (string) ($line['unit_cost'] ?? '0'),
            ]);
        }
    }
}
