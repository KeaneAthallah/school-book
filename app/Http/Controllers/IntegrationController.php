<?php

namespace App\Http\Controllers;

use App\Models\IntegrationLog;
use App\Models\OrderSource;
use App\Services\IntegrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'integrations.view'), 403);

        $sources = OrderSource::query()
            ->orderBy('name')
            ->get()
            ->map(fn (OrderSource $source): array => [
                'id' => $source->getKey(),
                'code' => $source->code,
                'name' => $source->name,
                'type' => $source->type->value,
                'type_label' => $source->type->label(),
                'is_active' => $source->is_active,
                'enabled' => (bool) ($source->config['enabled'] ?? false),
            ])
            ->all();

        $logs = IntegrationLog::query()
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (IntegrationLog $log): array => [
                'id' => $log->getKey(),
                'date' => $log->created_at?->toDateTimeString(),
                'source' => $log->source,
                'direction' => $log->direction,
                'reference' => $log->reference,
                'status' => $log->status,
                'message' => $log->message,
            ])
            ->all();

        return view('integrations.index', [
            'sources' => $sources,
            'logs' => $logs,
            'can' => ['manage' => $this->userCan($request, 'integrations.manage')],
        ]);
    }

    public function sync(Request $request, OrderSource $source, IntegrationService $service): RedirectResponse
    {
        abort_unless($this->userCan($request, 'integrations.manage'), 403);

        $result = $service->sync($source, $request->user());

        if (! $result->successful()) {
            return back()->with('error', (string) $result->message);
        }

        return back()->with('success', "Sinkronisasi {$source->name}: {$result->created} diimpor, {$result->skipped} dilewati.");
    }
}
