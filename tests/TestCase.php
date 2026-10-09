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
}
