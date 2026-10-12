<?php

namespace App\Http\Controllers;

use App\Dashboard\DashboardRole;
use App\Dashboard\DashboardRoleResolver;
use App\Dashboard\DashboardScope;
use App\Dashboard\Period;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Dashboard\ExecutiveDashboardService;
use App\Services\Dashboard\FinanceDashboardService;
use App\Services\Dashboard\OperatorDashboardService;
use App\Services\Dashboard\RoleDashboard;
use App\Services\Dashboard\SalesDashboardService;
use App\Services\Dashboard\WarehouseDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Single authenticated dashboard entry point.
 *
 * The workspace is resolved from the user's persisted roles only - never from
 * request input - and each workspace receives a DashboardScope that restricts
 * the records it may query.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $admin,
        private readonly OperatorDashboardService $operator,
        private readonly WarehouseDashboardService $warehouse,
        private readonly FinanceDashboardService $finance,
        private readonly SalesDashboardService $sales,
        private readonly ExecutiveDashboardService $executive,
    ) {}

    public function __invoke(Request $request, DashboardRoleResolver $resolver): View
    {
        abort_unless($this->userCan($request, 'dashboard.view'), 403);

        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $period = Period::make(
            $request->string('date_from')->toString() ?: null,
            $request->string('date_to')->toString() ?: null,
        );

        $role = $resolver->resolve($user);

        // No recognised role: never guess a workspace and never leak records.
        // The user still gets a controlled, useful page.
        if ($role === null) {
            return view('dashboard.limited', [
                'period' => $period,
                'permissions' => $user->permissionSlugs(),
            ]);
        }

        $scope = DashboardScope::forRole($role, $user);

        return view($role->view(), [
            'role' => $role,
            'period' => $period,
            'data' => $this->serviceFor($role)->build($user, $period, $scope),
        ]);
    }

    private function serviceFor(DashboardRole $role): RoleDashboard
    {
        return match ($role) {
            DashboardRole::Admin => $this->admin,
            DashboardRole::Operator => $this->operator,
            DashboardRole::Warehouse => $this->warehouse,
            DashboardRole::Finance => $this->finance,
            DashboardRole::Sales => $this->sales,
            DashboardRole::Executive => $this->executive,
        };
    }
}
