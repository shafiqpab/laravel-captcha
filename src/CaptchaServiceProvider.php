<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Webcraft\LaravelCaptcha\Support\CaptchaRenderer;
use Webcraft\LaravelCaptcha\Support\CaptchaStore;

final class CaptchaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/captcha.php', 'captcha');

        $this->app->singleton(CaptchaStore::class, function ($app) {
            $config = (array) $app['config']->get('captcha', []);

            return new CaptchaStore(
                session: $app['session.store'],
                sessionKey: $config['session_key'] ?? 'captcha',
                expireSeconds: (int) ($config['expire'] ?? 60),
                caseSensitive: (bool) ($config['case_sensitive'] ?? false),
                oneTime: (bool) ($config['one_time'] ?? true),
            );
        });

        $this->app->singleton(Captcha::class, function ($app) {
            return new Captcha(
                store: $app->make(CaptchaStore::class),
                config: (array) $app['config']->get('captcha', []),
            );
        });

        $this->app->alias(Captcha::class, 'captcha');

        $this->app->singleton(CaptchaRenderer::class, function ($app) {
            return new CaptchaRenderer($app->make(Captcha::class));
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/captcha.php');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'captcha');

        $this->registerValidationRule();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/captcha.php' => $this->app->configPath('captcha.php'),
            ], 'captcha-config');

            $this->publishes([
                __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/captcha'),
            ], 'captcha-lang');
        }
    }

    /**
     * Registers the 'captcha' string rule so it can be used as:
     *
     *   'captcha' => ['required', 'captcha']
     */
    private function registerValidationRule(): void
    {
        Validator::extend('captcha', function ($attribute, $value) {
            if (! is_string($value) && ! is_numeric($value)) {
                return false;
            }

            return $this->app->make(Captcha::class)->check((string) $value);
        });

        Validator::replacer('captcha', function ($message, $attribute, $rule, $parameters) {
            return trans('captcha::captcha.invalid', ['attribute' => $attribute]);
        });
    }
}
