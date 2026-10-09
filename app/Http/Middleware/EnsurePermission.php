<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Allow the request when the authenticated user has at least one of the
     * given permission slugs.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if ($permissions !== []) {
            $granted = false;

            foreach ($permissions as $permission) {
                if ($user->hasPermission($permission)) {
                    $granted = true;
                    break;
                }
            }

            abort_unless($granted, 403);
        }

        return $next($request);
    }
}
