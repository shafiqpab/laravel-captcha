<?php

declare(strict_types=1);

use Illuminate\Support\HtmlString;
use Webcraft\LaravelCaptcha\Support\CaptchaRenderer;

if (! function_exists('captcha')) {
    /**
     * Render the full CAPTCHA widget (challenge + refresh button + JS) for
     * use in Blade views:
     *
     *   {!! captcha() !!}
     *   {!! captcha('math') !!}
     */
    function captcha(?string $type = null): HtmlString
    {
        return app(CaptchaRenderer::class)->render($type);
    }
}

if (! function_exists('captcha_src')) {
    /**
     * Generate a fresh CAPTCHA and return only its embeddable "src".
     *
     * For image captchas this is a base64 data URI suitable for an <img>
     * tag. For math/text captchas it returns the question string instead,
     * since there is no image to embed.
     */
    function captcha_src(?string $type = null): string
    {
        $result = app('captcha')->generate($type);

        return $result->image ?? (string) $result->question;
    }
}
