<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardService $dashboard,
        NotificationService $notifications,
    ): View {
        abort_unless($this->userCan($request, 'dashboard.view'), 403);

        return view('dashboard.index', [
            'report' => $dashboard->report(
                $request->string('date_from')->toString() ?: null,
                $request->string('date_to')->toString() ?: null,
            ),
            'notifications' => $notifications->for($request->user()),
        ]);
    }
}
