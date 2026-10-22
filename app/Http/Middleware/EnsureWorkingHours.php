<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\UserOvertime;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkingHours
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (app()->environment('testing')) {
            return $next($request);
        }

        if ($user === null || $user->isAdmin()) {
            return $next($request);
        }

        if ($request->routeIs('login', 'logout', 'password.*')) {
            return $next($request);
        }

        $now = now();
        $current = $now->format('H:i:s');

        $start = $user->work_start_time?->format('H:i:s')
            ?? (string) Setting::value('work.default_start', '08:00:00');
        $end = $user->work_end_time?->format('H:i:s')
            ?? (string) Setting::value('work.default_end', '17:00:00');

        if (strlen($start) === 5) {
            $start .= ':00';
        }
        if (strlen($end) === 5) {
            $end .= ':00';
        }

        $inWorking = $this->within($current, $start, $end);

        if (! $inWorking) {
            $overtime = UserOvertime::query()
                ->where('user_id', $user->getKey())
                ->whereDate('date', $now->toDateString())
                ->get();

            foreach ($overtime as $ot) {
                $otStart = (string) $ot->start_time;
                $otEnd = (string) $ot->end_time;
                if (strlen($otStart) === 5) {
                    $otStart .= ':00';
                }
                if (strlen($otEnd) === 5) {
                    $otEnd .= ':00';
                }
                if ($this->within($current, $otStart, $otEnd)) {
                    $inWorking = true;
                    break;
                }
            }
        }

        if (! $inWorking) {
            abort(403, 'Akun Anda hanya dapat diakses pada jam kerja.');
        }

        return $next($request);
    }

    private function within(string $current, string $start, string $end): bool
    {
        if ($start <= $end) {
            return $current >= $start && $current <= $end;
        }

        return $current >= $start || $current <= $end;
    }
}