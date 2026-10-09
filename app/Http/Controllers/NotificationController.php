<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __invoke(Request $request, NotificationService $notifications): JsonResponse
    {
        return response()->json([
            'notifications' => $notifications->for($request->user()),
        ]);
    }
}
