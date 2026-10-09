<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'audit.view'), 403);

        $search = $request->string('search')->toString();

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $builder) use ($search): void {
                $builder->where('action', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'id' => $log->getKey(),
                'date' => $log->created_at?->toDateTimeString(),
                'user' => $log->user?->name,
                'action' => $log->action,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'changes' => [
                    'old' => $log->old_values,
                    'new' => $log->new_values,
                ],
            ]);

        return view('settings.audit', [
            'logs' => $logs,
            'filters' => ['search' => $search],
        ]);
    }
}
