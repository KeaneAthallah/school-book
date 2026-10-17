<?php

namespace App\Http\Controllers;

use App\Models\Publisher;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
                'company_logo' => (string) Setting::value('company.logo', ''),
                'header_publisher_id' => (string) Setting::value('print.header_publisher_id', ''),
                'header_name' => (string) Setting::value('print.header_name', ''),
                'tax_rate' => (string) Setting::value('finance.tax_rate', '0'),
            ],
            'publishers' => Publisher::query()->orderBy('name')->pluck('name', 'id'),
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
            'company_logo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'header_publisher_id' => ['nullable', 'integer', 'exists:publishers,id'],
            'header_name' => ['nullable', 'string', 'max:255'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
        ]);

        $logo = $validated['company_logo'] ?? null;

        if ($logo instanceof UploadedFile) {
            $old = (string) Setting::value('company.logo', '');
            $path = $logo->store('company', 'public');

            if ($path === false) {
                return back()->with('error', 'Logo perusahaan gagal diunggah. Silakan coba lagi.');
            }

            if ($old !== '') {
                Storage::disk('public')->delete($old);
            }

            $this->put('company.logo', $path, 'company');
        }

        $this->put('company.name', $validated['company_name'], 'company');
        $this->put('company.address', $validated['company_address'] ?? '', 'company');
        $this->put('company.phone', $validated['company_phone'] ?? '', 'company');
        $this->put('company.email', $validated['company_email'] ?? '', 'company');
        $this->put('print.header_publisher_id', ($validated['header_publisher_id'] ?? null) === null ? '' : (string) $validated['header_publisher_id'], 'print');
        $this->put('print.header_name', $validated['header_name'] ?? '', 'print');
        $this->put('finance.tax_rate', ($validated['tax_rate'] ?? null) === null ? '0' : (string) $validated['tax_rate'], 'finance');

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
