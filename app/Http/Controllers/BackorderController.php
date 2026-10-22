<?php

namespace App\Http\Controllers;

use App\Enums\BackorderStatus;
use App\Models\Backorder;
use App\Models\BackorderOrder;
use App\Models\BackorderOrderItem;
use App\Models\OrderItem;
use App\Models\Publisher;
use App\Services\PrintHeaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackorderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'procurement.view'), 403);

        $backorders = Backorder::query()
            ->whereIn('status', [BackorderStatus::Open->value, BackorderStatus::Partial->value])
            ->with(['product:id,title', 'orderItem.order:id,number,school_id,order_date', 'orderItem.order.school:id,name', 'orders.publisher:id,name'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Backorder $backorder): array => [
                'id' => $backorder->getKey(),
                'product' => $backorder->product?->title,
                'order_number' => $backorder->orderItem?->order?->number,
                'school' => $backorder->orderItem?->order?->school?->name,
                'order_date' => $backorder->orderItem?->order?->order_date?->toDateString(),
                'quantity' => $backorder->quantity,
                'fulfilled_quantity' => $backorder->fulfilled_quantity,
                'outstanding_quantity' => $backorder->outstandingQuantity(),
                'status' => $backorder->status->value,
                'status_label' => $backorder->status->label(),
            ]);

        $backorderOrders = BackorderOrder::query()
            ->with(['items.backorder.product:id,title', 'publisher:id,name', 'creator:id,name'])
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString()
            ->through(fn (BackorderOrder $order): array => [
                'id' => $order->getKey(),
                'publisher' => $order->publisher?->name,
                'number' => $order->number,
                'status' => $order->items->isNotEmpty() && $order->items->every(fn ($i) => $i->backorder?->status === BackorderStatus::Fulfilled)
                    ? 'received'
                    : ($order->items->contains(fn ($i) => $i->backorder?->status === BackorderStatus::Fulfilled || $i->backorder?->status === BackorderStatus::Partial) ? 'partial' : $order->status),
                'status_label' => $order->items->isNotEmpty() && $order->items->every(fn ($i) => $i->backorder?->status === BackorderStatus::Fulfilled)
                    ? 'Diterima'
                    : ($order->items->contains(fn ($i) => $i->backorder?->status === BackorderStatus::Fulfilled || $i->backorder?->status === BackorderStatus::Partial) ? 'Sebagian Terpenuhi' : ($order->status === 'received' ? 'Diterima' : 'Menunggu')),
                'ordered_at' => $order->ordered_at?->format('d/m/Y'),
                'items_count' => $order->items->count(),
            ]);

        $openBackorders = Backorder::query()
            ->with(['product:id,title', 'orderItem.order:id,number,school_id', 'orderItem.order.school:id,name', 'orderItems.order'])
            ->whereIn('status', [BackorderStatus::Open->value, BackorderStatus::Partial->value])
            ->orderByDesc('id')
            ->get()
            ->map(function (Backorder $b): ?array {
                $outstanding = $b->outstandingQuantity();
                $pendingQty = (int) $b->orderItems
                    ->filter(fn ($oi) => $oi->order?->status === 'pending')
                    ->sum('quantity');
                $remaining = max(0, $outstanding - $pendingQty);
                if ($remaining <= 0) {
                    return null;
                }
                return [
                    'id' => $b->getKey(),
                    'product' => $b->product?->title,
                    'order_number' => $b->orderItem?->order?->number,
                    'school' => $b->orderItem?->order?->school?->name,
                    'outstanding' => $remaining,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return view('backorders.index', [
            'backorders' => $backorders,
            'backorderOrders' => $backorderOrders,
            'openBackorders' => $openBackorders,
            'publishers' => Publisher::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function orderStore(Request $request): RedirectResponse
    {
        abort_unless($this->userCan($request, 'procurement.manage'), 403);

        // Bulk: selected[] + items[backorder_id] = quantity
        $selected = $request->input('selected');
        $bulk = $request->input('items');
        if (is_array($selected) && $selected !== []) {
            $validated = $request->validate([
                'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
                'selected' => ['required', 'array'],
                'selected.*' => ['integer', 'exists:backorders,id'],
                'items' => ['nullable', 'array'],
            ]);

            $created = 0;
            if (count($validated['selected']) > 0) {
                $header = BackorderOrder::query()->create([
                    'backorder_id' => (int) $validated['selected'][0],
                    'publisher_id' => (int) $validated['publisher_id'],
                    'quantity' => 0,
                    'number' => $request->input('number'),
                    'note' => $request->input('note'),
                    'status' => 'pending',
                    'ordered_at' => now(),
                    'created_by' => $request->user()?->getKey(),
                ]);

                $total = 0;
                foreach ($validated['selected'] as $backorderId) {
                    $qty = (int) ($bulk[$backorderId] ?? 0);
                    if ($qty < 1) {
                        continue;
                    }
                    $backorder = Backorder::query()->find((int) $backorderId);
                    if ($backorder === null) {
                        continue;
                    }
                    BackorderOrderItem::query()->create([
                        'backorder_order_id' => $header->getKey(),
                        'backorder_id' => (int) $backorderId,
                        'quantity' => $qty,
                    ]);
                    $total += $qty;
                    $created++;
                }

                $header->update(['quantity' => $total]);
            }

            return back()->with('success', $created.' back order ditandai diorder ke penerbit.');
        }

        $validated = $request->validate([
            'backorder_id' => ['required', 'integer', 'exists:backorders,id'],
            'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'number' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $header = BackorderOrder::query()->create([
            'backorder_id' => (int) $validated['backorder_id'],
            'publisher_id' => (int) $validated['publisher_id'],
            'quantity' => (int) $validated['quantity'],
            'number' => $validated['number'] ?? null,
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
            'ordered_at' => now(),
            'created_by' => $request->user()?->getKey(),
        ]);

        BackorderOrderItem::query()->create([
            'backorder_order_id' => $header->getKey(),
            'backorder_id' => (int) $validated['backorder_id'],
            'quantity' => (int) $validated['quantity'],
        ]);

        return back()->with('success', 'Back order ditandai diorder ke penerbit.');
    }

    public function orderShow(Request $request, BackorderOrder $backorderOrder): View
    {
        abort_unless($this->userCan($request, 'procurement.view'), 403);

        $backorderOrder->load(['backorder.product:id,code,title', 'backorder.orderItem.order:id,number,school_id,order_date', 'backorder.orderItem.order.school:id,name', 'publisher:id,name,address,phone', 'creator:id,name']);

        return view('backorders.show', [
            'order' => $backorderOrder,
        ]);
    }

    public function orderEdit(Request $request, BackorderOrder $backorderOrder): View
    {
        abort_unless($this->userCan($request, 'procurement.manage'), 403);

        $backorderOrder->load(['backorder.product:id,title', 'publisher:id,name']);

        return view('backorders.edit', [
            'order' => $backorderOrder,
            'publishers' => Publisher::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function orderUpdate(Request $request, BackorderOrder $backorderOrder): RedirectResponse
    {
        abort_unless($this->userCan($request, 'procurement.manage'), 403);

        $validated = $request->validate([
            'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'number' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $backorderOrder->update([
            'publisher_id' => (int) $validated['publisher_id'],
            'number' => $validated['number'] ?? null,
            'note' => $validated['note'] ?? null,
        ]);

        $total = 0;
        if (! empty($validated['items'])) {
            foreach ($validated['items'] as $itemId => $qty) {
                $item = \App\Models\BackorderOrderItem::query()->find((int) $itemId);
                if ($item === null || (int) $item->backorder_order_id !== (int) $backorderOrder->getKey()) {
                    continue;
                }
                if ((int) $qty < 1) {
                    $item->delete();
                    continue;
                }
                $item->update(['quantity' => (int) $qty]);
                $total += (int) $qty;
            }
        } else {
            $total = (int) ($validated['quantity'] ?? $backorderOrder->quantity);
        }

        $backorderOrder->update(['quantity' => $total]);

        return redirect()->route('backorders.index')->with('success', 'Pesanan ke penerbit diperbarui.');
    }

    public function orderDestroy(Request $request, BackorderOrder $backorderOrder): RedirectResponse
    {
        abort_unless($this->userCan($request, 'procurement.manage'), 403);

        $backorderOrder->delete();

        return back()->with('success', 'Pesanan ke penerbit dihapus; back order kembali ke daftar.');
    }

    public function orderPrint(Request $request, BackorderOrder $backorderOrder, PrintHeaderService $headers): View
    {
        abort_unless($this->userCan($request, 'procurement.view'), 403);

        $backorderOrder->load(['items.backorder.product:id,code,title', 'items.backorder.orderItem.order:id,number,school_id,order_date', 'items.backorder.orderItem.order.school:id,name,address', 'publisher:id,name,address,phone', 'creator:id,name']);

        return view('print.backorder-order', [
            'order' => $backorderOrder,
            'company' => $headers->resolve(),
        ]);
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
