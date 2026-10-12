<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'stock.view'), 403);

        $search = $request->string('search')->toString();

        $stocks = Stock::query()
            ->with(['product:id,code,title,min_stock', 'warehouse:id,name'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas('product', function (Builder $product) use ($search): void {
                $product->where('title', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
            }))
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->orderBy('products.title')
            ->select('stocks.*')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Stock $stock): array => [
                'id' => $stock->getKey(),
                'product' => $stock->product->title,
                'code' => $stock->product->code,
                'warehouse' => $stock->warehouse->name,
                'quantity' => $stock->quantity,
                'reserved_quantity' => $stock->reserved_quantity,
                'available_quantity' => $stock->availableQuantity(),
                'min_stock' => $stock->product->min_stock,
                'low' => $stock->product !== null && $stock->getAttribute('quantity') <= $stock->product->min_stock && $stock->product->min_stock > 0,
            ]);

        $movements = StockMovement::query()
            ->with(['product:id,code,title', 'warehouse:id,name', 'user:id,name'])
            ->orderByDesc('id')
            ->limit(25)
            ->get()
            ->map(fn (StockMovement $movement): array => [
                'id' => $movement->getKey(),
                'date' => $movement->created_at?->toDateTimeString(),
                'product' => $movement->product?->title,
                'warehouse' => $movement->warehouse?->name,
                'type' => $movement->type->value,
                'type_label' => $movement->type->label(),
                'quantity' => $movement->quantity,
                'balance_after' => $movement->balance_after,
                'note' => $movement->note,
                'user' => $movement->user?->name,
            ])->all();

        return view('inventory.stock.index', [
            'stocks' => $stocks,
            'movements' => $movements,
            'filters' => ['search' => $search],
            'products' => Product::query()->active()->orderBy('title')->get(['id', 'code', 'title'])
                ->map(fn (Product $product): array => ['value' => $product->getKey(), 'label' => $product->code.' - '.$product->title])->all(),
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Warehouse $warehouse): array => ['value' => $warehouse->getKey(), 'label' => $warehouse->name])->all(),
            'can' => ['adjust' => $this->userCan($request, 'stock.adjust')],
        ]);
    }

    public function adjust(Request $request, InventoryService $inventory): RedirectResponse
    {
        abort_unless($this->userCan($request, 'stock.adjust'), 403);

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $inventory->adjust(
            (int) $validated['product_id'],
            (int) $validated['warehouse_id'],
            (int) $validated['quantity'],
            (string) $validated['reason'],
            $request->user(),
        );

        return back()->with('success', 'Stok berhasil disesuaikan.');
    }

    public function movements(Request $request): View
    {
        abort_unless($this->userCan($request, 'stock.view'), 403);

        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();
        $from = $request->string('date_from')->toString();
        $to = $request->string('date_to')->toString();

        $movements = StockMovement::query()
            ->with(['product:id,code,title', 'warehouse:id,name', 'user:id,name'])
            ->when($type !== '', fn (Builder $query) => $query->where('type', $type))
            ->when($search !== '', fn (Builder $query) => $query->whereHas('product', function (Builder $product) use ($search): void {
                $product->where('title', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
            }))
            ->when($from !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (StockMovement $movement): array => [
                'id' => $movement->getKey(),
                'date' => $movement->created_at?->toDateTimeString(),
                'product' => $movement->product?->title,
                'code' => $movement->product?->code,
                'warehouse' => $movement->warehouse?->name,
                'type' => $movement->type->value,
                'type_label' => $movement->type->label(),
                'quantity' => $movement->quantity,
                'balance_after' => $movement->balance_after,
                'note' => $movement->note,
                'user' => $movement->user?->name,
            ]);

        return view('inventory.stock.movements', [
            'movements' => $movements,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'date_from' => $from,
                'date_to' => $to,
            ],
            'types' => collect(StockMovementType::cases())
                ->map(fn (StockMovementType $case): array => ['value' => $case->value, 'label' => $case->label()])
                ->all(),
        ]);
    }
}
