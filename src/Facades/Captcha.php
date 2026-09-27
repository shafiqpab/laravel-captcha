<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Webcraft\LaravelCaptcha\CaptchaResult generate(?string $type = null)
 * @method static bool check(string $input)
 * @method static void forget()
 * @method static void extend(string $type, callable $resolver)
 *
 * @see \Webcraft\LaravelCaptcha\Captcha
 */
final class Captcha extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'captcha';
    }
}
