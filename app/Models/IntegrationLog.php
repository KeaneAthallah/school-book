<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $source
 * @property string $direction
 * @property string|null $reference
 * @property string $status
 * @property string|null $message
 * @property array<string, mixed>|null $payload
 */
#[Fillable(['source', 'direction', 'reference', 'status', 'message', 'payload'])]
class IntegrationLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
