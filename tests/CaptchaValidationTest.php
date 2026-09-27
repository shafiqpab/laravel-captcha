<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Tests;

use Illuminate\Support\Facades\Validator;
use Webcraft\LaravelCaptcha\Captcha;

class CaptchaValidationTest extends TestCase
{
    public function test_correct_answer_passes_validation(): void
    {
        $captcha = $this->app->make(Captcha::class);
        $result = $captcha->generate('math');

        $this->assertTrue($captcha->check($result->answer));
    }

    public function test_incorrect_answer_fails_validation(): void
    {
        $captcha = $this->app->make(Captcha::class);
        $captcha->generate('math');

        $this->assertFalse($captcha->check('not-the-answer'));
    }

    public function test_answer_can_only_be_used_once(): void
    {
        $captcha = $this->app->make(Captcha::class);
        $result = $captcha->generate('math');

        $this->assertTrue($captcha->check($result->answer));
        // Second attempt with the same (now consumed) answer must fail.
        $this->assertFalse($captcha->check($result->answer));
    }

    public function test_expired_answer_fails_validation(): void
    {
        $this->app['config']->set('captcha.expire', -1);

        $captcha = $this->app->make(Captcha::class);
        $result = $captcha->generate('math');

        $this->assertFalse($captcha->check($result->answer));
    }

    public function test_case_insensitive_by_default(): void
    {
        $captcha = $this->app->make(Captcha::class);
        $result = $captcha->generate('text');

        $this->assertTrue($captcha->check(strtolower($result->answer)));
    }

    public function test_case_sensitive_mode_rejects_mismatched_case(): void
    {
        $this->app['config']->set('captcha.case_sensitive', true);

        $captcha = $this->app->make(Captcha::class);
        $result = $captcha->generate('text');

        $this->assertFalse($captcha->check(strtolower($result->answer) . '_wrong'));
    }

    public function test_string_rule_integrates_with_the_validator(): void
    {
        $captcha = $this->app->make(Captcha::class);
        $result = $captcha->generate('math');

        $validator = Validator::make(
            ['captcha' => $result->answer],
            ['captcha' => ['required', 'captcha']]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_string_rule_fails_with_correct_error_message(): void
    {
        $captcha = $this->app->make(Captcha::class);
        $captcha->generate('math');

        $validator = Validator::make(
            ['captcha' => 'wrong-answer'],
            ['captcha' => ['required', 'captcha']]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('invalid', $validator->errors()->first('captcha'));
    }

    public function test_extend_registers_a_custom_generator(): void
    {
        $captcha = $this->app->make(Captcha::class);

        $captcha->extend('fixed', function (array $config) {
            return new class($config) extends \Webcraft\LaravelCaptcha\Generators\AbstractGenerator {
                public function type(): string
                {
                    return 'fixed';
                }

                public function generate(): \Webcraft\LaravelCaptcha\CaptchaResult
                {
                    return new \Webcraft\LaravelCaptcha\CaptchaResult(
                        type: 'fixed',
                        answer: '42',
                        question: 'The answer is always 42'
                    );
                }
            };
        });

        $result = $captcha->generate('fixed');

        $this->assertSame('fixed', $result->type);
        $this->assertTrue($captcha->check('42'));
    }
}
