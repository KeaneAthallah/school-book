<?php
// Temporary deployment helper for hosts without terminal access (e.g. Hostinger hPanel).
// Runs migrate:fresh (drops all tables and re-runs migrations). DANGER: destroys data.
// Upload this file to the project's public/ folder, visit https://your-domain/migrate-fresh.php,
// then DELETE this file immediately after use.
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<pre>\n";

$commands = [
    'config:clear',
    'route:clear',
    'view:clear',
    'cache:clear',
    'optimize:clear',
];

foreach ($commands as $cmd) {
    try {
        $exit = $kernel->call($cmd);
        echo "OK  $cmd (exit $exit)\n";
    } catch (Throwable $e) {
        echo "ERR $cmd: ".$e->getMessage()."\n";
    }
}

// Drop all tables and re-run all migrations
try {
    $exit = $kernel->call('migrate:fresh', ['--force' => true]);
    echo "OK  migrate:fresh (exit $exit)\n";
} catch (Throwable $e) {
    echo "ERR migrate:fresh: ".$e->getMessage()."\n";
}

// Optional: seed the database (uncomment if needed)
// try {
//     $exit = $kernel->call('db:seed', ['--force' => true]);
//     echo "OK  db:seed (exit $exit)\n";
// } catch (Throwable $e) {
//     echo "ERR db:seed: ".$e->getMessage()."\n";
// }

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OK  opcache_reset\n";
}

echo "\nDone. DELETE this file now.\n</pre>";
