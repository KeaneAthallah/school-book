<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMutation;
use App\Services\PrintHeaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMutationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'fulfillment.view'), 403);

        $mutations = StockMutation::query()
            ->with(['product:id,code,title', 'creator:id,name'])
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (StockMutation $m): array => [
                'id' => $m->getKey(),
                'product' => $m->product?->title,
                'code' => $m->product?->code,
                'type' => $m->type,
                'type_label' => match ($m->type) {
                    'lost' => 'Hilang',
                    'damaged' => 'Rusak',
                    'borrowed' => 'Dipinjam',
                    default => $m->type,
                },
                'quantity' => $m->quantity,
                'reason' => $m->reason,
                'creator' => $m->creator?->name,
                'date' => $m->created_at?->toDateTimeString(),
            ]);

        return view('inventory.stock-mutations.index', [
            'mutations' => $mutations,
            'products' => Product::query()->active()->orderBy('title')->get(['id', 'code', 'title'])
                ->map(fn (Product $p): array => ['value' => $p->getKey(), 'label' => $p->code.' - '.$p->title])->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->userCan($request, 'fulfillment.pick'), 403);

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'string', 'in:lost,damaged,borrowed'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        StockMutation::query()->create([
            'product_id' => (int) $validated['product_id'],
            'type' => $validated['type'],
            'quantity' => (int) $validated['quantity'],
            'reason' => $validated['reason'] ?? null,
            'created_by' => $request->user()?->getKey(),
        ]);

        return back()->with('success', 'Mutasi stok dicatat.');
    }

    public function print(Request $request, StockMutation $mutation, PrintHeaderService $headers): View
    {
        abort_unless($this->userCan($request, 'fulfillment.view'), 403);

        $mutation->load(['product:id,code,title', 'creator:id,name']);

        return view('print.stock-mutation', [
            'mutation' => $mutation,
            'company' => $headers->resolve(),
        ]);
    }
}
