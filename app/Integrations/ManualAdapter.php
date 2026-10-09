<?php

namespace App\Integrations;

use App\Integrations\Contracts\OrderSourceAdapter;
use App\Models\OrderSource;

/**
 * Manual order source: orders are entered directly in the application, so
 * there is nothing to synchronize from an external system.
 */
class ManualAdapter implements OrderSourceAdapter
{
    public function key(): string
    {
        return 'manual';
    }

    public function fetch(OrderSource $source): array
    {
        return [];
    }
}
