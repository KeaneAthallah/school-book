<?php

use App\Models\Publisher;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function publisherAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

function publisherLogo(string $name): UploadedFile
{
    $path = sys_get_temp_dir().'/'.$name;
    copy(__DIR__.'/../Fixtures/proof.png', $path);

    return new UploadedFile($path, $name, 'image/png', null, true);
}

it('stores the publisher logo for an administrator', function () {
    Storage::fake('public');

    $this->actingAs(publisherAdmin())
        ->post('/master/publishers', [
            'name' => 'Penerbit Gamblang',
            'status' => 'active',
            'logo' => publisherLogo('logo-penerbit.png'),
        ])
        ->assertRedirect();

    $publisher = Publisher::query()->where('name', 'Penerbit Gamblang')->firstOrFail();

    expect($publisher->logo)->not->toBe('');

    Storage::disk('public')->assertExists($publisher->logo);
});

it('shows the publisher form with the logo field', function () {
    $publisher = Publisher::factory()->create(['name' => 'Penerbit Form']);

    $this->actingAs(publisherAdmin())
        ->get('/master/publishers/'.$publisher->id.'/edit')
        ->assertOk()
        ->assertSee('Logo');
});

it('replaces the logo on update and removes the old file', function () {
    Storage::fake('public');

    $publisher = Publisher::factory()->create(['name' => 'Penerbit Ganti']);

    $this->actingAs(publisherAdmin())
        ->put('/master/publishers/'.$publisher->id, [
            'name' => 'Penerbit Ganti',
            'status' => 'active',
            'logo' => publisherLogo('pertama.png'),
        ])
        ->assertRedirect();

    $first = $publisher->fresh()->logo;
    Storage::disk('public')->assertExists($first);

    $this->actingAs(publisherAdmin())
        ->put('/master/publishers/'.$publisher->id, [
            'name' => 'Penerbit Ganti',
            'status' => 'active',
            'logo' => publisherLogo('kedua.png'),
        ])
        ->assertRedirect();

    $second = $publisher->fresh()->logo;

    expect($second)->not->toBe($first);

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

it('keeps the existing logo when no new one is uploaded', function () {
    Storage::fake('public');

    $publisher = Publisher::factory()->create(['name' => 'Penerbit Tetap']);

    $this->actingAs(publisherAdmin())
        ->put('/master/publishers/'.$publisher->id, [
            'name' => 'Penerbit Tetap',
            'status' => 'active',
            'logo' => publisherLogo('awal.png'),
        ])
        ->assertRedirect();

    $original = $publisher->fresh()->logo;

    $this->actingAs(publisherAdmin())
        ->put('/master/publishers/'.$publisher->id, [
            'name' => 'Penerbit Tetap',
            'status' => 'active',
        ])
        ->assertRedirect();

    expect($publisher->fresh()->logo)->toBe($original);

    Storage::disk('public')->assertExists($original);
});

it('rejects a publisher logo that is not an image', function () {
    Storage::fake('public');

    $path = sys_get_temp_dir().'/bukan-gambar.txt';
    file_put_contents($path, 'bukan gambar');

    $this->actingAs(publisherAdmin())
        ->post('/master/publishers', [
            'name' => 'Penerbit Salah',
            'status' => 'active',
            'logo' => new UploadedFile($path, 'bukan-gambar.txt', 'text/plain', null, true),
        ])
        ->assertSessionHasErrors('logo');

    expect(Publisher::query()->where('name', 'Penerbit Salah')->count())->toBe(0);
});

it('removes the logo file when a publisher is deleted', function () {
    Storage::fake('public');

    $publisher = Publisher::factory()->create(['name' => 'Penerbit Hapus']);

    $this->actingAs(publisherAdmin())
        ->put('/master/publishers/'.$publisher->id, [
            'name' => 'Penerbit Hapus',
            'status' => 'active',
            'logo' => publisherLogo('akan-dihapus.png'),
        ])
        ->assertRedirect();

    $logo = $publisher->fresh()->logo;
    Storage::disk('public')->assertExists($logo);

    $this->actingAs(publisherAdmin())
        ->delete('/master/publishers/'.$publisher->id)
        ->assertRedirect();

    Storage::disk('public')->assertMissing($logo);
});

it('forbids a warehouse user from managing publishers', function () {
    $gudang = User::query()->where('email', 'gudang@sekolah.test')->firstOrFail();

    $this->actingAs($gudang)
        ->post('/master/publishers', ['name' => 'Penerbit Terlarang', 'status' => 'active'])
        ->assertForbidden();

    expect(Publisher::query()->where('name', 'Penerbit Terlarang')->count())->toBe(0);
});
