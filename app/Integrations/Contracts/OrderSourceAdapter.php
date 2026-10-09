<?php

namespace App\Integrations\Contracts;

use App\Exceptions\IntegrationNotConfiguredException;
use App\Models\OrderSource;

interface OrderSourceAdapter
{
    /**
     * Machine key for the adapter.
     */
    public function key(): string;

    /**
     * Fetch normalized external orders for the given source.
     *
     * Each item must expose the shape consumed by IntegrationService:
     * external_reference, order_date, school_npsn, and items (code, quantity, unit_price).
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws IntegrationNotConfiguredException when the
     *                                           integration is not configured with working credentials.
     */
    public function fetch(OrderSource $source): array;
}
