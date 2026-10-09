<?php

namespace App\Support;

/**
 * Outcome of an integration synchronization run.
 */
final class IntegrationSyncResult
{
    public function __construct(
        public readonly int $created,
        public readonly int $skipped,
        public readonly ?string $message = null,
    ) {}

    public function successful(): bool
    {
        return $this->message === null;
    }
}
