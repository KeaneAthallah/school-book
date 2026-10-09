<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Record a significant business action for the audit trail.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $oldValues = [],
        array $newValues = [],
        ?User $user = null,
    ): void {
        $actor = $user ?? Auth::user();

        $class = $subject === null ? null : $subject::class;

        AuditLog::query()->create([
            'user_id' => $actor instanceof User ? $actor->getKey() : null,
            'action' => $action,
            'auditable_type' => $class,
            'auditable_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'ip_address' => Request::ip(),
        ]);
    }
}
