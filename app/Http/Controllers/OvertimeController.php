<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserOvertime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'users.manage'), 403);

        $users = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'admin'))
            ->orderBy('name')
            ->get(['id', 'name']);
        $overtimes = UserOvertime::query()
            ->with('user:id,name')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (UserOvertime $o): array => [
                'id' => $o->getKey(),
                'user' => $o->user?->name,
                'date' => $o->date?->format('d/m/Y'),
                'start_time' => $o->start_time ? substr((string) $o->start_time, 0, 5) : null,
                'end_time' => $o->end_time ? substr((string) $o->end_time, 0, 5) : null,
                'reason' => $o->reason,
            ]);

        return view('settings.overtimes', [
            'users' => $users,
            'overtimes' => $overtimes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->userCan($request, 'users.manage'), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        UserOvertime::query()->create($validated);

        return back()->with('success', 'Jam lembur ditambahkan.');
    }

    public function destroy(Request $request, UserOvertime $overtime): RedirectResponse
    {
        abort_unless($this->userCan($request, 'users.manage'), 403);

        $overtime->delete();

        return back()->with('success', 'Jam lembur dihapus.');
    }
}