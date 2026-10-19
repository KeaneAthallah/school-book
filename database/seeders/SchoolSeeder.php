<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed the school master from the Dapodik capture dump (db_school.sql).
 *
 * The dump at the project root is a mysqldump of the capture database. Only
 * the `schools` INSERT statements are parsed (a small tuple tokenizer handles
 * mysqldump's backslash escaping); every other table in the file is ignored.
 *
 * The seeder is idempotent: rows are keyed on the NPSN, so it can run on
 * every seed without duplicating schools. When the dump is missing the
 * seeder warns and continues, so `db:seed` still works everywhere.
 */
class SchoolSeeder extends Seeder
{
    /** Dump file, relative to the project root. */
    private const SOURCE_FILE = 'db_school.sql';

    /** Column count of the dumped schools table (id .. updated_at). */
    private const COLUMN_COUNT = 17;

    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        $path = base_path(self::SOURCE_FILE);

        if (! is_readable($path)) {
            $this->command->warn('School dump '.self::SOURCE_FILE.' not found - skipping real school seed.');

            return;
        }

        $rows = $this->parseSchools(file_get_contents($path) ?: '');

        if ($rows === []) {
            $this->command->warn('No school rows found in '.self::SOURCE_FILE.'.');

            return;
        }

        // The dump stores raw user ids; resolve the sales account by email so
        // the seed stays valid on a fresh database.
        $salesUserId = User::query()->where('email', 'sales@sekolah.test')->value('id');

        $now = now();

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            /** @var list<array<string, mixed>> $records */
            $records = array_map(function (array $row) use ($salesUserId, $now): array {
                return [
                    'npsn' => $row['npsn'],
                    'name' => $row['name'],
                    'address' => $row['address'],
                    'district' => $row['district'],
                    'regency' => $row['regency'],
                    'province' => $row['province'],
                    'postal_code' => $row['postal_code'],
                    'contact_person' => $row['contact_person'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'principal_name' => $row['principal_name'],
                    'status' => $row['status'],
                    'sales_user_id' => $row['sales_user_id'] !== null ? $salesUserId : null,
                    'zona_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $chunk);

            School::query()->upsert($records, ['npsn'], [
                'name', 'address', 'district', 'regency', 'province', 'postal_code',
                'contact_person', 'phone', 'email', 'principal_name', 'status',
                'sales_user_id', 'updated_at',
            ]);
        }

        $this->command->info(sprintf('Seeded %d schools from %s.', count($rows), self::SOURCE_FILE));
    }

    /**
     * Parse every schools row out of the mysqldump.
     *
     * @return list<array{npsn: string, name: string, address: string|null, district: string|null, regency: string|null, province: string|null, postal_code: string|null, contact_person: string|null, phone: string|null, email: string|null, principal_name: string|null, status: string, sales_user_id: int|null}>
     */
    private function parseSchools(string $sql): array
    {
        $rows = [];
        $offset = 0;
        $prefix = 'INSERT INTO `schools` VALUES ';

        while (($start = strpos($sql, $prefix, $offset)) !== false) {
            foreach ($this->tuples(substr($sql, $start + strlen($prefix))) as $tuple) {
                if (count($tuple) < self::COLUMN_COUNT) {
                    continue;
                }

                // NPSN and name are required for an upsertable, presentable row.
                $npsn = $this->text($tuple[1] ?? null);
                $name = $this->text($tuple[2] ?? null);

                if ($npsn === null || $name === null) {
                    continue;
                }

                $rows[] = $this->schoolFromTuple($tuple, $npsn, $name);
            }

            $offset = $start + strlen($prefix);
        }

        return $rows;
    }

    /**
     * Tokenize the tuple list of one INSERT statement.
     *
     * Stops at the statement's closing semicolon. Values may be single-quoted
     * strings (with mysqldump backslash escapes), NULL, or bare numbers.
     *
     * @return list<list<null|float|int|string>>
     */
    private function tuples(string $values): array
    {
        $tuples = [];
        $tuple = [];
        $length = strlen($values);
        $i = 0;

        while ($i < $length) {
            $char = $values[$i];

            if ($char === '(') {
                $tuple = [];
                $i++;
            } elseif ($char === ')') {
                $tuples[] = $tuple;
                $i++;
            } elseif ($char === ';' || $char === "\n") {
                break;
            } elseif ($char === ',') {
                $i++;
            } elseif ($char === "'") {
                [$value, $i] = $this->readString($values, $i);
                $tuple[] = $value;
            } elseif (substr($values, $i, 4) === 'NULL' && ! ctype_alnum($values[$i + 4] ?? '')) {
                $tuple[] = null;
                $i += 4;
            } elseif ($char === '-' || ctype_digit($char)) {
                $number = $char;
                $i++;
                while ($i < $length && (ctype_digit($values[$i]) || $values[$i] === '.')) {
                    $number .= $values[$i];
                    $i++;
                }
                $tuple[] = str_contains($number, '.') ? (float) $number : (int) $number;
            } else {
                $i++;
            }
        }

        return $tuples;
    }

    /**
     * Read one single-quoted mysqldump string starting at the opening quote.
     *
     * @return array{0: string, 1: int} the decoded value and the offset after the closing quote
     */
    private function readString(string $sql, int $i): array
    {
        $length = strlen($sql);
        $value = '';
        $i++;

        while ($i < $length) {
            $char = $sql[$i];

            if ($char === '\\' && $i + 1 < $length) {
                $value .= match ($sql[$i + 1]) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    '0' => "\0",
                    'Z' => "\x1A",
                    default => $sql[$i + 1],
                };
                $i += 2;

                continue;
            }

            if ($char === "'") {
                return [$value, $i + 1];
            }

            $value .= $char;
            $i++;
        }

        return [$value, $i];
    }

    /**
     * Map one dumped tuple (id .. updated_at) onto the app's school columns.
     *
     * @param  list<null|float|int|string>  $tuple
     * @return array{npsn: string, name: string, address: string|null, district: string|null, regency: string|null, province: string|null, postal_code: string|null, contact_person: string|null, phone: string|null, email: string|null, principal_name: string|null, status: string, sales_user_id: int|null}
     */
    private function schoolFromTuple(array $tuple, string $npsn, string $name): array
    {
        return [
            'npsn' => $npsn,
            'name' => $name,
            'address' => $this->text($tuple[3] ?? null),
            'district' => $this->text($tuple[4] ?? null),
            'regency' => $this->text($tuple[5] ?? null),
            'province' => $this->text($tuple[6] ?? null),
            'postal_code' => $this->text($tuple[7] ?? null),
            'contact_person' => $this->text($tuple[8] ?? null),
            'phone' => $this->text($tuple[9] ?? null),
            'email' => $this->text($tuple[10] ?? null),
            'principal_name' => $this->text($tuple[11] ?? null),
            'status' => $this->text($tuple[12] ?? null) ?? 'active',
            'sales_user_id' => isset($tuple[13]) ? (int) $tuple[13] : null,
        ];
    }

    private function text(mixed $value): ?string
    {
        if ($value === null || ! is_string($value)) {
            return null;
        }

        $text = trim($value);

        return $text === '' ? null : $text;
    }
}
