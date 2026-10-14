<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Page responses render the Inertia root view, which references the
        // per-page Vite entries. Stub Vite so tests do not depend on built assets.
        $this->withoutVite();

        // CSRF is exercised by the browser; here we test controllers, so skip
        // the token check while keeping authentication and authorization active.
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    protected function setUpTraits()
    {
        // Pest boots the framework before PHPUnit applies the phpunit.xml env
        // vars, so Laravel's env repository keeps the .env connection. Force
        // the in-memory test database here — after the app boots but before
        // RefreshDatabase migrates — so tests never touch the live database.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        return parent::setUpTraits();
    }
}
