<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function createApplication()
    {
        $app = require dirname(__DIR__).'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
