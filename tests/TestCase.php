<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render Blade layouts that call @vite(). Resolving the
        // real manifest would require `npm run build` before every test run,
        // which CI does in a separate job with its own workspace. The assertions
        // here cover server-rendered output, not the compiled assets, so the
        // Vite tags are stubbed out.
        $this->withoutVite();
    }
}
