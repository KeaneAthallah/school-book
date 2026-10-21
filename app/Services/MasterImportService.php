<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Goods;
use App\Models\Publisher;
use App\Support\MasterDataRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Excel import with a downloadable template for the catalog master data
 * (buku and barang lainnya).
 *
 * Every row is validated and saved through the same MasterDataRegistry
 * rules and save closures the manual form uses, so an import can never
 * produce a record the form would have rejected.
 */
class MasterImportService
{
    /**
     * Template/import columns per resource, in workbook order. The label
     * doubles as the exact header text the uploaded file is matched on.
     *
     * @var array<string, list<array{name: string, label: string, type?: string, required?: bool, options?: list<string>}>>
     */
    private const COLUMNS = [
        'books' => [
            ['name' => 'code', 'label' => 'Kode Buku', 'required' => true],
            ['name' => 'title', 'label' => 'Judul', 'required' => true],
            ['name' => 'isbn', 'label' => 'ISBN'],
            ['name' => 'publisher_id', 'label' => 'Penerbit', 'type' => 'publisher', 'required' => true],
            ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'category'],
            ['name' => 'grade', 'label' => 'Kelas'],
            ['name' => 'subject', 'label' => 'Mata Pelajaran'],
            ['name' => 'classification', 'label' => 'Klasifikasi', 'type' => 'enum', 'options' => ['Het', 'Reguler', 'Depag', 'Banpem', 'Alcom']],
            ['name' => 'price_zona_3', 'label' => 'Harga Jual Zona 3', 'type' => 'price'],
            ['name' => 'price_zona_4', 'label' => 'Harga Jual Zona 4', 'type' => 'price'],
            ['name' => 'price_zona_5', 'label' => 'Harga Jual Zona 5', 'type' => 'price'],
            ['name' => 'cost_price', 'label' => 'Harga Pokok', 'type' => 'price'],
            ['name' => 'rack_location', 'label' => 'Lokasi Rak'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['Aktif', 'Nonaktif']],
        ],
        'barang-lainnya' => [
            ['name' => 'code', 'label' => 'Kode Barang', 'required' => true],
            ['name' => 'title', 'label' => 'Nama Barang', 'required' => true],
            ['name' => 'price', 'label' => 'Harga Jual', 'type' => 'price', 'required' => true],
            ['name' => 'cost_price', 'label' => 'Harga Pokok', 'type' => 'price'],
            ['name' => 'rack_location', 'label' => 'Lokasi Rak'],
            ['name' => 'store_name', 'label' => 'Nama Toko'],
            ['name' => 'unit', 'label' => 'Satuan'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'status', 'options' => ['Aktif', 'Nonaktif']],
        ],
    ];

    /**
     * One example row per resource, shown right under the headers so the
     * format is self-explanatory.
     *
     * @var array<string, list<string>>
     */
    private const SAMPLES = [
        'books' => ['BK-001', 'Contoh Judul Buku', '978-602-1234-56-7', 'Penerbit Contoh', 'Umum', '1', 'Matematika', 'Reguler', '90000', '90000', '90000', '63000', 'A-1', 'Aktif'],
        'barang-lainnya' => ['BRG-001', 'Contoh Barang', '50000', '35000', 'B-2', 'Toko Contoh', 'Pcs', 'Aktif'],
    ];

    /** @var list<string> */
    private const GUIDE_LINES = [
        'Cara pakai:',
        '1. Isi data pada sheet "Data". Jangan mengubah, menghapus, atau menggeser nama kolom di baris pertama.',
        '2. Baris kedua berisi contoh — boleh diganti atau dihapus.',
        '3. Unggah file kembali lewat halaman master data. Baris dengan kesalahan tidak diimpor dan dilaporkan; baris valid lainnya tetap diproses.',
        '',
        'Aturan kolom:',
        '- Kode harus unik. Jika kode sudah ada di sistem, data baris tersebut akan memperbarui data lama (bukan duplikat).',
        '- Penerbit dan Kategori diisi dengan nama. Nama yang belum terdaftar akan dibuat otomatis.',
        '- Harga diisi angka saja tanpa "Rp" (boleh pakai titik pemisah ribuan, contoh: 90.000).',
        '- Klasifikasi dan Status memakai pilihan dropdown — jangan mengetik manual.',
        '- Status kosong otomatis dianggap "Aktif".',
        '- Buku Reguler: Harga Jual Zona 3, 4 dan 5 wajib diisi semuanya dan nilainya harus sama.',
        '- Baris yang seluruh kolomnya kosong akan dilewati.',
    ];

    public function supports(string $resource): bool
    {
        return isset(self::COLUMNS[$resource]);
    }

    /**
     * Build the downloadable template workbook: a guide sheet plus a data
     * sheet with headers, an example row and dropdown validation.
     */
    public function template(string $resource): ?StreamedResponse
    {
        $columns = self::COLUMNS[$resource] ?? null;

        if ($columns === null) {
            return null;
        }

        $spreadsheet = new Spreadsheet;

        $this->buildGuideSheet($spreadsheet->getActiveSheet(), $resource);
        $this->buildDataSheet($spreadsheet->createSheet(), $resource, $columns);

        $fileName = 'template-'.($resource === 'books' ? 'buku' : 'barang').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Read an uploaded workbook and create/update catalog records.
     *
     * @return array{total: int, created: int, updated: int, errors: list<string>}
     */
    public function import(string $resource, UploadedFile $file): array
    {
        $columns = self::COLUMNS[$resource] ?? null;
        abort_if($columns === null, 404);

        $result = ['total' => 0, 'created' => 0, 'updated' => 0, 'errors' => []];

        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (Throwable $e) {
            $result['errors'][] = 'File tidak bisa dibaca — pastikan mengunggah file .xlsx/.xls hasil template resmi. ('.$e->getMessage().')';

            return $result;
        }

        /** @var list<list<null|bool|int|float|string>> $rows */
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        /** @var list<null|bool|int|float|string> $header */
        $header = array_shift($rows) ?? [];
        $map = $this->mapHeader($header, $columns);

        foreach (['code', 'title'] as $requiredKey) {
            if (! isset($map[$requiredKey])) {
                $result['errors'][] = 'Kolom "'.$this->label($resource, $requiredKey).'" tidak ditemukan — gunakan file template resmi tanpa mengubah nama kolom.';

                return $result;
            }
        }

        $definition = MasterDataRegistry::get($resource);
        $labels = $this->labels($resource);
        /** @var array<string, true> $seen */
        $seen = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // the header occupies row 1

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $result['total']++;

            $attributes = $this->parseRow($resource, $columns, $map, $row);
            $code = (string) ($attributes['code'] ?? '');

            if (isset($seen[$code])) {
                $result['errors'][] = "Baris {$line}: kode \"{$code}\" duplikat di dalam file.";

                continue;
            }

            $seen[$code] = true;

            $existing = $this->findByCode($resource, $code);

            $validator = Validator::make($attributes, $definition['rules']($existing), [], $labels);
            $errors = $validator->errors()->all();

            if ($resource === 'books') {
                array_push($errors, ...$this->regulerErrors($attributes));
            }

            if ($errors !== []) {
                $result['errors'][] = "Baris {$line}: ".implode('; ', $errors);

                continue;
            }

            try {
                DB::transaction(function () use ($definition, $attributes, $existing): void {
                    /** @var callable(array<string, mixed>, Model|null): Model $save */
                    $save = $definition['save'];
                    $save($attributes, $existing);
                });
            } catch (Throwable $e) {
                $result['errors'][] = "Baris {$line}: gagal disimpan. (".$e->getMessage().')';

                continue;
            }

            $existing === null ? $result['created']++ : $result['updated']++;
        }

        return $result;
    }

    private function buildGuideSheet(Worksheet $sheet, string $resource): void
    {
        $sheet->setTitle('Petunjuk');
        $sheet->getColumnDimension('A')->setWidth(110);

        $lines = array_merge(
            ['IMPORT '.mb_strtoupper($resource === 'books' ? 'BUKU' : 'BARANG LAINNYA'), ''],
            self::GUIDE_LINES,
        );

        foreach ($lines as $index => $line) {
            $sheet->setCellValue('A'.($index + 1), $line);
            $sheet->getStyle('A'.($index + 1))->getFont()->setBold($index === 0);
        }
    }

    /**
     * @param  list<array{name: string, label: string, type?: string, options?: list<string>}>  $columns
     */
    private function buildDataSheet(Worksheet $sheet, string $resource, array $columns): void
    {
        $sheet->setTitle('Data');
        $sheet->freezePane('A2');

        foreach ($columns as $offset => $column) {
            $row = $offset + 1;
            $letter = Coordinate::stringFromColumnIndex($row);

            $sheet->setCellValue($letter.'1', $column['label']);
            $sheet->setCellValue($letter.'2', self::SAMPLES[$resource][$offset] ?? null);
            $sheet->getColumnDimension($letter)->setWidth(max(12, (int) (mb_strlen($column['label']) * 1.3)));

            if (($column['type'] ?? null) === 'price') {
                $sheet->getStyle($letter.'3:'.$letter.'1000')->getNumberFormat()->setFormatCode('#,##0');
            }

            if (isset($column['options'])) {
                $validation = new DataValidation;
                $validation->setType(DataValidation::TYPE_LIST)
                    ->setAllowBlank(true)
                    ->setShowInputMessage(true)
                    ->setShowErrorMessage(true)
                    // PhpSpreadsheet quirk: false is what renders the dropdown.
                    ->setShowDropDown(false)
                    ->setPromptTitle('Pilih dari daftar')
                    ->setPrompt('Jangan ketik manual, pilih salah satu nilai.')
                    ->setErrorTitle('Nilai tidak valid')
                    ->setError('Silakan pilih nilai dari daftar dropdown.')
                    ->setFormula1('"'.implode(',', $column['options']).'"');

                $sheet->setDataValidation($letter.'3:'.$letter.'1000', $validation);
            }
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));

        $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_WHITE));
        $sheet->getStyle('A1:'.$lastColumn.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2A44');
        $sheet->getStyle('A2:'.$lastColumn.'2')->getFont()->setItalic(true)->setColor(new Color('FF808080'));
    }

    /**
     * Match the workbook header row against the expected labels.
     *
     * @param  list<null|bool|int|float|string>  $header
     * @param  list<array{name: string, label: string}>  $columns
     * @return array<string, int> field name => column index
     */
    private function mapHeader(array $header, array $columns): array
    {
        $map = [];

        foreach ($columns as $column) {
            foreach ($header as $index => $cell) {
                if (mb_strtolower(trim((string) $cell)) === mb_strtolower($column['label'])) {
                    $map[$column['name']] = $index;

                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Convert one workbook row into the attribute shape the registry rules
     * and save closures expect.
     *
     * @param  list<array{name: string, label: string, type?: string}>  $columns
     * @param  array<string, int>  $map
     * @param  list<null|bool|int|float|string>  $row
     * @return array<string, mixed>
     */
    private function parseRow(string $resource, array $columns, array $map, array $row): array
    {
        $attributes = [];

        foreach ($columns as $column) {
            if (! isset($map[$column['name']])) {
                continue;
            }

            $value = $row[$map[$column['name']]] ?? null;

            $attributes[$column['name']] = match ($column['type'] ?? 'text') {
                'price' => $this->parsePrice($value),
                'publisher' => $this->resolvePublisher($value),
                'category' => $this->resolveCategory($value),
                'status' => $this->parseStatus($value),
                default => $this->text($value),
            };
        }

        return $attributes;
    }

    /**
     * Business rule for Reguler books: all three zone prices are mandatory
     * and must carry the same value.
     *
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    private function regulerErrors(array $attributes): array
    {
        if (($attributes['classification'] ?? null) !== 'Reguler') {
            return [];
        }

        $zones = [
            'Harga Jual Zona 3' => $attributes['price_zona_3'] ?? null,
            'Harga Jual Zona 4' => $attributes['price_zona_4'] ?? null,
            'Harga Jual Zona 5' => $attributes['price_zona_5'] ?? null,
        ];

        if (array_filter($zones, fn ($price): bool => $price !== null) !== $zones) {
            return ['untuk buku Reguler, Harga Jual Zona 3, 4 dan 5 wajib diisi semuanya'];
        }

        if (count(array_unique(array_map(fn ($price): float => (float) $price, $zones))) > 1) {
            return ['untuk buku Reguler, Harga Jual Zona 3, 4 dan 5 harus berisi harga yang sama'];
        }

        return [];
    }

    private function findByCode(string $resource, string $code): ?Model
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $resource === 'books' ? Book::class : Goods::class;

        return $modelClass::query()
            ->whereHas('product', fn ($query) => $query->where('code', $code))
            ->first();
    }

    private function resolvePublisher(mixed $value): ?int
    {
        $name = $this->text($value);

        if ($name === null) {
            return null;
        }

        return Publisher::query()->firstOrCreate(['name' => $name])->getKey();
    }

    private function resolveCategory(mixed $value): ?int
    {
        $name = $this->text($value);

        if ($name === null) {
            return null;
        }

        return BookCategory::query()->firstOrCreate(
            ['name' => $name],
            ['slug' => Str::slug($name).'-'.substr(md5($name.microtime(true)), 0, 4)],
        )->getKey();
    }

    private function parseStatus(mixed $value): string
    {
        $text = mb_strtolower($this->text($value) ?? '');

        return match ($text) {
            'nonaktif', 'inactive' => 'inactive',
            default => 'active',
        };
    }

    /** Parse Indonesian-formatted prices ("95.000", "1.250.000,50") into a decimal string. */
    private function parsePrice(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        $raw = str_replace(' ', '', trim((string) $value));

        if ($raw === '') {
            return null;
        }

        // Indonesian formatting uses "." as the thousand separator and ","
        // as the decimal mark. A lone dot with exactly three digits after it
        // is therefore a thousand separator ("80.000"), not a decimal point.
        if (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', str_replace('.', '', $raw));
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $raw) === 1) {
            $raw = str_replace('.', '', $raw);
        }

        if (! is_numeric($raw)) {
            return null;
        }

        return number_format((float) $raw, 2, '.', '');
    }

    private function text(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * @param  list<null|bool|int|float|string>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($this->text($cell) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string> attribute name => Indonesian label
     */
    private function labels(string $resource): array
    {
        $labels = [];

        foreach (self::COLUMNS[$resource] ?? [] as $column) {
            $labels[$column['name']] = $column['label'];
        }

        return $labels;
    }

    private function label(string $resource, string $name): string
    {
        foreach (self::COLUMNS[$resource] ?? [] as $column) {
            if ($column['name'] === $name) {
                return $column['label'];
            }
        }

        return $name;
    }
}
