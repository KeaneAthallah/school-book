<?php

namespace App\Services;

use App\Models\Publisher;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class PrintHeaderService
{
    /**
     * Identity shown at the top of printed documents (invoice, delivery note).
     *
     * The header follows the publisher selected in the company profile,
     * or the company profile itself; a manual name overrides either.
     *
     * @return array<string, string>
     */
    public function resolve(): array
    {
        $manualName = trim((string) Setting::value('print.header_name', ''));
        $publisherId = (int) Setting::value('print.header_publisher_id', '0');

        $publisher = $publisherId > 0 ? Publisher::find($publisherId) : null;

        if ($publisher !== null) {
            return [
                'name' => $manualName !== '' ? $manualName : $publisher->name,
                'address' => (string) $publisher->address,
                'phone' => (string) $publisher->phone,
                'email' => (string) $publisher->email,
                'logo' => $this->url((string) $publisher->logo),
            ];
        }

        return [
            'name' => $manualName !== '' ? $manualName : (string) Setting::value('company.name', config('app.name')),
            'address' => (string) Setting::value('company.address', ''),
            'phone' => (string) Setting::value('company.phone', ''),
            'email' => (string) Setting::value('company.email', ''),
            'logo' => $this->url((string) Setting::value('company.logo', '')),
        ];
    }

    private function url(string $path): string
    {
        return $path !== '' ? Storage::url($path) : '';
    }
}
