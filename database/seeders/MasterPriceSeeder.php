<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Product;
use App\Models\Publisher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Seed the book catalog from the yearly price masters in docs/.
 *
 * Each workbook holds one sheet per book category. Every sheet is a price
 * table ("Kode Buku", "Judul Buku", "Kelas", "Harga 2027") preceded by
 * school form rows, and the header row sits at a different position in each
 * sheet, so it is located dynamically. The SD workbook additionally carries
 * a "Master" sheet with the full catalog, including a "Kategori" column.
 *
 * The seeder is idempotent: rows are keyed on the book code, so it can run
 * on every seed without duplicating products.
 */
class MasterPriceSeeder extends Seeder
{
    /** Price masters live in the project's docs directory. */
    private const SOURCE_DIR = 'docs';

    /** Every catalog links back to the publisher's katalog URL. */
    private const PUBLISHER_NAME = 'Mas Media Books';

    private const CHUNK_SIZE = 500;

    /** @var array<string, true> book codes already collected; first occurrence wins */
    private array $seen = [];

    /** @var list<array{code: string, title: string, price: string, cost_price: string, grade: string|null, category: string}> */
    private array $rows = [];

    /** @var array<string, int> category slug => id */
    private array $categories = [];

    public function run(): void
    {
        ini_set('memory_limit', '512M');

        $files = glob(base_path(self::SOURCE_DIR.'/*.xlsx')) ?: [];

        foreach ($files as $file) {
            $this->importWorkbook((string) $file);
        }

        $this->flush(count($files));
    }

    private function importWorkbook(string $file): void
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);

        try {
            $spreadsheet = $reader->load($file);
        } catch (ReaderException $e) {
            $this->command->warn("Skipping unreadable workbook {$file}: {$e->getMessage()}");

            return;
        }

        $level = $this->levelFromFilename($file);

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $this->importSheet($sheet, $level);
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    private function importSheet(Worksheet $sheet, string $level): void
    {
        $sheetName = trim($sheet->getTitle());
        /** @var list<list<null|bool|int|float|string>> $rows */
        $rows = $sheet->toArray(null, false, false);

        $header = $this->findHeader($rows);
        if ($header === null) {
            return;
        }

        [$headerIndex, $map] = $header;

        $defaultCategory = mb_strtolower($sheetName) === 'master'
            ? "Buku {$level}"
            : $sheetName;

        for ($i = $headerIndex + 1, $count = count($rows); $i < $count; $i++) {
            $row = $rows[$i];
            $code = $this->text($row[$map['code']] ?? null);
            $title = $this->text($row[$map['title']] ?? null);
            $price = $this->parsePrice($row[$map['price']] ?? null);

            if ($code === null || $title === null || $price === null || isset($this->seen[$code])) {
                continue;
            }

            $this->seen[$code] = true;
            $this->rows[] = [
                'code' => $code,
                'title' => $title,
                'price' => $price,
                'cost_price' => number_format((float) $price * 0.7, 2, '.', ''),
                'grade' => $this->text($row[$map['grade']] ?? null),
                'category' => $this->text($row[$map['category']] ?? null) ?? $defaultCategory,
            ];
        }
    }

    /**
     * Locate the price table header row and map its columns.
     *
     * @param  list<list<null|bool|int|float|string>>  $rows
     * @return array{0: int, 1: array{code: int, title: int, price: int, grade: int|null, category: int|null}}|null
     */
    private function findHeader(array $rows): ?array
    {
        foreach ($rows as $index => $row) {
            /** @var list<string> $cells */
            $cells = array_map(
                static fn (mixed $value): string => mb_strtolower(trim((string) $value)),
                $row,
            );

            if (! self::hasAny($cells, ['kode buku', 'kode']) || ! self::hasAny($cells, ['judul buku', 'judul'])) {
                continue;
            }

            $map = [
                'code' => self::column($cells, ['kode buku', 'kode']),
                'title' => self::column($cells, ['judul buku', 'judul']),
                'price' => self::column($cells, ['harga 2027', 'harga']),
                'grade' => self::column($cells, ['kelas']),
                'category' => self::column($cells, ['kategori']),
            ];

            if ($map['code'] === null || $map['title'] === null || $map['price'] === null) {
                continue;
            }

            return [$index, $map];
        }

        return null;
    }

    /**
     * @param  list<string>  $cells
     * @param  list<string>  $needles
     */
    private static function hasAny(array $cells, array $needles): bool
    {
        foreach ($cells as $cell) {
            if (in_array($cell, $needles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $cells
     * @param  list<string>  $needles
     */
    private static function column(array $cells, array $needles): ?int
    {
        foreach ($cells as $index => $cell) {
            if (in_array($cell, $needles, true)) {
                return $index;
            }
        }

        return null;
    }

    /** Derive the education level from the workbook filename. */
    private function levelFromFilename(string $file): string
    {
        $name = mb_strtolower(basename($file));

        return match (true) {
            str_contains($name, 'paud') || str_contains($name, 'tk') => 'PAUD/TK',
            str_contains($name, 'smp') || str_contains($name, 'mts') => 'SMP/MTS',
            str_contains($name, 'sma') => 'SMA',
            default => 'SD',
        };
    }

    private function text(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /** Parse Indonesian-formatted prices ("95.000", "1.250.000,50") into a decimal string. */
    private function parsePrice(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }

        $raw = str_replace(' ', '', trim((string) $value));
        if ($raw === '') {
            return null;
        }

        // Dots are thousand separators and the comma is the decimal mark.
        $raw = str_replace(',', '.', str_replace('.', '', $raw));

        if (! is_numeric($raw)) {
            return null;
        }

        return number_format((float) $raw, 2, '.', '');
    }

    private function flush(int $fileCount): void
    {
        if ($this->rows === []) {
            $this->command->warn('No book rows found in the price masters.');

            return;
        }

        $publisherId = Publisher::query()
            ->firstOrCreate(['name' => self::PUBLISHER_NAME])
            ->id;

        foreach (array_chunk($this->rows, self::CHUNK_SIZE) as $chunk) {
            /** @var list<array{code: string, title: string, price: string, cost_price: string, grade: string|null, category: string}> $chunk */
            Product::upsert(
                array_map(static function (array $row): array {
                    return [
                        'type' => 'book',
                        'code' => $row['code'],
                        'title' => $row['title'],
                        'price' => $row['price'],
                        'cost_price' => $row['cost_price'],
                        'rack_location' => null,
                        'status' => 'active',
                    ];
                }, $chunk),
                ['code'],
                ['type', 'title', 'price', 'cost_price', 'status'],
            );

            $ids = Product::query()
                ->whereIn('code', array_column($chunk, 'code'))
                ->pluck('id', 'code');

            $books = [];
            foreach ($chunk as $row) {
                $books[] = [
                    'product_id' => $ids[$row['code']],
                    'isbn' => null,
                    'publisher_id' => $publisherId,
                    'category_id' => $this->categoryId($row['category']),
                    'grade' => $row['grade'],
                    'subject' => null,
                ];
            }

            Book::upsert($books, ['product_id'], ['isbn', 'publisher_id', 'category_id', 'grade', 'subject']);
        }

        $this->command->info(sprintf('Seeded %d books from %d workbook(s).', count($this->rows), $fileCount));
    }

    private function categoryId(string $name): int
    {
        $slug = Str::slug($name);

        if (! isset($this->categories[$slug])) {
            $this->categories[$slug] = BookCategory::query()
                ->updateOrCreate(['slug' => $slug], ['name' => $name])
                ->id;
        }

        return $this->categories[$slug];
    }
}
