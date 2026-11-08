<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Support;

use PHPUnit\Framework\TestCase;

abstract class AppTestCase extends TestCase
{
    protected TestApp $app;

    protected function setUp(): void
    {
        $this->app = TestApp::create();
    }

    protected function tearDown(): void
    {
        $this->app->cleanup();
    }
}
