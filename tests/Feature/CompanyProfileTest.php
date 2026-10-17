<?php

use App\Models\Invoice;
use App\Models\Publisher;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function settingsAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

function companyLogo(string $name = 'logo-perusahaan.png'): UploadedFile
{
    $path = sys_get_temp_dir().'/'.$name;
    copy(__DIR__.'/../Fixtures/proof.png', $path);

    return new UploadedFile($path, $name, 'image/png', null, true);
}

function headerPublisher(array $attributes = []): Publisher
{
    return Publisher::query()->create(array_merge([
        'name' => 'Penerbit Header',
        'contact_person' => 'Budi',
        'phone' => '0411-12345',
        'email' => 'kontak@penerbit.test',
        'address' => 'Jl. Penerbit No. 1, Makassar',
        'status' => 'active',
    ], $attributes));
}

it('renders the company profile form for an administrator', function () {
    $publisher = headerPublisher(['name' => 'Penerbit Pilihan']);

    $this->actingAs(settingsAdmin())
        ->get('/settings/company')
        ->assertOk()
        ->assertSee('Profil Perusahaan')
        ->assertSee('Header Cetak')
        ->assertSee('Penerbit Pilihan')
        ->assertSee('name="company_logo"', false);
});

it('stores the company logo', function () {
    Storage::fake('public');

    $this->actingAs(settingsAdmin())
        ->put('/settings/company', [
            'company_name' => 'CV Prana Media',
            'company_logo' => companyLogo('logo-awal.png'),
        ])
        ->assertRedirect();

    $logo = (string) Setting::value('company.logo', '');

    expect($logo)->not->toBe('');

    Storage::disk('public')->assertExists($logo);
});

it('replaces the company logo and removes the old file', function () {
    Storage::fake('public');

    $this->actingAs(settingsAdmin())
        ->put('/settings/company', [
            'company_name' => 'CV Prana Media',
            'company_logo' => companyLogo('pertama.png'),
        ])
        ->assertRedirect();

    $first = (string) Setting::value('company.logo', '');

    $this->actingAs(settingsAdmin())
        ->put('/settings/company', [
            'company_name' => 'CV Prana Media',
            'company_logo' => companyLogo('kedua.png'),
        ])
        ->assertRedirect();

    $second = (string) Setting::value('company.logo', '');

    expect($second)->not->toBe($first);

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

it('keeps the existing logo and reports an error when the upload fails', function () {
    // Force the store() call to fail (it returns string|false) by making the
    // filesystem disk fail the write.
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('putFileAs')->once()->andReturn(false);

    $this->mock(FilesystemFactory::class, function ($mock) use ($disk) {
        $mock->shouldReceive('disk')->andReturn($disk);
    });

    Setting::updateOrCreate(['key' => 'company.logo'], ['value' => 'company/logo-lama.png', 'group' => 'company']);

    $this->actingAs(settingsAdmin())
        ->put('/settings/company', [
            'company_name' => 'CV Prana Media',
            'company_logo' => companyLogo('logo-gagal.png'),
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Setting::value('company.logo'))->toBe('company/logo-lama.png');
});

it('rejects a company logo that is not an image', function () {
    Storage::fake('public');

    $path = sys_get_temp_dir().'/bukan-logo.txt';
    file_put_contents($path, 'bukan logo');

    $this->actingAs(settingsAdmin())
        ->put('/settings/company', [
            'company_name' => 'CV Prana Media',
            'company_logo' => new UploadedFile($path, 'bukan-logo.txt', 'text/plain', null, true),
        ])
        ->assertSessionHasErrors('company_logo');
});

it('saves the print header selection and manual name', function () {
    $publisher = headerPublisher();

    $this->actingAs(settingsAdmin())
        ->put('/settings/company', [
            'company_name' => 'CV Prana Media',
            'header_publisher_id' => (string) $publisher->id,
            'header_name' => 'PT Header Kustom',
        ])
        ->assertRedirect();

    expect(Setting::value('print.header_publisher_id'))->toBe((string) $publisher->id)
        ->and(Setting::value('print.header_name'))->toBe('PT Header Kustom');
});

it('rejects an unknown print header publisher', function () {
    $this->actingAs(settingsAdmin())
        ->put('/settings/company', [
            'company_name' => 'CV Prana Media',
            'header_publisher_id' => '999999',
        ])
        ->assertSessionHasErrors('header_publisher_id');
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

it('prints the company logo on the invoice', function () {
    Storage::fake('public');

    $logo = companyLogo('logo-cetak.png')->store('company', 'public');
    Setting::updateOrCreate(['key' => 'company.logo'], ['value' => $logo, 'group' => 'company']);

    $invoice = Invoice::factory()->create();

    $this->actingAs(settingsAdmin())
        ->get('/invoices/'.$invoice->id.'/print')
        ->assertOk()
        ->assertSee(Storage::url($logo));
});

it('prints the publisher header on the invoice', function () {
    $publisher = headerPublisher();
    Setting::updateOrCreate(['key' => 'print.header_publisher_id'], ['value' => (string) $publisher->id, 'group' => 'print']);

    $invoice = Invoice::factory()->create();

    $this->actingAs(settingsAdmin())
        ->get('/invoices/'.$invoice->id.'/print')
        ->assertOk()
        ->assertSee('Penerbit Header')
        ->assertSee('Jl. Penerbit No. 1, Makassar')
        ->assertSee('kontak@penerbit.test');
});

it('prints the publisher header on the delivery note', function () {
    $publisher = headerPublisher(['name' => 'Penerbit Surat Jalan']);
    Setting::updateOrCreate(['key' => 'print.header_publisher_id'], ['value' => (string) $publisher->id, 'group' => 'print']);

    $shipment = Shipment::factory()->create();

    $this->actingAs(settingsAdmin())
        ->get('/shipments/'.$shipment->id.'/print')
        ->assertOk()
        ->assertSee('Penerbit Surat Jalan')
        ->assertSee('Jl. Penerbit No. 1, Makassar');
});

it('prints the manual header name on the invoice', function () {
    Setting::updateOrCreate(['key' => 'print.header_name'], ['value' => 'PT Header Kustom', 'group' => 'print']);
    Setting::updateOrCreate(['key' => 'company.name'], ['value' => 'CV Prana Media', 'group' => 'company']);

    $invoice = Invoice::factory()->create();

    $this->actingAs(settingsAdmin())
        ->get('/invoices/'.$invoice->id.'/print')
        ->assertOk()
        ->assertSee('PT Header Kustom')
        ->assertDontSee('CV Prana Media');
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
