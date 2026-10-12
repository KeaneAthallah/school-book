<?php

namespace App\Services\Dashboard;

use App\Dashboard\DashboardScope;
use App\Dashboard\Period;
use App\Models\User;
use App\Services\NotificationService;

/**
 * Base class for the six role workspaces.
 *
 * Services only resolve data. Which workspace a user may open is decided before
 * build() runs (DashboardRoleResolver plus the permission checks in the
 * controller), and which records may be seen is enforced by the DashboardScope
 * that is handed in - so no service ever makes an authorization decision of its
 * own and a role dashboard cannot accidentally widen access.
 */
abstract class RoleDashboard
{
    public function __construct(
        protected readonly DashboardMetrics $metrics,
        protected readonly NotificationService $notifications,
    ) {}

    /**
     * @return array<string, mixed>
     */
    abstract public function build(User $user, Period $period, DashboardScope $scope): array;

    /**
     * Operational alerts for the signed-in user, already restricted to the
     * records that user is allowed to see.
     *
     * @return list<array<string, mixed>>
     */
    protected function alerts(User $user, DashboardScope $scope): array
    {
        return $this->notifications->for($user, $scope);
    }
}
