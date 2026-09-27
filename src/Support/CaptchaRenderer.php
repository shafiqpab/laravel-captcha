<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Webcraft\LaravelCaptcha\Captcha;

final class CaptchaRenderer
{
    private static bool $scriptPrinted = false;

    public function __construct(private readonly Captcha $captcha)
    {
    }

    public function render(?string $type = null): HtmlString
    {
        $result = $this->captcha->generate($type);
        $wrapperId = 'captcha-' . Str::random(8);
        $refreshUrl = route('captcha.refresh', ['type' => $result->type]);

        $body = $result->isImage()
            ? sprintf(
                '<img src="%s" alt="captcha" class="laravel-captcha-img">',
                e($result->image)
            )
            : sprintf(
                '<span class="laravel-captcha-question">%s</span>',
                e($result->question)
            );

        $html = <<<HTML
<div id="{$wrapperId}" class="laravel-captcha laravel-captcha-{$result->type}">
    {$body}
    <button type="button" class="laravel-captcha-refresh" aria-label="Refresh captcha"
        onclick="LaravelCaptcha.refresh('{$wrapperId}', '{$refreshUrl}')">&#x21bb;</button>
</div>
HTML;

        return new HtmlString($html . $this->script());
    }

    /**
     * The refresh script only needs to be printed once per response, even
     * if captcha() is called multiple times on the same page.
     */
    private function script(): string
    {
        if (self::$scriptPrinted) {
            return '';
        }

        self::$scriptPrinted = true;

        return <<<'HTML'
<script>
(function () {
    if (window.LaravelCaptcha) {
        return;
    }

    window.LaravelCaptcha = {
        refresh: function (wrapperId, url) {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    var wrapper = document.getElementById(wrapperId);
                    if (!wrapper) {
                        return;
                    }
                    if (data.type === 'image' && data.src) {
                        var img = wrapper.querySelector('img.laravel-captcha-img');
                        if (img) {
                            img.src = data.src;
                        }
                    } else if (data.question) {
                        var question = wrapper.querySelector('.laravel-captcha-question');
                        if (question) {
                            question.textContent = data.question;
                        }
                    }
                })
                .catch(function (error) {
                    console.error('Captcha refresh failed:', error);
                });
        }
    };
})();
</script>
HTML;
    }

    /**
     * Reset the "script printed" flag. Intended for tests only.
     */
    public static function resetScriptState(): void
    {
        self::$scriptPrinted = false;
    }
}
