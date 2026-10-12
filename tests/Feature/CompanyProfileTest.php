<?php

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RbacSeeder;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function settingsAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

it('renders the company profile form for an administrator', function () {
    $this->actingAs(settingsAdmin())
        ->get('/settings/company')
        ->assertOk()
        ->assertSee('Profil Perusahaan');
});

it('updates the company profile', function () {
    $this->actingAs(settingsAdmin())
        ->put('/settings/company', [
            'company_name' => 'CV Prana Media',
            'company_address' => 'Jl. Merdeka No. 1, Makassar',
            'company_phone' => '0411-99999',
            'company_email' => 'halo@prana.media',
            'tax_rate' => '11',
        ])
        ->assertRedirect();

    expect(Setting::value('company.name'))->toBe('CV Prana Media')
        ->and(Setting::value('company.address'))->toBe('Jl. Merdeka No. 1, Makassar')
        ->and(Setting::value('company.phone'))->toBe('0411-99999')
        ->and(Setting::value('company.email'))->toBe('halo@prana.media')
        ->and(Setting::value('finance.tax_rate'))->toBe('11');
});

it('accepts an empty email and tax rate', function () {
    $this->actingAs(settingsAdmin())
        ->put('/settings/company', [
            'company_name' => 'CV Prana Media',
            'company_address' => '',
            'company_phone' => '',
            'company_email' => '',
            'tax_rate' => '',
        ])
        ->assertRedirect();

    expect(Setting::value('company.name'))->toBe('CV Prana Media')
        ->and(Setting::value('company.email'))->toBe('')
        ->and(Setting::value('finance.tax_rate'))->toBe('0');
});

it('validates the company profile fields', function () {
    $this->actingAs(settingsAdmin())
        ->put('/settings/company', ['company_name' => ''])
        ->assertSessionHasErrors('company_name');

    $this->actingAs(settingsAdmin())
        ->put('/settings/company', [
            'company_name' => 'CV Prana Media',
            'company_email' => 'bukan-email',
        ])
        ->assertSessionHasErrors('company_email');
});

it('prints the company profile on the invoice', function () {
    Setting::updateOrCreate(['key' => 'company.name'], ['value' => 'CV Prana Media', 'group' => 'company']);
    Setting::updateOrCreate(['key' => 'company.email'], ['value' => 'halo@prana.media', 'group' => 'company']);

    $invoice = Invoice::factory()->create();

    $this->actingAs(settingsAdmin())
        ->get('/invoices/'.$invoice->id.'/print')
        ->assertOk()
        ->assertSee('CV Prana Media')
        ->assertSee('halo@prana.media');
});

it('forbids a finance user from editing the company profile', function () {
    $keuangan = User::query()->where('email', 'keuangan@sekolah.test')->firstOrFail();

    $this->actingAs($keuangan)
        ->get('/settings/company')
        ->assertForbidden();

    $this->actingAs($keuangan)
        ->put('/settings/company', ['company_name' => 'Bukan Admin'])
        ->assertForbidden();

    expect(Setting::value('company.name'))->not->toBe('Bukan Admin');
});
