<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\Rebate;
use App\Models\RebateRule;
use App\Services\RebateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RebateController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'rebates.view'), 403);

        $search = $request->string('search')->toString();

        $rebates = Rebate::query()
            ->with(['school:id,name', 'invoice:id,number,total_amount,paid_amount,due_date,invoice_date,status', 'publisher:id,name', 'attachments'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $builder) use ($search): void {
                $builder->where('number', 'like', '%'.$search.'%')
                    ->orWhereHas('school', fn (Builder $school) => $school->where('name', 'like', '%'.$search.'%'));
            }))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Rebate $rebate): array => [
                'id' => $rebate->getKey(),
                'number' => $rebate->number,
                'school' => $rebate->school->name,
                'invoice' => $rebate->invoice?->number,
                'type' => $rebate->type,
                'base_amount' => $rebate->base_amount,
                'value' => $rebate->value,
                'amount' => $rebate->amount,
                'status' => $rebate->status->value,
                'status_label' => $rebate->status->label(),
                'requested_at' => $rebate->created_at?->toDateTimeString(),
                'attachments' => $rebate->attachments->sortByDesc('id')->map(fn (Attachment $attachment): array => [
                    'id' => $attachment->getKey(),
                    'name' => $attachment->original_name,
                    'url' => Storage::url($attachment->path),
                    'time' => $attachment->created_at?->toDateTimeString(),
                ])->all(),
            ]);

        return view('finance.rebates.index', [
            'rebates' => $rebates,
            'filters' => ['search' => $search],
            'rules' => RebateRule::query()->active()->orderBy('name')->get(['id', 'name', 'type', 'value'])
                ->map(fn (RebateRule $rule): array => [
                    'value' => $rule->getKey(),
                    'label' => $rule->name,
                    'type' => $rule->type,
                    'rate' => $rule->value,
                ])->all(),
            'eligibleInvoices' => Invoice::query()
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->whereColumn('paid_amount', '>=', 'total_amount')
                ->with('school:id,name')
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(fn (Invoice $invoice): array => [
                    'value' => $invoice->getKey(),
                    'label' => $invoice->number.' - '.$invoice->school->name,
                ])->all(),
            'can' => [
                'request' => $this->userCan($request, 'rebates.request'),
                'approve' => $this->userCan($request, 'rebates.approve'),
                'pay' => $this->userCan($request, 'rebates.pay'),
            ],
        ]);
    }

    public function store(Request $request, RebateService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'rebates.request'), 403);

        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'rebate_rule_id' => ['nullable', 'integer', 'exists:rebate_rules,id'],
            'type' => ['nullable', 'in:percentage,fixed'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $rebate = $service->request($validated, $request->user());

        return back()->with('success', 'Rebate '.$rebate->number.' diajukan.');
    }

    public function approve(Request $request, Rebate $rebate, RebateService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'rebates.approve'), 403);

        $service->approve($rebate, $request->user());

        return back()->with('success', 'Rebate '.$rebate->number.' disetujui.');
    }

    public function reject(Request $request, Rebate $rebate, RebateService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'rebates.approve'), 403);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $service->reject($rebate, (string) $validated['reason'], $request->user());

        return back()->with('success', 'Rebate '.$rebate->number.' ditolak.');
    }

    public function pay(Request $request, Rebate $rebate, RebateService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'rebates.pay'), 403);

        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
            'proof_photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'proof_photo.required' => 'Bukti foto pembayaran rebate wajib dilampirkan.',
            'proof_photo.image' => 'Bukti pembayaran rebate harus berupa gambar (JPEG, PNG, atau WebP).',
            'proof_photo.mimes' => 'Bukti pembayaran rebate harus berupa gambar (JPEG, PNG, atau WebP).',
            'proof_photo.max' => 'Bukti pembayaran rebate maksimal 5 MB.',
        ]);

        $service->markPaid($rebate, (string) $validated['reference'], $request->user(), $request->file('proof_photo'));

        return back()->with('success', 'Rebate '.$rebate->number.' ditandai dibayarkan.');
    }
}
