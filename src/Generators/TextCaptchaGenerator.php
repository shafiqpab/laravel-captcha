<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Generators;

use Webcraft\LaravelCaptcha\CaptchaResult;

final class TextCaptchaGenerator extends AbstractGenerator
{
    public function type(): string
    {
        return 'text';
    }

    public function generate(): CaptchaResult
    {
        $length = (int) ($this->config['length'] ?? 5);
        $pool = (string) ($this->config['characters'] ?? 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
        $answer = $this->randomString($length, $pool);

        $template = $this->config['text']['template'] ?? 'Type the following characters: :code';
        $question = str_replace(':code', $answer, $template);

        return new CaptchaResult(type: 'text', answer: $answer, question: $question);
    }
}
