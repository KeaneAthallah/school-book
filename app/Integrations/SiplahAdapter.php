<?php

namespace App\Integrations;

use App\Exceptions\IntegrationNotConfiguredException;
use App\Integrations\Contracts\OrderSourceAdapter;
use App\Models\OrderSource;

/**
 * SIPLah adapter.
 *
 * No verified API specification or credentials are bundled with this project,
 * so the adapter deliberately refuses to guess endpoints. Provide a concrete
 * implementation (or register a substitute through IntegrationManager) once
 * the official integration documentation and credentials are available.
 */
class SiplahAdapter implements OrderSourceAdapter
{
    public function key(): string
    {
        return 'siplah';
    }

    public function fetch(OrderSource $source): array
    {
        if (($source->config['enabled'] ?? false) !== true) {
            throw new IntegrationNotConfiguredException(
                'Integrasi SIPLah belum diaktifkan. Lengkapi kredensial API pada konfigurasi sumber pesanan.',
            );
        }

        throw new IntegrationNotConfiguredException(
            'Integrasi SIPLah belum memiliki implementasi API terverifikasi pada proyek ini.',
        );
    }
}
