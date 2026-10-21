<?php

namespace App\Http\Controllers;

use App\Enums\PickingStatus;
use App\Models\Order;
use App\Models\Picking;
use App\Services\FulfillmentService;
use App\Services\PrintHeaderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PickingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'fulfillment.view'), 403);

        $status = $request->string('status')->toString();

        $pickings = Picking::query()
            ->with(['order:id,number,school_id', 'order.school:id,name'])
            ->withCount('items')
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Picking $picking): array => [
                'id' => $picking->getKey(),
                'number' => $picking->number,
                'order' => $picking->order?->number,
                'school' => $picking->order?->school?->name,
                'status' => $picking->status->value,
                'status_label' => $picking->status->label(),
                'items_count' => (int) $picking->getAttribute('items_count'),
                'created_at' => $picking->created_at?->toDateTimeString(),
            ]);

        return view('inventory.pickings.index', [
            'pickings' => $pickings,
            'filters' => ['status' => $status],
            'statuses' => collect(PickingStatus::cases())
                ->map(fn (PickingStatus $case): array => ['value' => $case->value, 'label' => $case->label()])
                ->all(),
        ]);
    }

    public function store(Request $request, FulfillmentService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'fulfillment.pick'), 403);

        $validated = $request->validate(['order_id' => ['required', 'integer', 'exists:orders,id']]);

        $order = Order::query()->findOrFail((int) $validated['order_id']);
        $picking = $service->create($order, $request->user());

        return redirect()->route('pickings.show', $picking)->with('success', 'Picking '.$picking->number.' berhasil dibuat.');
    }

    public function show(Request $request, Picking $picking): View
    {
        abort_unless($this->userCan($request, 'fulfillment.view'), 403);

        $picking->load(['order:id,number,school_id', 'order.school:id,name', 'items.product:id,code,title']);

        return view('inventory.pickings.show', [
            'picking' => [
                'id' => $picking->getKey(),
                'number' => $picking->number,
                'order' => $picking->order?->number,
                'school' => $picking->order?->school?->name,
                'status' => $picking->status->value,
                'status_label' => $picking->status->label(),
                'items' => $picking->items->map(fn ($item): array => [
                    'id' => $item->getKey(),
                    'product' => $item->product?->title,
                    'code' => $item->product?->code,
                    'quantity' => $item->quantity,
                    'picked_quantity' => $item->picked_quantity,
                ])->all(),
            ],
            'can' => [
                'pick' => ($this->userCan($request, 'fulfillment.pick')) && in_array($picking->status->value, ['draft', 'picking'], true),
                'pack' => ($this->userCan($request, 'fulfillment.pack')) && $picking->status->value === 'picked',
            ],
        ]);
    }

    public function print(Request $request, Picking $picking, PrintHeaderService $headers): View
    {
        abort_unless($this->userCan($request, 'fulfillment.view'), 403);

        $picking->load([
            'order:id,number,school_id,order_date',
            'order.school:id,name,npsn,address',
            'items.product:id,code,title', 'items.product.book.publisher:id,name',
        ]);

        return view('print.picking-note', [
            'picking' => $picking,
            'company' => $headers->resolve(),
        ]);
    }

    public function destroy(Request $request, Picking $picking): RedirectResponse
    {
        abort_unless($this->userCan($request, 'fulfillment.pick'), 403);

        $picking->delete();

        return redirect()->route('pickings.index')->with('success', 'Picking '.$picking->number.' berhasil dihapus.');
    }

    public function record(Request $request, Picking $picking, FulfillmentService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'fulfillment.pick'), 403);

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.picked_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $service->recordPicked($picking, $validated['items'], $request->user());

        return back()->with('success', 'Hasil picking disimpan.');
    }

    public function pack(Request $request, Picking $picking, FulfillmentService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'fulfillment.pack'), 403);

        $service->pack($picking, $request->user());

        $next = Picking::query()
            ->where('id', '>', $picking->getKey())
            ->whereIn('status', [PickingStatus::Draft->value, PickingStatus::Picking->value])
            ->orderBy('id')
            ->first();

        if ($next instanceof Picking) {
            return redirect()->route('pickings.show', $next)->with('success', 'Picking '.$picking->number.' selesai dipacking. Lanjut ke picking berikutnya.');
        }

        return redirect()->route('pickings.index', ['status' => 'draft'])->with('success', 'Picking '.$picking->number.' selesai dipacking dan siap kirim.');
    }

    public function recordAndPack(Request $request, Picking $picking, FulfillmentService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'fulfillment.pick'), 403);
        abort_unless($this->userCan($request, 'fulfillment.pack'), 403);

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.picked_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $service->recordPicked($picking, $validated['items'], $request->user());
        $picking->refresh();
        $service->pack($picking, $request->user());

        $next = Picking::query()
            ->where('id', '>', $picking->getKey())
            ->whereIn('status', [PickingStatus::Draft->value, PickingStatus::Picking->value])
            ->orderBy('id')
            ->first();

        if ($next instanceof Picking) {
            return redirect()->route('pickings.show', $next)->with('success', 'Picking '.$picking->number.' selesai. Lanjut ke picking berikutnya.');
        }

        return redirect()->route('pickings.index', ['status' => 'draft'])->with('success', 'Picking '.$picking->number.' selesai dipacking dan siap kirim.');
    }
}
