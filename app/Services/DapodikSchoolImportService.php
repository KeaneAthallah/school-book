<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Imports the one-time Dapodik school snapshots captured by scripts/dapodik/sulawesi-capture.cjs
 * into the existing `schools` master rows (no schema changes).
 *
 * Dapodik is behind a WAF that blocks plain HTTP, so the snapshot JSON is fetched once
 * via a browser and then imported from disk. This is a manual, idempotent one-time
 * import — no scheduler — keyed on NPSN (unique). Reruns never duplicate or mutate
 * manually maintained rows.
 */
class DapodikSchoolImportService
{
    /**
     * Disk holding the Dapodik snapshots. Its root is storage/app/data/dapodik
     * (see config/filesystems.php), the same place scripts/dapodik/sulawesi-capture.cjs
     * writes to — not the storage/app/private root of the default "local" disk.
     */
    public const SNAPSHOT_DISK = 'dapodik';

    /** Directory on the snapshot disk holding per-kabupaten snapshot JSON files (or a single snapshot). */
    public const SNAPSHOT_DIR = 'sulawesi';

    /**
     * Import every snapshot file found in the snapshot directory.
     *
     * @param  bool  $updateExisting  When true, existing schools (matched by npsn) get
     *                                their mapped geo/name fields refreshed. Defaults
     *                                to false so manual sales rows are never touched.
     * @return array{created:int, updated:int, skipped_existing:int, skipped_invalid:int, files:int, total:int}
     */
    public function import(bool $updateExisting = false): array
    {
        $dir = self::SNAPSHOT_DIR;
        $disk = Storage::disk(self::SNAPSHOT_DISK);

        if (! $disk->exists($dir)) {
            throw new RuntimeException(sprintf('Dapodik snapshot directory missing: %s', $disk->path($dir)));
        }

        $files = collect($disk->files($dir))
            ->filter(fn (string $p) => str_ends_with($p, '.json'))
            ->sort();

        $created = 0;
        $updated = 0;
        $skippedExisting = 0;
        $skippedInvalid = 0;
        $total = 0;

        DB::transaction(function () use ($disk, $files, $updateExisting, &$created, &$updated, &$skippedExisting, &$skippedInvalid, &$total) {
            foreach ($files as $file) {
                $snapshot = $this->readSnapshot($disk->path($file));
                $count = $this->importSnapshot($snapshot, $updateExisting, $created, $updated, $skippedExisting, $skippedInvalid);
                $total += $count;
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped_existing' => $skippedExisting,
            'skipped_invalid' => $skippedInvalid,
            'files' => $files->count(),
            'total' => $total,
        ];
    }

    /**
     * Import one snapshot (the single-file path used by import()).
     *
     * @param  array{captured_at?: string, source?: string, schools: array<int, array<string,mixed>>}  $snapshot
     */
    private function importSnapshot(array $snapshot, bool $updateExisting, int &$created, int &$updated, int &$skippedExisting, int &$skippedInvalid): int
    {
        $total = 0;

        foreach ($snapshot['schools'] as $row) {
            $total++;

            $name = trim((string) ($row['nama'] ?? ''));
            if ($name === '') {
                $skippedInvalid++;

                continue;
            }

            $npsn = $this->cleanNpsn($row['npsn'] ?? null);
            $attributes = $this->toFillable($row, $name);

            $existing = $npsn !== null
                ? School::where('npsn', $npsn)->first()
                : School::whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->where('district', $this->cleanDistrict($row['kecamatan'] ?? ''))
                    ->first();

            if ($existing instanceof School) {
                if ($updateExisting) {
                    // Never overwrite status/sales contact fields owned by the sales side;
                    // only refresh the Dapodik-sourced geo data.
                    $existing->update($attributes);
                    $updated++;
                } else {
                    $skippedExisting++;
                }
            } else {
                School::create($attributes);
                $created++;
            }
        }

        return $total;
    }

    /**
     * @return array{schools: array<int, array<string,mixed>>, semester?: string, captured_at?: string}
     */
    private function readSnapshot(string $path): array
    {
        $contents = (string) file_get_contents($path);
        $snapshot = json_decode($contents, true);

        if (! is_array($snapshot) || ! isset($snapshot['schools']) || ! is_array($snapshot['schools'])) {
            throw new RuntimeException(sprintf('Snapshot Dapodik tidak valid (butuh key "schools"): %s', $path));
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function toFillable(array $row, string $name): array
    {
        $desa = trim((string) ($row['desa_kelurahan'] ?? ''));
        $jalan = trim((string) ($row['alamat_jalan'] ?? ''));
        $address = trim(implode(', ', array_filter([$jalan, $desa])));

        return [
            'npsn' => $this->cleanNpsn($row['npsn'] ?? null),
            'name' => $name,
            'address' => $address !== '' ? $address : null,
            'district' => $this->cleanDistrict($row['kecamatan'] ?? ''),
            'regency' => $this->cleanRegency($row['kabupaten'] ?? ''),
            'province' => $this->cleanProvince($row['provinsi'] ?? ''),
            'postal_code' => $this->cleanPostal($row['kode_pos'] ?? null),
            // school-book fields with no Dapodik source are left unset (status default 'active',
            // sales_user_id / contact fields untouched for new rows).
        ];
    }

    private function cleanNpsn(mixed $value): ?string
    {
        $v = trim((string) $value);

        return $v !== '' && $v !== '0' ? $v : null;
    }

    private function cleanDistrict(mixed $value): string
    {
        return trim(preg_replace('/^Kec\.\s*/i', '', (string) $value));
    }

    private function cleanRegency(mixed $value): string
    {
        return trim(preg_replace('/^(Kab\.|Kota)\s*/i', '', (string) $value));
    }

    private function cleanProvince(mixed $value): string
    {
        return trim(preg_replace('/^Prov\.\s*/i', '', (string) $value));
    }

    private function cleanPostal(mixed $value): ?string
    {
        $v = trim((string) $value);

        return $v !== '' ? $v : null;
    }
}
