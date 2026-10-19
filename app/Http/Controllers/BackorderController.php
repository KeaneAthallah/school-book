<?php

namespace App\Http\Controllers;

use App\Models\Backorder;
use App\Models\BackorderOrder;
use App\Models\OrderItem;
use App\Models\Publisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackorderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'procurement.view'), 403);

        $backorders = Backorder::query()
            ->with(['product:id,title', 'orderItem.order:id,number,school_id,order_date', 'orderItem.order.school:id,name', 'orders.publisher:id,name'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('backorders.index', [
            'backorders' => $backorders,
            'publishers' => Publisher::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function orderStore(Request $request): RedirectResponse
    {
        abort_unless($this->userCan($request, 'procurement.manage'), 403);

        $validated = $request->validate([
            'backorder_id' => ['required', 'integer', 'exists:backorders,id'],
            'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'number' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        BackorderOrder::query()->create([
            'backorder_id' => (int) $validated['backorder_id'],
            'publisher_id' => (int) $validated['publisher_id'],
            'quantity' => (int) $validated['quantity'],
            'number' => $validated['number'] ?? null,
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
            'ordered_at' => now(),
            'created_by' => $request->user()?->getKey(),
        ]);

        return back()->with('success', 'Back order ditandai diorder ke penerbit.');
    }

    public function unavailable(Request $request): View
    {
        abort_unless($this->userCan($request, 'procurement.view'), 403);

        $items = OrderItem::query()
            ->where('availability_status', 'unavailable')
            ->with(['product:id,code,title', 'order:id,number,school_id,order_date', 'order.school:id,name'])
            ->orderByDesc('id')
            ->get();

        $groups = $items->groupBy('product_id')->map(function ($rows) {
            $product = $rows->first()->product;

            return [
                'product' => $product->title ?? '-',
                'code' => $product?->code,
                'total_qty' => (int) $rows->sum('quantity'),
                'details' => $rows->map(fn (OrderItem $item): array => [
                    'order_number' => $item->order?->number,
                    'school' => $item->order?->school?->name,
                    'date' => $item->order?->order_date?->toDateString(),
                    'quantity' => $item->quantity,
                    'status' => $item->order?->status instanceof \BackedEnum ? $item->order->status->value : (string) $item->order?->status,
                ])->all(),
            ];
        })->values()->all();

        return view('backorders.unavailable', ['groups' => $groups]);
    }
}
