<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Tests;

class CaptchaHelperTest extends TestCase
{
    public function test_captcha_helper_renders_image_widget_markup(): void
    {
        $this->app['config']->set('captcha.default', 'image');

        $html = (string) captcha();

        $this->assertStringContainsString('laravel-captcha-image', $html);
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('LaravelCaptcha.refresh', $html);
    }

    public function test_captcha_helper_renders_math_widget_markup(): void
    {
        $html = (string) captcha('math');

        $this->assertStringContainsString('laravel-captcha-math', $html);
        $this->assertStringContainsString('laravel-captcha-question', $html);
        $this->assertMatchesRegularExpression('/\d+ [+\-*] \d+ = \?/', $html);
    }

    public function test_captcha_src_returns_a_data_uri_for_image_type(): void
    {
        $src = captcha_src('image');

        $this->assertStringStartsWith('data:image/png;base64,', $src);
    }

    public function test_captcha_src_returns_question_for_non_image_types(): void
    {
        $src = captcha_src('math');

        $this->assertMatchesRegularExpression('/\d+ [+\-*] \d+ = \?/', $src);
    }

    public function test_refresh_route_returns_json_payload(): void
    {
        $response = $this->get(route('captcha.refresh', ['type' => 'math']));

        $response->assertOk();
        $response->assertJsonStructure(['type', 'question']);
        $response->assertJson(['type' => 'math']);
    }

    public function test_refresh_route_returns_image_src_for_image_type(): void
    {
        $response = $this->get(route('captcha.refresh', ['type' => 'image']));

        $response->assertOk();
        $response->assertJsonStructure(['type', 'src']);
        $response->assertJson(['type' => 'image']);
    }
}
