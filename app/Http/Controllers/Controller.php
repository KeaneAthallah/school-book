<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Determine whether the authenticated user holds a permission.
     *
     * Uses an instanceof check so a missing user is denied rather than
     * causing an error, and so static analysis can narrow the type.
     */
    protected function userCan(Request $request, string $permission): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->hasPermission($permission);
    }
}
