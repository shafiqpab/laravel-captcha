<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha;

/**
 * Immutable result produced by a CaptchaGenerator.
 *
 * - "answer"   is the raw value the user must type back (never rendered as-is
 *              for image captchas, only its hash is stored in the session).
 * - "question" is the human readable challenge for text-based captchas
 *              (math, text). Null for image captchas.
 * - "image"    is a base64 "data:image/png;base64,..." URI. Null for
 *              non-image captchas.
 */
final class CaptchaResult
{
    public function __construct(
        public readonly string $type,
        public readonly string $answer,
        public readonly ?string $question = null,
        public readonly ?string $image = null,
    ) {
    }

    public function isImage(): bool
    {
        return $this->image !== null;
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'question' => $this->question,
            'src' => $this->image,
        ], static fn ($value) => $value !== null);
    }
}
