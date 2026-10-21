<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Zona;
use Illuminate\Console\Command;

class FillRegulerBookZonePricesCommand extends Command
{
    /**
     * Zone codes every Reguler-classified book must carry a selling price for.
     *
     * @var array<int, string>
     */
    private const ZONE_CODES = ['Z3', 'Z4', 'Z5'];

    public $signature = 'books:fill-reguler-zone-prices
        {--dry-run : Show what would be done without writing anything.}';

    public $description = 'One-time backfill: mark every book as Reguler and set Harga Jual Zona 3/4/5 to the same value taken from the price it already has. Not scheduled.';

    public function handle(): int
    {
        /** @var array<string, int> $zonaIds zona code => id */
        $zonaIds = Zona::query()
            ->whereIn('code', self::ZONE_CODES)
            ->pluck('id', 'code')
            ->all();

        $missingZonas = array_diff(self::ZONE_CODES, array_keys($zonaIds));

        if ($missingZonas !== []) {
            $this->error('Zona belum ada di master data: '.implode(', ', $missingZonas).'. Jalankan `db:seed --class=MasterDataSeeder` dulu.');

            return self::FAILURE;
        }

        // Step 1: every book becomes Reguler, so the zone-price rule applies
        // to the whole catalog.
        $unmarked = Book::query()
            ->where(fn ($query) => $query->whereNull('classification')->orWhere('classification', '!=', 'Reguler'));

        $reclassified = $this->option('dry-run')
            ? (clone $unmarked)->count()
            : $unmarked->update(['classification' => 'Reguler']);

        // Step 2: make sure every one of them carries all three zone prices
        // at the same value.
        $scanned = 0;
        $filled = 0;
        $withoutSource = [];

        Book::query()
            ->where('classification', 'Reguler')
            ->with('product.prices')
            ->chunkById(200, function ($books) use ($zonaIds, &$scanned, &$filled, &$withoutSource): void {
                foreach ($books as $book) {
                    $product = $book->product;

                    if ($product === null) {
                        continue;
                    }

                    $scanned++;

                    /** @var array<int, string> $existing zona id => price */
                    $existing = $product->prices->mapWithKeys(
                        fn ($price): array => [(int) $price->zona_id => (string) $price->price]
                    )->all();

                    // Older books carry a single price on the product; the zone
                    // form mirrors it into Zona 3, so it is the best fallback.
                    $sourcePrice = $existing[$zonaIds['Z3']] ?? $product->price;

                    if ((float) $sourcePrice <= 0) {
                        $withoutSource[] = $product->code;

                        continue;
                    }

                    foreach (self::ZONE_CODES as $code) {
                        $current = $existing[$zonaIds[$code]] ?? null;

                        // All three zones must carry the same value, so any
                        // zone that differs from the source gets rewritten.
                        if ($current !== null && (float) $current === (float) $sourcePrice) {
                            continue;
                        }

                        if (! $this->option('dry-run')) {
                            $product->prices()->updateOrCreate(
                                ['zona_id' => $zonaIds[$code]],
                                ['price' => (string) $sourcePrice],
                            );
                        }

                        $filled++;
                    }
                }
            });

        $this->table(
            ['Metric', 'Value'],
            [
                ['Reguler books scanned', $scanned],
                ['Zone prices set (kept equal)', $this->option('dry-run') ? "$filled (dry-run, not written)" : (string) $filled],
                ['Skipped (no source price)', (string) count($withoutSource)],
            ]
        );

        if ($withoutSource !== []) {
            $preview = implode(', ', array_slice($withoutSource, 0, 20));
            $rest = count($withoutSource) - 20;

            $this->warn('These books have no usable price yet (price is null or 0) — fill them manually: '.$preview.($rest > 0 ? ", and $rest more." : '.'));
        }

        $this->info($this->option('dry-run') ? 'Dry run: nothing was written.' : 'Done.');

        return self::SUCCESS;
    }
}
