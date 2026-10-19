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
                'book_tax_rate' => (string) Setting::value('finance.book_tax_rate', '0.5'),
                'goods_tax_rate' => (string) Setting::value('finance.goods_tax_rate', '2'),
                'ppn_rate' => (string) Setting::value('finance.ppn_rate', '12'),
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

        // Accept common alias field names for tax rates.
        $aliases = [
            'tax_book_rate' => 'book_tax_rate',
            'book_tax' => 'book_tax_rate',
            'tax_book' => 'book_tax_rate',
            'pajak_buku' => 'book_tax_rate',
            'tax_goods_rate' => 'goods_tax_rate',
            'goods_tax' => 'goods_tax_rate',
            'tax_goods' => 'goods_tax_rate',
            'pajak_barang' => 'goods_tax_rate',
            'pajak_barang_lainnya' => 'goods_tax_rate',
            'ppn' => 'ppn_rate',
            'tax_ppn' => 'ppn_rate',
            'tax_ppn_rate' => 'ppn_rate',
        ];
        foreach ($aliases as $alias => $canonical) {
            if ($request->has($alias) && ! $request->has($canonical)) {
                $request->merge([$canonical => $request->input($alias)]);
            }
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'string', 'email', 'max:255'],
            'company_logo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'header_publisher_id' => ['nullable', 'integer', 'exists:publishers,id'],
            'header_name' => ['nullable', 'string', 'max:255'],
            'book_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'goods_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ppn_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
        $bookRate = ($validated['book_tax_rate'] ?? null) === null ? '0.5' : (string) $validated['book_tax_rate'];
        $goodsRate = ($validated['goods_tax_rate'] ?? null) === null ? '2' : (string) $validated['goods_tax_rate'];
        $ppnRate = ($validated['ppn_rate'] ?? null) === null ? '12' : (string) $validated['ppn_rate'];
        foreach (['finance.book_tax_rate', 'finance.tax_book_rate', 'finance.book_tax', 'finance.tax_book', 'finance.pajak_buku'] as $k) {
            $this->put($k, $bookRate, 'finance');
        }
        foreach (['finance.goods_tax_rate', 'finance.tax_goods_rate', 'finance.goods_tax', 'finance.tax_goods', 'finance.pajak_barang_lainnya', 'finance.pajak_barang'] as $k) {
            $this->put($k, $goodsRate, 'finance');
        }
        foreach (['finance.ppn_rate', 'finance.tax_ppn_rate', 'finance.ppn', 'finance.tax_ppn'] as $k) {
            $this->put($k, $ppnRate, 'finance');
        }

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
