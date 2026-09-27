<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Object-style alternative to the string rule.
 *
 * Usage: 'captcha' => ['required', new CaptchaRule]
 *
 * The plain string form 'captcha' (registered by the service provider via
 * Validator::extend) is the primary, documented API and behaves
 * identically to this class.
 */
final class CaptchaRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return app('captcha')->check((string) $value);
    }

    public function message(): string
    {
        return trans('captcha::captcha.invalid');
    }
}
