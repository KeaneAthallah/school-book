<?php
// Temporary deployment helper for hosts without terminal access (e.g. Hostinger hPanel).
// Upload this file to the project's public/ folder, visit https://your-domain/deploy.php,
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

// Run pending migrations
try {
    $exit = $kernel->call('migrate', ['--force' => true]);
    echo "OK  migrate (exit $exit)\n";
} catch (Throwable $e) {
    echo "ERR migrate: ".$e->getMessage()."\n";
}

// Recompute all order totals with the current tax formula (pengurang).
try {
    $service = $app->make(\App\Services\OrderService::class);
    $count = 0;
    foreach (\App\Models\Order::query()->get() as $order) {
        $service->recalculate($order, null);
        $count++;
    }
    echo "OK  recomputed $count orders\n";
} catch (Throwable $e) {
    echo "ERR recompute: ".$e->getMessage()."\n";
}

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OK  opcache_reset\n";
}

echo "\nDone. DELETE this file now.\n</pre>";