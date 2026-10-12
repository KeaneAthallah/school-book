<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function editCompany(Request $request): View
    {
        abort_unless($this->userCan($request, 'settings.manage'), 403);

        return view('settings.company', [
            'values' => [
                'company_name' => (string) Setting::value('company.name', config('app.name')),
                'company_address' => (string) Setting::value('company.address', ''),
                'company_phone' => (string) Setting::value('company.phone', ''),
                'company_email' => (string) Setting::value('company.email', ''),
                'tax_rate' => (string) Setting::value('finance.tax_rate', '0'),
            ],
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        abort_unless($this->userCan($request, 'settings.manage'), 403);

        $request->merge(array_map(
            static fn (mixed $value): mixed => $value === '' ? null : $value,
            $request->all(),
        ));

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'string', 'email', 'max:255'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
        ]);

        $this->put('company.name', $validated['company_name'], 'company');
        $this->put('company.address', $validated['company_address'] ?? '', 'company');
        $this->put('company.phone', $validated['company_phone'] ?? '', 'company');
        $this->put('company.email', $validated['company_email'] ?? '', 'company');
        $this->put('finance.tax_rate', $validated['tax_rate'] === null ? '0' : (string) $validated['tax_rate'], 'finance');

        return back()->with('success', 'Profil perusahaan diperbarui.');
    }

    private function put(string $key, string $value, string $group): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group],
        );
    }
}
