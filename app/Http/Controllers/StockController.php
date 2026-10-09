<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Book;
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
            ->with(['book:id,code,title,min_stock', 'warehouse:id,name'])
            ->when($search !== '', fn (Builder $query) => $query->whereHas('book', function (Builder $book) use ($search): void {
                $book->where('title', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
            }))
            ->join('books', 'books.id', '=', 'stocks.book_id')
            ->orderBy('books.title')
            ->select('stocks.*')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Stock $stock): array => [
                'id' => $stock->getKey(),
                'book' => $stock->book->title,
                'code' => $stock->book->code,
                'warehouse' => $stock->warehouse->name,
                'quantity' => $stock->quantity,
                'reserved_quantity' => $stock->reserved_quantity,
                'available_quantity' => $stock->availableQuantity(),
                'min_stock' => $stock->book->min_stock,
                'low' => $stock->book !== null && $stock->getAttribute('quantity') <= $stock->book->min_stock && $stock->book->min_stock > 0,
            ]);

        $movements = StockMovement::query()
            ->with(['book:id,code,title', 'warehouse:id,name', 'user:id,name'])
            ->orderByDesc('id')
            ->limit(25)
            ->get()
            ->map(fn (StockMovement $movement): array => [
                'id' => $movement->getKey(),
                'date' => $movement->created_at?->toDateTimeString(),
                'book' => $movement->book?->title,
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
            'books' => Book::query()->active()->orderBy('title')->get(['id', 'code', 'title'])
                ->map(fn (Book $book): array => ['value' => $book->getKey(), 'label' => $book->code.' - '.$book->title])->all(),
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Warehouse $warehouse): array => ['value' => $warehouse->getKey(), 'label' => $warehouse->name])->all(),
            'can' => ['adjust' => $this->userCan($request, 'stock.adjust')],
        ]);
    }

    public function adjust(Request $request, InventoryService $inventory): RedirectResponse
    {
        abort_unless($this->userCan($request, 'stock.adjust'), 403);

        $validated = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $inventory->adjust(
            (int) $validated['book_id'],
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
            ->with(['book:id,code,title', 'warehouse:id,name', 'user:id,name'])
            ->when($type !== '', fn (Builder $query) => $query->where('type', $type))
            ->when($search !== '', fn (Builder $query) => $query->whereHas('book', function (Builder $book) use ($search): void {
                $book->where('title', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
            }))
            ->when($from !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (StockMovement $movement): array => [
                'id' => $movement->getKey(),
                'date' => $movement->created_at?->toDateTimeString(),
                'book' => $movement->book?->title,
                'code' => $movement->book?->code,
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
