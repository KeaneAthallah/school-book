<?php

namespace App\Services;

use App\Exceptions\IntegrationNotConfiguredException;
use App\Integrations\IntegrationManager;
use App\Models\Book;
use App\Models\IntegrationLog;
use App\Models\OrderSource;
use App\Models\School;
use App\Models\User;
use App\Support\IntegrationSyncResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IntegrationService
{
    public function __construct(
        private readonly IntegrationManager $manager,
        private readonly OrderService $orders,
    ) {}

    /**
     * Synchronize external orders for a source.
     *
     * The adapter contract is intentionally strict: when a source has no
     * verified API implementation the run is recorded as skipped instead of
     * fabricating orders.
     */
    public function sync(OrderSource $source, User $user): IntegrationSyncResult
    {
        try {
            $payloads = $this->manager->for($source)->fetch($source);
        } catch (IntegrationNotConfiguredException $exception) {
            $this->log($source, 'skipped', $exception->getMessage());

            return new IntegrationSyncResult(0, 0, $exception->getMessage());
        }

        $created = 0;
        $skipped = 0;

        foreach ($payloads as $payload) {
            if ($this->import($source, $payload, $user)) {
                $created++;
            } else {
                $skipped++;
            }
        }

        $message = "Sinkronisasi selesai: {$created} pesanan diimpor, {$skipped} dilewati.";
        $this->log($source, 'success', $message);

        return new IntegrationSyncResult($created, $skipped);
    }

    /**
     * Import a single normalized external order. Returns false when the
     * payload cannot be matched or is a duplicate.
     *
     * @param  array<string, mixed>  $payload
     */
    public function import(OrderSource $source, array $payload, User $user): bool
    {
        $reference = (string) ($payload['external_reference'] ?? '');

        if ($reference === '') {
            return false;
        }

        $school = School::query()->where('npsn', (string) ($payload['school_npsn'] ?? ''))->first();

        if ($school === null) {
            $this->log($source, 'skipped', "Sekolah dengan NPSN {$payload['school_npsn']} tidak ditemukan.", $reference);

            return false;
        }

        $items = [];

        foreach ((array) ($payload['items'] ?? []) as $line) {
            $book = Book::query()->where('code', (string) ($line['code'] ?? ''))->first();

            if ($book === null) {
                $this->log($source, 'skipped', "Buku dengan kode {$line['code']} tidak ditemukan.", $reference);

                return false;
            }

            $items[] = [
                'book_id' => $book->getKey(),
                'quantity' => (int) ($line['quantity'] ?? 0),
                'unit_price' => (string) ($line['unit_price'] ?? $book->price),
            ];
        }

        if ($items === []) {
            return false;
        }

        try {
            DB::transaction(function () use ($source, $payload, $reference, $school, $items, $user): void {
                $order = $this->orders->create([
                    'order_date' => (string) ($payload['order_date'] ?? now()->toDateString()),
                    'school_id' => $school->getKey(),
                    'order_source_id' => $source->getKey(),
                    'external_reference' => $reference,
                    'external_source' => $source->code,
                    'source_payload' => $payload,
                    'items' => $items,
                ], $user);

                $this->orders->confirm($order, $user);
            });
        } catch (ValidationException $exception) {
            $this->log($source, 'skipped', $exception->getMessage(), $reference);

            return false;
        }

        $this->log($source, 'success', 'Pesanan eksternal diimpor.', $reference);

        return true;
    }

    private function log(OrderSource $source, string $status, string $message, ?string $reference = null): void
    {
        IntegrationLog::query()->create([
            'source' => $source->code,
            'direction' => 'inbound',
            'reference' => $reference,
            'status' => $status,
            'message' => $message,
            'payload' => null,
        ]);
    }
}
