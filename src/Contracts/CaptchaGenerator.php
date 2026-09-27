<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Contracts;

use Webcraft\LaravelCaptcha\CaptchaResult;

/**
 * Implement this interface to add a new CAPTCHA type.
 *
 * Register it at runtime with:
 *
 *   app('captcha')->extend('recaptcha-lite', fn (array $config) => new MyGenerator($config));
 */
interface CaptchaGenerator
{
    /**
     * The unique type identifier, e.g. "image", "math", "text".
     */
    public function type(): string;

    /**
     * Generate a fresh challenge/answer pair.
     */
    public function generate(): CaptchaResult;
}
