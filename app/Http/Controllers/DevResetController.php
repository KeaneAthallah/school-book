<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class DevResetController extends Controller
{
    /**
     * Environments in which the dev reset tool is allowed to run.
     *
     * @var list<string>
     */
    private const ALLOWED_ENVIRONMENTS = ['local', 'development', 'dev', 'testing'];

    private function ensureAllowed(Request $request): void
    {
        abort_unless(in_array(app()->environment(), self::ALLOWED_ENVIRONMENTS, true), 404);
        abort_unless($this->userCan($request, 'dev.reset'), 403);
    }

    public function show(Request $request): View
    {
        $this->ensureAllowed($request);

        return view('dev.reset');
    }

    public function reset(Request $request): RedirectResponse
    {
        $this->ensureAllowed($request);

        $request->validate([
            'confirmation' => ['required', 'string', 'in:RESET'],
        ]);

        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        return redirect()
            ->route('dev.reset.show')
            ->with('success', 'Database direset (migrate:fresh --seed).');
    }
}