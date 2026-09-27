<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Generators;

use Webcraft\LaravelCaptcha\CaptchaResult;

final class MathCaptchaGenerator extends AbstractGenerator
{
    public function type(): string
    {
        return 'math';
    }

    public function generate(): CaptchaResult
    {
        $mathConfig = $this->config['math'] ?? [];
        $min = (int) ($mathConfig['min'] ?? 1);
        $max = (int) ($mathConfig['max'] ?? 20);
        $operators = $mathConfig['operators'] ?? ['+', '-', '*'];

        if (empty($operators)) {
            $operators = ['+'];
        }

        $a = $this->randomInt($min, $max);
        $b = $this->randomInt($min, $max);
        $operator = $operators[random_int(0, count($operators) - 1)];

        // Avoid negative results for subtraction so the challenge stays simple.
        if ($operator === '-' && $b > $a) {
            [$a, $b] = [$b, $a];
        }

        $answer = match ($operator) {
            '+' => $a + $b,
            '-' => $a - $b,
            '*' => $a * $b,
            default => $a + $b,
        };

        $question = sprintf('%d %s %d = ?', $a, $operator, $b);

        return new CaptchaResult(type: 'math', answer: (string) $answer, question: $question);
    }
}
