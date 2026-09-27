<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Webcraft\LaravelCaptcha\CaptchaServiceProvider;
use Webcraft\LaravelCaptcha\Support\CaptchaRenderer;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CaptchaServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('session.driver', 'array');
        $app['config']->set('captcha.expire', 60);
        $app['config']->set('captcha.one_time', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Each test gets a fresh widget so the "print script once" guard
        // in CaptchaRenderer doesn't leak between assertions.
        CaptchaRenderer::resetScriptState();
    }
}
