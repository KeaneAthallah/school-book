<?php

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Product;
use App\Models\Publisher;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\RbacSeeder;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->seed(RbacSeeder::class);

    foreach ([['Z3', 'Zona 3'], ['Z4', 'Zona 4'], ['Z5', 'Zona 5']] as [$code, $name]) {
        Zona::factory()->create(['code' => $code, 'name' => $name, 'status' => 'active']);
    }
});

function importAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

/**
 * Write a workbook into the upload area and return an uploaded file pointing
 * at it. `File::create()` copies the source, which breaks `getRealPath()`
 * for binary workbooks, so the workbook is written straight to the target.
 *
 * @param  list<string>  $headers
 * @param  list<list<null|int|float|string>>  $rows
 */
function importWorkbook(string $name, array $headers, array $rows): File
{
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'master-import-'.uniqid();
    mkdir($directory.'/livewire-file/uploads', 0777, true);

    $path = $directory.'/livewire-file/uploads/'.$name;

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($headers as $index => $header) {
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $header);
    }

    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $columnIndex => $value) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 2), $value);
        }
    }

    (new Xlsx($spreadsheet))->save($path);

    return new File($name, fopen($path, 'rb') ?: null, $path);
}

function bookImportHeaders(): array
{
    return [
        'Kode Buku', 'Judul', 'ISBN', 'Penerbit', 'Kategori', 'Kelas', 'Mata Pelajaran',
        'Klasifikasi', 'Harga Jual Zona 3', 'Harga Jual Zona 4', 'Harga Jual Zona 5',
        'Harga Pokok', 'Lokasi Rak', 'Status',
    ];
}

it('provides a downloadable template for books', function () {
    $response = $this->actingAs(importAdmin())->get('/master/books/import/template');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
});

it('provides a downloadable template for barang lainnya', function () {
    $this->actingAs(importAdmin())
        ->get('/master/barang-lainnya/import/template')
        ->assertOk();
});

