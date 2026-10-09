<?php

namespace App\Integrations;

use App\Exceptions\IntegrationNotConfiguredException;
use App\Integrations\Contracts\OrderSourceAdapter;
use App\Models\OrderSource;

/**
 * e-Katalog adapter.
 *
 * Mirrors the SIPLah adapter: it fails loudly rather than inventing endpoints,
 * and can be replaced through IntegrationManager once the real integration is
 * documented and credentialed.
 */
class EkatalogAdapter implements OrderSourceAdapter
{
    public function key(): string
    {
        return 'ekatalog';
    }

    public function fetch(OrderSource $source): array
    {
        if (($source->config['enabled'] ?? false) !== true) {
            throw new IntegrationNotConfiguredException(
                'Integrasi e-Katalog belum diaktifkan. Lengkapi kredensial API pada konfigurasi sumber pesanan.',
            );
        }

        throw new IntegrationNotConfiguredException(
            'Integrasi e-Katalog belum memiliki implementasi API terverifikasi pada proyek ini.',
        );
    }
}
