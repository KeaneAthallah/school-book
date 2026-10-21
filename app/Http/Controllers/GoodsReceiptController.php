<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\Publisher;
use App\Services\GoodsReceiptService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'receipts.view'), 403);

        $search = $request->string('search')->toString();

        $receipts = GoodsReceipt::query()
            ->with(['publisher:id,name'])
            ->withCount('items')
            ->when($search !== '', fn (Builder $query) => $query->where('number', 'like', '%'.$search.'%'))
            ->orderByDesc('receipt_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (GoodsReceipt $receipt): array => [
                'id' => $receipt->getKey(),
                'number' => $receipt->number,
                'receipt_date' => $receipt->receipt_date->toDateString(),
                'publisher' => $receipt->publisher?->name,
                'status' => $receipt->status->value,
                'status_label' => $receipt->status->label(),
                'items_count' => (int) $receipt->getAttribute('items_count'),
            ]);

        return view('inventory.receipts.index', [
            'receipts' => $receipts,
            'filters' => ['search' => $search],
            'can' => ['create' => $this->userCan($request, 'receipts.manage')],
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->userCan($request, 'receipts.manage'), 403);

        return view('inventory.receipts.form', $this->formProps());
    }

    public function store(StoreGoodsReceiptRequest $request, GoodsReceiptService $service): RedirectResponse
    {
        $receipt = $service->create($request->validated(), $request->user());

        return redirect()->route('receipts.show', $receipt)->with('success', 'Penerimaan '.$receipt->number.' berhasil dibuat.');
    }

    public function show(Request $request, GoodsReceipt $receipt): View
    {
        abort_unless($this->userCan($request, 'receipts.view'), 403);

        $receipt->load(['publisher:id,name', 'items.product:id,code,title', 'confirmedBy:id,name']);

        return view('inventory.receipts.show', [
            'receipt' => [
                'id' => $receipt->getKey(),
                'number' => $receipt->number,
                'receipt_date' => $receipt->receipt_date->toDateString(),
                'publisher' => $receipt->publisher?->name,
                'status' => $receipt->status->value,
                'status_label' => $receipt->status->label(),
                'notes' => $receipt->notes,
                'confirmed_by' => $receipt->confirmedBy?->name,
                'confirmed_at' => $receipt->confirmed_at?->toDateTimeString(),
                'items' => $receipt->items->map(fn ($item): array => [
                    'product' => $item->product?->title,
                    'code' => $item->product?->code,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                ])->all(),
            ],
            'can' => ['confirm' => $this->userCan($request, 'receipts.confirm')],
        ]);
    }

    public function edit(Request $request, GoodsReceipt $receipt): View
    {
        abort_unless($this->userCan($request, 'receipts.manage'), 403);
        abort_unless($receipt->isDraft(), 403);

        $receipt->load(['publisher:id,name', 'items.product:id,code,title']);

        return view('inventory.receipts.form', array_merge($this->formProps(), [
            'receipt' => [
                'id' => $receipt->getKey(),
                'publisher_id' => $receipt->publisher_id,
                'receipt_date' => $receipt->receipt_date->toDateString(),
                'notes' => $receipt->notes,
                'items' => $receipt->items->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'product_label' => $item->product?->code.' - '.$item->product?->title,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                ])->all(),
            ],
        ]));
    }

    public function update(StoreGoodsReceiptRequest $request, GoodsReceipt $receipt, GoodsReceiptService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'receipts.manage'), 403);

        $service->update($receipt, $request->validated(), $request->user());

        return redirect()->route('receipts.show', $receipt)->with('success', 'Penerimaan '.$receipt->number.' diperbarui.');
    }

    public function confirm(Request $request, GoodsReceipt $receipt, GoodsReceiptService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'receipts.confirm'), 403);

        $service->confirm($receipt, $request->user());

        return back()->with('success', 'Penerimaan '.$receipt->number.' dikonfirmasi dan stok diperbarui.');
    }

    public function destroy(Request $request, GoodsReceipt $receipt, GoodsReceiptService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'receipts.manage'), 403);

        $service->delete($receipt, $request->user());

        return redirect()->route('receipts.index')->with('success', 'Penerimaan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(): array
    {
        return [
            'publishers' => Publisher::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Publisher $publisher): array => ['value' => $publisher->getKey(), 'label' => $publisher->name])->all(),
            'products' => Product::query()->active()->orderBy('title')->get(['id', 'code', 'title', 'cost_price'])
                ->map(fn (Product $product): array => [
                    'value' => $product->getKey(),
                    'label' => $product->code.' - '.$product->title,
                    'cost_price' => $product->cost_price,
                ])->all(),
            'defaultDate' => now()->toDateString(),
        ];
    }
}
