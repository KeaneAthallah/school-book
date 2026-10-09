<?php

namespace App\Integrations;

use App\Enums\OrderSourceType;
use App\Integrations\Contracts\OrderSourceAdapter;
use App\Models\OrderSource;

/**
 * Resolves the adapter responsible for an order source.
 *
 * Registered as a singleton so tests (or future real integrations) can swap an
 * adapter with extend().
 */
class IntegrationManager
{
    /** @var array<string, OrderSourceAdapter> */
    private array $adapters = [];

    public function __construct()
    {
        $this->adapters = [
            OrderSourceType::Siplah->value => new SiplahAdapter,
            OrderSourceType::Ekatalog->value => new EkatalogAdapter,
            OrderSourceType::Manual->value => new ManualAdapter,
            OrderSourceType::Other->value => new ManualAdapter,
        ];
    }

    public function extend(OrderSourceType $type, OrderSourceAdapter $adapter): void
    {
        $this->adapters[$type->value] = $adapter;
    }

    public function for(OrderSource $source): OrderSourceAdapter
    {
        return $this->adapters[$source->type->value] ?? new ManualAdapter;
    }
}