it('imports books from a workbook, creating publishers and categories on the fly', function () {
    $file = importWorkbook('buku.xlsx', bookImportHeaders(), [
        ['BK-IMP-1', 'Buku Impor Pertama', '978-1', 'Penerbit Baru', 'Umum', '1', 'Matematika', 'Reguler', '90000', '90000', '90000', '63000', 'A-1', 'Aktif'],
        ['BK-IMP-2', 'Buku Impor Kedua', null, 'Penerbit Baru', 'Agama', '2', 'PAI', 'Het', '80000', '81000', '82000', '56000', null, ''],
    ]);

    $this->actingAs(importAdmin())
        ->post('/master/books/import', ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('success')
        ->assertSessionMissing('import_errors');

    $first = Book::query()->whereHas('product', fn ($q) => $q->where('code', 'BK-IMP-1'))->firstOrFail();

    expect($first->classification)->toBe('Reguler');
    expect(Publisher::query()->where('name', 'Penerbit Baru')->exists())->toBeTrue();
    expect(BookCategory::query()->where('name', 'Umum')->exists())->toBeTrue();

    expect($first->product->prices()->with('zona')->get()
        ->mapWithKeys(fn ($price): array => [(string) $price->zona->code => (string) $price->price])
        ->all())->toBe([
            'Z3' => '90000.00',
            'Z4' => '90000.00',
            'Z5' => '90000.00',
        ]);

    // Empty status defaults to active.
    $second = Book::query()->whereHas('product', fn ($q) => $q->where('code', 'BK-IMP-2'))->firstOrFail();
    expect((string) $second->product->price)->toBe('80000.00');
    expect($second->product->status)->toBe('active');
});

it('rejects reguler books whose zone prices are incomplete or not equal', function () {
    $file = importWorkbook('buku.xlsx', bookImportHeaders(), [
        ['BK-BAD-1', 'Zona Kurang', null, 'Penerbit X', null, null, null, 'Reguler', '90000', null, null, null, null, 'Aktif'],
        ['BK-BAD-2', 'Zona Beda', null, 'Penerbit X', null, null, null, 'Reguler', '90000', '95000', '90000', null, null, 'Aktif'],
        ['BK-OK-1', 'Zona Sama', null, 'Penerbit X', null, null, null, 'Reguler', '90000', '90000', '90000', null, null, 'Aktif'],
    ]);

    $this->actingAs(importAdmin())
        ->post('/master/books/import', ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('error')
        ->assertSessionHas('import_errors');

    expect(Book::query()->whereHas('product', fn ($q) => $q->where('code', 'BK-BAD-1'))->exists())->toBeFalse();
    expect(Book::query()->whereHas('product', fn ($q) => $q->where('code', 'BK-BAD-2'))->exists())->toBeFalse();
    expect(Book::query()->whereHas('product', fn ($q) => $q->where('code', 'BK-OK-1'))->exists())->toBeTrue();
});

it('updates an existing book when the code is already present', function () {
    $headers = bookImportHeaders();

    $this->actingAs(importAdmin())
        ->post('/master/books/import', ['file' => importWorkbook('a.xlsx', $headers, [
            ['BK-UPD-1', 'Judul Lama', null, 'Penerbit X', null, null, null, 'Reguler', '90000', '90000', '90000', null, null, 'Aktif'],
        ])])
        ->assertRedirect();

    $this->actingAs(importAdmin())
        ->post('/master/books/import', ['file' => importWorkbook('b.xlsx', $headers, [
            ['BK-UPD-1', 'Judul Baru', null, 'Penerbit X', null, null, null, 'Reguler', '95000', '95000', '95000', null, null, 'Aktif'],
        ])])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Product::query()->where('code', 'BK-UPD-1')->count())->toBe(1);
    expect(Product::query()->where('code', 'BK-UPD-1')->value('title'))->toBe('Judul Baru');
});

it('parses an Indonesian thousand-separated price given as text', function () {
    $file = importWorkbook('ribuan.xlsx', bookImportHeaders(), [
        ['BK-TXT-1', 'Harga Teks', null, 'Penerbit X', null, null, null, 'Het', '1.250.000', null, null, null, null, 'Aktif'],
    ]);

    $this->actingAs(importAdmin())
        ->post('/master/books/import', ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect((string) Product::query()->where('code', 'BK-TXT-1')->value('price'))->toBe('1250000.00');
});

it('reports duplicate codes inside the same file', function () {
    $file = importWorkbook('dup.xlsx', bookImportHeaders(), [
        ['BK-DUP-1', 'Pertama', null, 'Penerbit X', null, null, null, 'Reguler', '90000', '90000', '90000', null, null, 'Aktif'],
        ['BK-DUP-1', 'Kedua', null, 'Penerbit X', null, null, null, 'Reguler', '90000', '90000', '90000', null, null, 'Aktif'],
    ]);

    $this->actingAs(importAdmin())
        ->post('/master/books/import', ['file' => $file])
        ->assertSessionHas('import_errors');
});

it('fails with a clear message when the header row is not the official template', function () {
    $file = importWorkbook('salah.xlsx', ['Foo', 'Bar'], [['1', '2']]);

    $this->actingAs(importAdmin())
        ->post('/master/books/import', ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('import_errors');

    expect(Book::query()->count())->toBe(0);
});

it('imports barang lainnya from a workbook', function () {
    $file = importWorkbook('barang.xlsx', [
        'Kode Barang', 'Nama Barang', 'Harga Jual', 'Harga Pokok', 'Lokasi Rak', 'Nama Toko', 'Satuan', 'Status',
    ], [
        ['BRG-IMP-1', 'Kipas Angin', '250000', '200000', 'C-1', 'Toko Contoh', 'Unit', 'Aktif'],
    ]);

    $this->actingAs(importAdmin())
        ->post('/master/barang-lainnya/import', ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('success');

    $product = Product::query()->where('code', 'BRG-IMP-1')->firstOrFail();

    expect($product->type)->toBe('goods');
    expect((string) $product->price)->toBe('250000.00');
    expect($product->goods->unit)->toBe('Unit');
});

it('requires the books.manage permission to import', function () {
    $user = User::query()->where('email', 'operator@sekolah.test')->firstOrFail();

    $this->actingAs($user)
        ->post('/master/books/import', ['file' => importWorkbook('x.xlsx', bookImportHeaders(), [])])
        ->assertForbidden();
});

it('validates the uploaded file type', function () {
    $file = UploadedFile::fake()->create('catatan.pdf', 20, 'application/pdf');

    $this->actingAs(importAdmin())
        ->post('/master/books/import', ['file' => $file])
        ->assertSessionHasErrors('file');
});
