<?php

namespace App\Http\Controllers;

use App\Enums\GoodsReceiptStatus;
use App\Enums\ShipmentStatus;
use App\Support\Money;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Rangkuman stok gudang per barang.
 *
 * Barang masuk berasal dari penerimaan yang sudah dikonfirmasi, barang keluar
 * dari pengiriman yang sudah dikirim/diterima. Selisih keduanya adalah sisa
 * stok yang tersedia di gudang, dikurangi mutasi (hilang, rusak, dipinjam).
 */
class StockSummaryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'procurement.view'), 403);

        $search = $request->string('search')->toString();
        $filter = $request->string('filter')->toString();

        $rows = $this->baseQuery($search, $filter)
            ->select([
                'products.id',
                'products.code',
                'products.title',
                'products.type',
                'products.cost_price',
            ])
            ->selectRaw('COALESCE(inbound.qty, 0) as masuk')
            ->selectRaw('COALESCE(outbound.qty, 0) as keluar')
            ->selectRaw('COALESCE(mutation.qty, 0) as mutasi')
            ->orderBy('products.title')
            ->orderBy('products.code')
            ->paginate(25)
            ->withQueryString();

        $rows->setCollection(
            $rows->getCollection()->map(function (object $row): array {
                $masuk = (int) data_get($row, 'masuk');
                $keluar = (int) data_get($row, 'keluar');
                $mutasi = (int) data_get($row, 'mutasi');
                $sisa = $masuk - $keluar - $mutasi;

                return [
                    'id' => (int) data_get($row, 'id'),
                    'code' => (string) data_get($row, 'code'),
                    'title' => (string) data_get($row, 'title'),
                    'type' => (string) data_get($row, 'type'),
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'mutasi' => $mutasi,
                    'sisa' => $sisa,
                    'nilai_sisa' => Money::mul((string) $sisa, (string) data_get($row, 'cost_price')),
                ];
            })->values()
        );

        $totals = $this->baseQuery($search, $filter)
            ->selectRaw('COALESCE(SUM(inbound.qty), 0) as masuk')
            ->selectRaw('COALESCE(SUM(outbound.qty), 0) as keluar')
            ->selectRaw('COALESCE(SUM(mutation.qty), 0) as mutasi')
            ->selectRaw('COUNT(*) as item_count')
            ->selectRaw(
                'COALESCE(SUM((COALESCE(inbound.qty, 0) - COALESCE(outbound.qty, 0) - COALESCE(mutation.qty, 0)) * products.cost_price), 0) as nilai_sisa'
            )
            ->first();

        $sisa = (int) data_get($totals, 'masuk') - (int) data_get($totals, 'keluar') - (int) data_get($totals, 'mutasi');

        return view('inventory.stock-summary.index', [
            'rows' => $rows,
            'filters' => ['search' => $search, 'filter' => $filter],
            'summary' => [
                'item_count' => (int) data_get($totals, 'item_count'),
                'masuk' => (int) data_get($totals, 'masuk'),
                'keluar' => (int) data_get($totals, 'keluar'),
                'mutasi' => (int) data_get($totals, 'mutasi'),
                'sisa' => $sisa,
            ],
            'nilai_sisa' => data_get($totals, 'nilai_sisa'),
        ]);
    }

    private function baseQuery(string $search, string $filter): Builder
    {
        $inbound = DB::table('goods_receipt_items')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
            ->where('goods_receipts.status', GoodsReceiptStatus::Confirmed->value)
            ->groupBy('goods_receipt_items.product_id')
            ->selectRaw('goods_receipt_items.product_id as product_id, SUM(goods_receipt_items.quantity) as qty');

        $outbound = DB::table('shipment_items')
            ->join('shipments', 'shipments.id', '=', 'shipment_items.shipment_id')
            ->whereIn('shipments.status', [ShipmentStatus::Shipped->value, ShipmentStatus::Delivered->value])
            ->groupBy('shipment_items.product_id')
            ->selectRaw('shipment_items.product_id as product_id, SUM(shipment_items.quantity) as qty');

        $mutation = DB::table('stock_mutations')
            ->groupBy('product_id')
            ->selectRaw('product_id as product_id, SUM(quantity) as qty');

        $query = DB::table('products')
            ->leftJoinSub($inbound, 'inbound', 'inbound.product_id', '=', 'products.id')
            ->leftJoinSub($outbound, 'outbound', 'outbound.product_id', '=', 'products.id')
            ->leftJoinSub($mutation, 'mutation', 'mutation.product_id', '=', 'products.id')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereRaw('COALESCE(inbound.qty, 0) > 0')
                    ->orWhereRaw('COALESCE(outbound.qty, 0) > 0')
                    ->orWhereRaw('COALESCE(mutation.qty, 0) > 0');
            });

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('products.code', 'like', '%'.$search.'%')
                    ->orWhere('products.title', 'like', '%'.$search.'%');
            });
        }

        match ($filter) {
            'tersedia' => $query->whereRaw(
                '(COALESCE(inbound.qty, 0) - COALESCE(outbound.qty, 0) - COALESCE(mutation.qty, 0)) > 0'
            ),
            'habis' => $query->whereRaw(
                '(COALESCE(inbound.qty, 0) - COALESCE(outbound.qty, 0) - COALESCE(mutation.qty, 0)) <= 0'
            ),
            default => null,
        };

        return $query;
    }
}
