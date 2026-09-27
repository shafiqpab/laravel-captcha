<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Tests;

use Webcraft\LaravelCaptcha\Generators\ImageCaptchaGenerator;
use Webcraft\LaravelCaptcha\Generators\MathCaptchaGenerator;
use Webcraft\LaravelCaptcha\Generators\TextCaptchaGenerator;

class CaptchaGenerationTest extends TestCase
{
    public function test_image_generator_produces_a_base64_png_and_matching_length_answer(): void
    {
        $generator = new ImageCaptchaGenerator([
            'length' => 6,
            'characters' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
            'width' => 150,
            'height' => 50,
            'difficulty' => 'medium',
        ]);

        $result = $generator->generate();

        $this->assertSame('image', $result->type);
        $this->assertSame(6, strlen($result->answer));
        $this->assertNotNull($result->image);
        $this->assertStringStartsWith('data:image/png;base64,', $result->image);

        $encoded = substr($result->image, strlen('data:image/png;base64,'));
        $this->assertNotFalse(base64_decode($encoded, true));
    }

    public function test_image_generator_answers_only_use_configured_characters(): void
    {
        $generator = new ImageCaptchaGenerator([
            'length' => 20,
            'characters' => 'AB',
        ]);

        $result = $generator->generate();

        $this->assertMatchesRegularExpression('/^[AB]+$/', $result->answer);
    }

    public function test_math_generator_produces_a_correct_arithmetic_answer(): void
    {
        $generator = new MathCaptchaGenerator([
            'math' => ['min' => 1, 'max' => 10, 'operators' => ['+']],
        ]);

        $result = $generator->generate();

        $this->assertSame('math', $result->type);
        $this->assertMatchesRegularExpression('/^\d+ \+ \d+ = \?$/', $result->question);

        preg_match('/^(\d+) \+ (\d+) = \?$/', $result->question, $matches);
        $expected = (int) $matches[1] + (int) $matches[2];

        $this->assertSame((string) $expected, $result->answer);
    }

    public function test_math_generator_never_produces_a_negative_subtraction_result(): void
    {
        $generator = new MathCaptchaGenerator([
            'math' => ['min' => 1, 'max' => 20, 'operators' => ['-']],
        ]);

        for ($i = 0; $i < 25; $i++) {
            $result = $generator->generate();
            $this->assertGreaterThanOrEqual(0, (int) $result->answer);
        }
    }

    public function test_text_generator_produces_a_question_containing_the_answer(): void
    {
        $generator = new TextCaptchaGenerator([
            'length' => 5,
            'characters' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
            'text' => ['template' => 'Type the following characters: :code'],
        ]);

        $result = $generator->generate();

        $this->assertSame('text', $result->type);
        $this->assertSame(5, strlen($result->answer));
        $this->assertStringContainsString($result->answer, $result->question);
    }
}
