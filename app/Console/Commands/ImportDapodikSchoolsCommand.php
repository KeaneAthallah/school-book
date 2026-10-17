<?php

namespace App\Console\Commands;

use App\Services\DapodikSchoolImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class ImportDapodikSchoolsCommand extends Command
{
    public $signature = 'schools:import-dapodik
        {--region= : Single kabupaten kode to capture (e.g. 180700). Skips the Sulawesi Tengah / Sulawesi Barat sweep.}
        {--force : Re-capture kabupaten even if its snapshot already exists.}
        {--update : Also refresh geo/name fields of existing schools matched by npsn.}
        {--skip-capture : Skip the browser capture step and import existing snapshots only.}';

    public $description = 'One-time import of Dapodik school data for Sulawesi Tengah & Sulawesi Barat (browser capture + import). Not scheduled.';

    public function handle(DapodikSchoolImportService $import): int
    {
        $region = (string) ($this->option('region') ?: '');
        $force = (bool) $this->option('force');
        $update = (bool) $this->option('update');
        $skipCapture = (bool) $this->option('skip-capture');

        if (! $skipCapture) {
            $this->info('Step 1: browser capture of Dapodik school data (WAF requires MS Edge)');
            $code = $this->runCapture($region, $force);
            if ($code !== 0) {
                $this->error('Browser capture failed — see output above. Existing snapshots can still be imported with --skip-capture.');

                return $code;
            }
        } else {
            $this->info('Skipping browser capture (--skip-capture).');
        }

        $this->info('Step 2: importing snapshots into schools');
        $result = $import->import(updateExisting: $update);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Snapshot files', $result['files']],
                ['Total school rows', $result['total']],
                ['Created', $result['created']],
                ['Updated', $result['updated']],
                ['Skipped (existing, untouched)', $result['skipped_existing']],
                ['Skipped (invalid/empty name)', $result['skipped_invalid']],
            ]
        );

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function runCapture(string $region, bool $force): int
    {
        if (! $this->binaryAvailable('node')) {
            $this->error('Node.js is required to run the Dapodik capture (https://nodejs.org/).');

            return self::FAILURE;
        }

        $script = base_path('scripts/dapodik/sulawesi-capture.cjs');
        if (! is_file($script)) {
            $this->error(sprintf('Capture script missing: %s', $script));

            return self::FAILURE;
        }

        $nodeModules = base_path('node_modules/playwright-core');
        if (! is_dir($nodeModules)) {
            $this->warn('Installing playwright-core (one-time, dev-only)...');
            $install = Process::command(['npm', 'install', '--no-save', 'playwright-core'])
                ->timeout(600)
                ->run();
            if (! $install->successful()) {
                $this->error('Failed to install playwright-core. Run: npm i --no-save playwright-core');

                return self::FAILURE;
            }
        }

        if (! $this->binaryAvailable('msedge') && ! $this->edgeInstalled()) {
            $this->warn('MS Edge was not found on PATH. The capture still may work if Playwright can resolve the msedge channel; if it fails, install Microsoft Edge.');
        }

        $command = ['node', $script];
        if ($region !== '') {
            $command[] = '--region='.$region;
        }
        if ($force) {
            $command[] = '--force';
        }

        $this->line('$ '.implode(' ', array_map(fn ($c) => Str::contains($c, ' ') ? '"'.$c.'"' : $c, $command)));
        $process = Process::command($command)
            ->forever() // long-running: tens of thousands of schools may take hours
            ->run(null, function (string $type, string $line): void {
                $this->line($line);
            });

        return $process->exitCode();
    }

    private function binaryAvailable(string $name): bool
    {
        $which = strtoupper(substr(PHP_OS_FAMILY, 0, 3)) === 'WIN' ? 'where' : 'which';

        $p = Process::command([$which, $name])->run();

        return $p->successful();
    }

    private function edgeInstalled(): bool
    {
        // Look for Edge via a couple of well-known locations on Windows.
        foreach (['C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe', 'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe'] as $candidate) {
            if (is_file($candidate)) {
                return true;
            }
        }

        return false;
    }
}
