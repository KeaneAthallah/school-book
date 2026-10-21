<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Services\PrintHeaderService;
use App\Services\ShipmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'shipments.view'), 403);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $shipments = Shipment::query()
            ->with(['order:id,number,school_id', 'order.school:id,name', 'courier:id,name'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $builder) use ($search): void {
                $builder->where('number', 'like', '%'.$search.'%')
                    ->orWhere('delivery_note_number', 'like', '%'.$search.'%');
            }))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Shipment $shipment): array => [
                'id' => $shipment->getKey(),
                'number' => $shipment->number,
                'delivery_note_number' => $shipment->delivery_note_number,
                'order' => $shipment->order?->number,
                'school' => $shipment->order?->school?->name,
                'courier' => $shipment->courier?->name,
                'ship_date' => $shipment->ship_date?->toDateString(),
                'status' => $shipment->status->value,
                'status_label' => $shipment->status->label(),
            ]);

        return view('shipping.index', [
            'shipments' => $shipments,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => collect(ShipmentStatus::cases())
                ->map(fn (ShipmentStatus $case): array => ['value' => $case->value, 'label' => $case->label()])
                ->all(),
            'can' => ['create' => $this->userCan($request, 'shipments.manage')],
        ]);
    }

    public function create(Request $request, ShipmentService $service): View
    {
        abort_unless($this->userCan($request, 'shipments.manage'), 403);

        $order = null;

        if ($request->filled('order_id')) {
            $orderModel = Order::query()->with('items.product:id,code,title')->findOrFail($request->integer('order_id'));

            $order = [
                'id' => $orderModel->getKey(),
                'number' => $orderModel->number,
                'school' => $orderModel->school?->name,
                'items' => $orderModel->items
                    ->map(fn ($item): array => [
                        'order_item_id' => $item->getKey(),
                        'product' => $item->product?->title,
                        'code' => $item->product?->code,
                        'shippable' => $service->shippableQuantity($item),
                    ])
                    ->filter(fn (array $item): bool => $item['shippable'] > 0)
                    ->values()
                    ->all(),
            ];
        }

        return view('shipping.form', [
            'order' => $order,
            'orders' => $this->shippableOrders(),
            'couriers' => Courier::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Courier $courier): array => ['value' => $courier->getKey(), 'label' => $courier->name])->all(),
            'shippingMethods' => ShippingMethod::query()->active()->orderBy('name')->get(['id', 'name', 'cost'])
                ->map(fn (ShippingMethod $method): array => ['value' => $method->getKey(), 'label' => $method->name, 'cost' => $method->cost])->all(),
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, ShipmentService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'shipments.manage'), 403);

        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
            'shipping_method_id' => ['nullable', 'integer', 'exists:shipping_methods,id'],
            'ship_date' => ['nullable', 'date'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $order = Order::query()->findOrFail((int) $validated['order_id']);
        $shipment = $service->create($order, $validated, $request->user());

        return redirect()->route('shipments.show', $shipment)->with('success', 'Pengiriman '.$shipment->number.' dibuat.');
    }

    public function show(Request $request, Shipment $shipment): View
    {
        abort_unless($this->userCan($request, 'shipments.view'), 403);

        $shipment->load(['order:id,number,school_id', 'order.school:id,name,address', 'courier:id,name', 'shippingMethod:id,name', 'items.product:id,code,title']);

        return view('shipping.show', [
            'shipment' => [
                'id' => $shipment->getKey(),
                'number' => $shipment->number,
                'delivery_note_number' => $shipment->delivery_note_number,
                'order' => $shipment->order?->number,
                'school' => $shipment->order?->school?->name,
                'address' => $shipment->address ?? $shipment->order?->school?->address,
                'courier' => $shipment->courier?->name,
                'shipping_method' => $shipment->shippingMethod?->name,
                'tracking_number' => $shipment->tracking_number,
                'recipient_name' => $shipment->recipient_name,
                'ship_date' => $shipment->ship_date?->toDateString(),
                'status' => $shipment->status->value,
                'status_label' => $shipment->status->label(),
                'confirmed_at' => $shipment->confirmed_at?->toDateTimeString(),
                'notes' => $shipment->notes,
                'items' => $shipment->items->map(fn ($item): array => [
                    'product' => $item->product?->title,
                    'code' => $item->product?->code,
                    'quantity' => $item->quantity,
                ])->all(),
                'attachments' => $shipment->attachments()->orderByDesc('id')->get()->map(fn ($attachment): array => [
                    'id' => $attachment->getKey(),
                    'name' => $attachment->original_name,
                    'url' => Storage::url($attachment->path),
                    'time' => $attachment->created_at?->toDateTimeString(),
                    'type' => $attachment->type ?? 'delivery',
                ])->all(),
            ],
            'can' => [
                'confirm' => ($this->userCan($request, 'shipments.confirm')) && ! $shipment->isConfirmed(),
                'attach' => $this->userCan($request, 'shipments.confirm'),
                'manage_attachments' => $this->userCan($request, 'shipments.manage'),
                'deliver' => ($this->userCan($request, 'shipments.confirm')) && $shipment->status->value === 'shipped',
                'invoice' => ($this->userCan($request, 'invoices.manage')) && in_array($shipment->status->value, ['shipped', 'delivered'], true),
            ],
        ]);
    }

    public function confirm(Request $request, Shipment $shipment, ShipmentService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'shipments.confirm'), 403);

        $request->validate([
            'proof_photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'proof_photo.required' => 'Bukti foto pengiriman wajib dilampirkan.',
            'proof_photo.image' => 'Bukti pengiriman harus berupa gambar (JPEG, PNG, atau WebP).',
            'proof_photo.mimes' => 'Bukti pengiriman harus berupa gambar (JPEG, PNG, atau WebP).',
            'proof_photo.max' => 'Bukti pengiriman maksimal 5 MB.',
        ]);

        $service->confirm($shipment, $request->user(), $request->file('proof_photo'));

        return back()->with('success', 'Pesanan telah dikirim berikut bukti foto.');
    }

    public function storeAttachment(Request $request, Shipment $shipment): RedirectResponse
    {
        abort_unless($this->userCan($request, 'shipments.confirm'), 403);

        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'type' => ['nullable', 'string', 'in:delivery,receipt'],
        ], [
            'photo.required' => 'Bukti foto pengiriman wajib dilampirkan.',
            'photo.image' => 'Bukti pengiriman harus berupa gambar (JPEG, PNG, atau WebP).',
            'photo.mimes' => 'Bukti pengiriman harus berupa gambar (JPEG, PNG, atau WebP).',
            'photo.max' => 'Bukti pengiriman maksimal 5 MB.',
        ]);

        $photo = $request->file('photo');
        $path = $photo->store('shipments/'.$shipment->id, 'public');

        $shipment->attachments()->create([
            'original_name' => $photo->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $photo->getMimeType(),
            'size' => $photo->getSize(),
            'type' => $request->input('type', 'delivery'),
            'uploaded_by' => $request->user()?->getKey(),
        ]);

        return back()->with('success', 'Bukti foto pengiriman ditambahkan.');
    }

    public function deliver(Request $request, Shipment $shipment, ShipmentService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'shipments.confirm'), 403);

        $service->markDelivered($shipment, $request->user());

        return back()->with('success', 'Pengiriman ditandai diterima.');
    }

    public function print(Request $request, Shipment $shipment, PrintHeaderService $headers): View
    {
        abort_unless($this->userCan($request, 'shipments.view'), 403);

        $shipment->load(['order.school', 'courier', 'shippingMethod', 'createdBy:id,name', 'items.product']);

        return view('print.delivery-note', [
            'shipment' => $shipment,
            'company' => $headers->resolve(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function shippableOrders(): array
    {
        $existingPickingOrderIds = \App\Models\Picking::query()
            ->whereIn('status', [\App\Enums\PickingStatus::Draft->value, \App\Enums\PickingStatus::Picking->value, \App\Enums\PickingStatus::Picked->value])
            ->pluck('order_id')
            ->all();

        return Order::query()
            ->with('school:id,name')
            ->whereIn('status', [
                OrderStatus::PartialReady->value,
                OrderStatus::Ready->value,
                OrderStatus::PartialShipped->value,
            ])
            ->whereNotIn('id', $existingPickingOrderIds)
            ->orderByDesc('order_date')
            ->limit(50)
            ->get()
            ->map(fn (Order $order): array => [
                'value' => $order->getKey(),
                'label' => $order->number.' - '.$order->school->name,
            ])
            ->all();
    }
}
