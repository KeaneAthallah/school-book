<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Backorder;
use App\Models\Order;
use App\Models\OrderSource;
use App\Models\Product;
use App\Models\School;
use App\Models\Stock;
use App\Models\User;
use App\Models\Zona;
use App\Services\OrderService;
use App\Services\PrintHeaderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'orders.view'), 403);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $source = $request->string('source')->toString();

        $orders = $this->scoped($request)
            ->with(['school:id,name', 'orderSource:id,name'])
            ->withCount('items')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->where('number', 'like', '%'.$search.'%')
                        ->orWhereHas('school', fn (Builder $school) => $school->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($source !== '', fn (Builder $query) => $query->whereHas('orderSource', fn (Builder $sourceQuery) => $sourceQuery->where('code', $source)))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'id' => $order->getKey(),
                'number' => $order->number,
                'order_date' => $order->order_date->toDateString(),
                'school' => $order->school?->name,
                'source' => $order->orderSource?->name,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'items_count' => (int) $order->getAttribute('items_count'),
                'total_amount' => $order->total_amount,
            ]);

        return view('orders.index', [
            'orders' => $orders,
            'filters' => ['search' => $search, 'status' => $status, 'source' => $source],
            'statuses' => collect(OrderStatus::cases())
                ->map(fn (OrderStatus $case): array => ['value' => $case->value, 'label' => $case->label()])
                ->all(),
            'sources' => OrderSource::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn (OrderSource $source): array => ['value' => $source->code, 'label' => $source->name])
                ->all(),
            'can' => [
                'create' => $this->userCan($request, 'orders.manage'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->userCan($request, 'orders.manage'), 403);

        return view('orders.form', $this->formProps($request));
    }

    public function store(StoreOrderRequest $request, OrderService $service): RedirectResponse
    {
        $order = $service->create($request->validated(), $request->user());

        return redirect()->route('orders.show', $order)->with('success', 'Pesanan '.$order->number.' berhasil dibuat.');
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($this->userCan($request, 'orders.view'), 403);
        $this->authorizeSchoolAccess($request->user(), $order);

        $order->load([
            'school:id,name,npsn',
            'orderSource:id,name',
            'operator:id,name',
            'items' => fn ($query) => $query->with('product:id,code,title')->orderBy('id'),
            'items.warehouse:id,name',
            'shipments' => fn ($query) => $query->orderBy('id'),
            'shipments.items',
            'invoices' => fn ($query) => $query->orderBy('id'),
        ]);

        $backorders = Backorder::query()
            ->whereIn('order_item_id', $order->items->pluck('id'))
            ->with('product:id,title')
            ->get();

        return view('orders.show', [
            'order' => [
                'id' => $order->getKey(),
                'number' => $order->number,
                'order_date' => $order->order_date->toDateString(),
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'school' => $order->school?->name,
                'npsn' => $order->school?->npsn,
                'source' => $order->orderSource?->name,
                'operator' => $order->operator?->name,
                'notes' => $order->notes,
                'external_reference' => $order->external_reference,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'items' => $order->items->map(fn ($item): array => [
                    'id' => $item->getKey(),
                    'product' => $item->product?->title,
                    'code' => $item->product?->code,
                    'warehouse' => $item->warehouse?->name,
                    'quantity' => $item->quantity,
                    'reserved_quantity' => $item->reserved_quantity,
                    'shipped_quantity' => $item->shipped_quantity,
                    'backorder_quantity' => $item->backorder_quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                ])->all(),
                'shipments' => $order->shipments->map(fn ($shipment): array => [
                    'id' => $shipment->getKey(),
                    'number' => $shipment->number,
                    'delivery_note_number' => $shipment->delivery_note_number,
                    'status' => $shipment->status->value,
                    'status_label' => $shipment->status->label(),
                    'ship_date' => $shipment->ship_date?->toDateString(),
                    'quantity' => (int) $shipment->items->sum('quantity'),
                ])->all(),
                'invoices' => $order->invoices->map(fn ($invoice): array => [
                    'id' => $invoice->getKey(),
                    'number' => $invoice->number,
                    'status' => $invoice->status->value,
                    'status_label' => $invoice->status->label(),
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                ])->all(),
                'backorders' => $backorders->map(fn ($backorder): array => [
                    'id' => $backorder->getKey(),
                    'product' => $backorder->product?->title,
                    'quantity' => $backorder->quantity,
                    'fulfilled_quantity' => $backorder->fulfilled_quantity,
                    'status' => $backorder->status->value,
                    'status_label' => $backorder->status->label(),
                ])->all(),
            ],
            'can' => [
                'update' => ($this->userCan($request, 'orders.manage')) && $order->isEditable(),
                'confirm' => ($this->userCan($request, 'orders.confirm'))
                    && in_array($order->status->value, ['draft', 'received'], true),
                'cancel' => ($this->userCan($request, 'orders.cancel'))
                    && ! in_array($order->status->value, ['cancelled', 'fully_shipped', 'completed'], true),
                'ship' => ($this->userCan($request, 'shipments.manage'))
                    && (int) $order->items->sum('reserved_quantity') > 0,
                'invoice' => ($this->userCan($request, 'invoices.manage'))
                    && $order->shipments->contains(fn ($shipment): bool => in_array($shipment->status->value, ['shipped', 'delivered'], true)),
            ],
        ]);
    }

    public function edit(Request $request, Order $order): View
    {
        abort_unless($this->userCan($request, 'orders.manage'), 403);
        abort_unless($order->isEditable(), 403);

        return view('orders.form', [
            ...$this->formProps($request),
            'order' => [
                'id' => $order->getKey(),
                'number' => $order->number,
                'order_date' => $order->order_date->toDateString(),
                'school_id' => $order->school_id,
                'order_source_id' => $order->order_source_id,
                'notes' => $order->notes,
                'external_reference' => $order->external_reference,
                'discount_amount' => $order->discount_amount,
                'items' => $order->items()->get()->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                ])->all(),
            ],
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order, OrderService $service): RedirectResponse
    {
        $service->update($order, $request->validated(), $request->user());

        return redirect()->route('orders.show', $order)->with('success', 'Pesanan berhasil diperbarui.');
    }

    public function confirm(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'orders.confirm'), 403);

        $service->confirm($order, $request->user());

        return back()->with('success', 'Pesanan '.$order->number.' berhasil dikonfirmasi.');
    }

    public function cancel(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'orders.cancel'), 403);

        $service->cancel($order, $request->user());

        return back()->with('success', 'Pesanan '.$order->number.' dibatalkan.');
    }

    public function print(Request $request, Order $order, PrintHeaderService $headers): View
    {
        abort_unless($this->userCan($request, 'orders.view'), 403);
        $this->authorizeSchoolAccess($request->user(), $order);

        $order->load([
            'school',
            'orderSource:id,name',
            'items' => fn ($query) => $query->with('product:id,code,title')->orderBy('id'),
            'items.warehouse:id,name',
            'invoices',
        ]);

        return view('print.order-note', [
            'order' => $order,
            'company' => $headers->resolve(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request): array
    {
        $availability = Stock::query()
            ->selectRaw('product_id, COALESCE(SUM(quantity - reserved_quantity), 0) as available')
            ->groupBy('product_id')
            ->pluck('available', 'product_id');

        return [
            'schools' => School::query()->active()->orderBy('name')->get(['id', 'name', 'npsn', 'zona_id'])
                ->map(fn (School $school): array => ['value' => $school->getKey(), 'label' => $school->name.' ('.$school->npsn.')'])
                ->all(),
            'sources' => OrderSource::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (OrderSource $source): array => ['value' => $source->getKey(), 'label' => $source->name])
                ->all(),
            'products' => Product::query()->active()->with('prices')->orderBy('title')->get(['id', 'code', 'title', 'price'])
                ->map(fn (Product $product): array => [
                    'value' => $product->getKey(),
                    'label' => $product->code.' - '.$product->title,
                    'price' => $product->price,
                    'available' => (int) ($availability[$product->getKey()] ?? 0),
                ])
                ->all(),
            'zonas' => Zona::query()->active()->orderBy('code')->get(['id', 'code', 'name'])
                ->map(fn (Zona $zona): array => ['value' => $zona->getKey(), 'label' => $zona->name])
                ->all(),
            'defaultDate' => now()->toDateString(),
        ];
    }

    /**
     * @return Builder<Order>
     */
    private function scoped(Request $request): Builder
    {
        $query = Order::query();
        $user = $request->user();

        if ($user instanceof User && $user->hasRole('sales') && ! $user->isAdmin()) {
            $query->whereHas('school', fn (Builder $school) => $school->where('sales_user_id', $user->getKey()));
        }

        return $query;
    }

    private function authorizeSchoolAccess(?User $user, Order $order): void
    {
        if ($user === null) {
            return;
        }

        if ($user->hasRole('sales') && ! $user->isAdmin()) {
            abort_unless($order->school?->sales_user_id === $user->getKey(), 403);
        }
    }
}
