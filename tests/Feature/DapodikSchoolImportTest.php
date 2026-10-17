<?php

use App\Models\School;
use App\Services\DapodikSchoolImportService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(DapodikSchoolImportService::SNAPSHOT_DISK);
    Storage::disk(DapodikSchoolImportService::SNAPSHOT_DISK)->makeDirectory(DapodikSchoolImportService::SNAPSHOT_DIR);
});

function fixtureSnapshot(array $rows): void
{
    Storage::disk(DapodikSchoolImportService::SNAPSHOT_DISK)
        ->put(DapodikSchoolImportService::SNAPSHOT_DIR.'/morowali-schools.json', json_encode([
            'captured_at' => '2026-09-11T05:34:03.275Z',
            'source' => 'dapodik',
            'region_code' => '180700',
            'region_name' => 'Kabupaten Morowali',
            'province_code' => '180000',
            'province' => 'Prov. Sulawesi Tengah',
            'semester' => '20261',
            'schools' => $rows,
        ]));
}

it('maps a Dapodik snapshot row into the existing schools columns', function () {
    fixtureSnapshot([
        [
            'npsn' => '12345678',
            'nama' => 'SD NEGERI 1 SAMPARENGGA',
            'bentuk_pendidikan' => 'SD',
            'provinsi' => 'Prov. Sulawesi Tengah',
            'kabupaten' => 'Kab. Morowali',
            'kecamatan' => 'Kec. Menui Kepulauan',
            'desa_kelurahan' => 'Samarengga',
            'alamat_jalan' => 'Jl. Merdeka',
            'kode_pos' => '94675',
        ],
    ]);

    $result = app(DapodikSchoolImportService::class)->import();

    expect($result)
        ->created->toBe(1)
        ->skipped_existing->toBe(0)
        ->skipped_invalid->toBe(0)
        ->total->toBe(1);

    expect(School::where('npsn', '12345678')->first())
        ->name->toBe('SD NEGERI 1 SAMPARENGGA')
        ->address->toBe('Jl. Merdeka, Samarengga')
        ->district->toBe('Menui Kepulauan')
        ->regency->toBe('Morowali')
        ->province->toBe('Sulawesi Tengah')
        ->postal_code->toBe('94675')
        ->status->toBe('active');
});

it('is idempotent — rerunning never duplicates by npsn', function () {
    fixtureSnapshot([
        ['npsn' => '12345678', 'nama' => 'SD NEGERI 1 SAMPARENGGA', 'kecamatan' => 'Kec. A', 'kabupaten' => 'Kab. Morowali', 'provinsi' => 'Prov. Sulawesi Tengah', 'alamat_jalan' => 'Jl. X', 'desa_kelurahan' => 'Desa', 'kode_pos' => '12345'],
        ['npsn' => '', 'nama' => 'Sekolah Tanpa NPSN', 'kecamatan' => 'Kec. B', 'kabupaten' => 'Kab. Tomini', 'provinsi' => 'Prov. Sulawesi Tengah'],
    ]);

    $service = app(DapodikSchoolImportService::class);

    $first = $service->import();
    $second = $service->import();

    expect(School::count())->toBe(2);
    expect($first['created'])->toBe(2)->and($second['created'])->toBe(0)->and($second['skipped_existing'])->toBe(2);
});

it('skips rows with an empty name', function () {
    fixtureSnapshot([
        ['npsn' => '99999999', 'nama' => '', 'kecamatan' => 'Kec. A'],
        ['npsn' => '11111111', 'nama' => 'Sekolah Valid', 'kecamatan' => 'Kec. A', 'kabupaten' => 'Kab. Morowali', 'provinsi' => 'Prov. Sulawesi Tengah'],
    ]);

    $result = app(DapodikSchoolImportService::class)->import();

    expect($result['skipped_invalid'])->toBe(1)->and($result['created'])->toBe(1);
    expect(School::where('npsn', '99999999')->exists())->toBeFalse();
});

it('never touches manually maintained rows unless --update', function () {
    // A sales-created school with our own district/contact info.
    School::factory()->create([
        'npsn' => '12345678',
        'name' => 'SD NEGERI 1 SAMPARENGGA',
        'district' => 'Kec. Sales Dulu',
        'phone' => '081234567890',
    ]);

    fixtureSnapshot([
        ['npsn' => '12345678', 'nama' => 'SD NEGERI 1 SAMPARENGGA', 'kecamatan' => 'Kec. Menui Kepulauan', 'kabupaten' => 'Kab. Morowali', 'provinsi' => 'Prov. Sulawesi Tengah', 'alamat_jalan' => 'Jl. Merdeka', 'desa_kelurahan' => 'Samarengga', 'kode_pos' => '94675'],
    ]);

    $default = app(DapodikSchoolImportService::class)->import();
    $school = School::where('npsn', '12345678')->first();

    expect($default['skipped_existing'])->toBe(1)->and($default['updated'])->toBe(0);
    expect($school->district)->toBe('Kec. Sales Dulu')
        ->and($school->phone)->toBe('081234567890');

    $withUpdate = app(DapodikSchoolImportService::class)->import(updateExisting: true);
    $school->refresh();

    expect($withUpdate['updated'])->toBe(1);
    expect($school->district)->toBe('Menui Kepulauan')
        ->and($school->phone)->toBe('081234567890'); // sales-owned field untouched
});

it('fails loudly when the snapshot directory is missing', function () {
    Storage::disk(DapodikSchoolImportService::SNAPSHOT_DISK)
        ->deleteDirectory(DapodikSchoolImportService::SNAPSHOT_DIR);

    expect(fn () => app(DapodikSchoolImportService::class)->import())
        ->toThrow(RuntimeException::class, 'Dapodik snapshot directory missing');
});
