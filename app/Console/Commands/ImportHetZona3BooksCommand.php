<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Publisher;
use App\Models\Zona;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

class ImportHetZona3BooksCommand extends Command
{
    public $signature = 'books:import-het-zona3
        {file? : Path to the XLSX file, defaults to docs/HARGA BARU BUKU KURIKULUM MERDEKA 2026 - ZONA 3.xlsx}
        {--dry-run : Show what would be done without writing}';

    public $description = 'Import HET books from Zona 3 price list (Mas Media Books)';

    /** @var array<string, int> zona code => id */
    private array $zonas = [];

    private ?Publisher $publisher = null;

    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $file = $this->argument('file') ?: 'docs/HARGA BARU BUKU KURIKULUM MERDEKA 2026 - ZONA 3.xlsx';
        $path = base_path($file);

        if (! file_exists($path)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $this->loadLookups();

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);

        try {
            $spreadsheet = $reader->load($path);
        } catch (ReaderException $e) {
            $this->error("Cannot read workbook: {$e->getMessage()}");

            return self::FAILURE;
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, false, false);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use (&$created, &$updated, &$skipped, $rows) {
            for ($i = 5; $i < count($rows); $i++) { // Data starts around row 6
                $row = $rows[$i];
                $seq = $this->text($row[0] ?? null); // Col A
                $grade = $this->text($row[1] ?? null); // Col B
                $title = $this->text($row[2] ?? null); // Col C
                $type = $this->text($row[3] ?? null); // Col D - SISWA/BUKU GURU

                // Skip empty rows and summary rows
                if ($title === null) {
                    $skipped++;

                    continue;
                }
                $titleLower = strtolower($title);
                if (str_contains($titleLower, 'total') || str_contains($titleLower, 'ongkir') || str_contains($titleLower, 'ppn')) {
                    $skipped++;

                    continue;
                }
                // Skip if it's not a numeric sequence in expected range
                if ($seq === null || ! is_numeric($seq)) {
                    // might be a summary row
                    if (! preg_match('/^[0-9]+$/', $seq ?? '')) {
                        $skipped++;

                        continue;
                    }
                }

                // Generate a code if missing
                $bookCode = $this->generateCode($title, $grade, $i);

                $zona1 = $this->parsePrice($row[4] ?? null); // Col E - ZONA 1 (cost)
                $zona3 = $this->parsePrice($row[6] ?? null); // Col G - ZONA 3
                $zona4 = $this->parsePrice($row[7] ?? null); // Col H - ZONA 4
                $zona5a = $this->parsePrice($row[8] ?? null); // Col I - ZONA 5A

                if ($this->option('dry-run')) {
                    $this->line("Would import: {$bookCode} - {$title}");
                    $created++;

                    continue;
                }

                // Find or create product
                $product = Product::firstOrNew(['code' => $bookCode]);
                $isNew = ! $product->exists;
                $product->type = 'book';
                $product->title = Str::limit($title, 500);
                $product->price = $zona3 ?: ($zona1 ?: '0');
                $product->cost_price = $zona1 ?: '0';
                $product->status = $product->status ?? 'active';
                $product->save();

                // Find or create book
                $book = Book::firstOrNew(['product_id' => $product->id]);
                $book->product_id = $product->id;
                $book->publisher_id = $this->publisher?->id;
                $book->classification = 'HET';
                $book->grade = $grade;
                $book->subject = null;
                $book->save();

                // Set zone prices
                if (isset($this->zonas['Z3']) && $zona3) {
                    ProductPrice::updateOrCreate(
                        ['product_id' => $product->id, 'zona_id' => $this->zonas['Z3']],
                        ['price' => $zona3]
                    );
                }
                if (isset($this->zonas['Z4']) && $zona4) {
                    ProductPrice::updateOrCreate(
                        ['product_id' => $product->id, 'zona_id' => $this->zonas['Z4']],
                        ['price' => $zona4]
                    );
                }
                if (isset($this->zonas['Z5']) && $zona5a) {
                    ProductPrice::updateOrCreate(
                        ['product_id' => $product->id, 'zona_id' => $this->zonas['Z5']],
                        ['price' => $zona5a]
                    );
                }

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        });

        $this->info("Imported HET Zona 3 books: created={$created}, updated={$updated}, skipped={$skipped}");

        return self::SUCCESS;
    }

    private function loadLookups(): void
    {
        $this->publisher = Publisher::firstOrCreate(['name' => 'Mas Media Books']);
        foreach (Zona::all() as $z) {
            $this->zonas[$z->code] = $z->id;
        }
    }

    private function generateCode(string $title, ?string $grade, int $row): string
    {
        $slug = Str::slug(substr($title, 0, 30));

        return 'HET-'.strtoupper(substr($slug, 0, 20)).'-'.$row;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function parsePrice(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }
        $raw = str_replace([' ', '.', ','], ['', '', '.'], trim((string) $value));
        if ($raw === '' || ! is_numeric($raw)) {
            return null;
        }

        return number_format((float) $raw, 2, '.', '');
    }
}
