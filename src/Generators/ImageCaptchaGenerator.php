<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Generators;

use Webcraft\LaravelCaptcha\CaptchaResult;

final class ImageCaptchaGenerator extends AbstractGenerator
{
    public function type(): string
    {
        return 'image';
    }

    public function generate(): CaptchaResult
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('The GD extension is required to generate image captchas.');
        }

        $length = (int) ($this->config['length'] ?? 5);
        $pool = (string) ($this->config['characters'] ?? 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
        $answer = $this->randomString($length, $pool);

        $width = max(60, (int) ($this->config['width'] ?? 150));
        $height = max(30, (int) ($this->config['height'] ?? 50));

        $image = imagecreatetruecolor($width, $height);

        [$bgR, $bgG, $bgB] = $this->config['background_color'] ?? [255, 255, 255];
        $background = imagecolorallocate($image, (int) $bgR, (int) $bgG, (int) $bgB);
        imagefill($image, 0, 0, $background);

        [$lineCount, $dotCount, $noiseEnabled] = $this->resolveDifficulty();

        if ($noiseEnabled && ($this->config['noise'] ?? true)) {
            $this->drawNoisePixels($image, $width, $height);
        }

        $this->drawLines($image, $width, $height, (int) ($this->config['lines'] ?? $lineCount));
        $this->drawDots($image, $width, $height, (int) ($this->config['dots'] ?? $dotCount));
        $this->drawText($image, $answer, $width, $height);

        // A few extra lines on top of the text make OCR meaningfully harder.
        $this->drawLines($image, $width, $height, max(1, (int) round($lineCount / 3)));

        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        $dataUri = 'data:image/png;base64,' . base64_encode($binary);

        return new CaptchaResult(type: 'image', answer: $answer, image: $dataUri);
    }

    /**
     * @return array{0: int, 1: int, 2: bool} [lineCount, dotCount, noiseEnabled]
     */
    private function resolveDifficulty(): array
    {
        return match ($this->config['difficulty'] ?? 'medium') {
            'easy' => [2, 15, false],
            'hard' => [9, 90, true],
            default => [5, 45, true],
        };
    }

    private function drawNoisePixels($image, int $width, int $height): void
    {
        $count = (int) (($width * $height) / 12);

        for ($i = 0; $i < $count; $i++) {
            $color = imagecolorallocate(
                $image,
                random_int(190, 255),
                random_int(190, 255),
                random_int(190, 255)
            );

            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $color);
        }
    }

    private function drawLines($image, int $width, int $height, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $color = imagecolorallocate(
                $image,
                random_int(90, 190),
                random_int(90, 190),
                random_int(90, 190)
            );

            imageline(
                $image,
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height),
                $color
            );
        }
    }

    private function drawDots($image, int $width, int $height, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $color = imagecolorallocate(
                $image,
                random_int(110, 210),
                random_int(110, 210),
                random_int(110, 210)
            );

            $x = random_int(0, $width - 1);
            $y = random_int(0, $height - 1);
            $size = random_int(1, 2);

            imagefilledellipse($image, $x, $y, $size, $size, $color);
        }
    }

    private function drawText($image, string $text, int $width, int $height): void
    {
        $length = mb_strlen($text);
        $fontPath = $this->config['font'] ?? null;
        $fontSize = (int) ($this->config['font_size'] ?? 24);
        $rotateEnabled = (bool) ($this->config['rotate'] ?? true);
        $colors = $this->config['text_colors'] ?? [[51, 51, 51], [0, 51, 102], [102, 0, 0]];

        $useTtf = is_string($fontPath) && $fontPath !== '' && function_exists('imagettftext') && file_exists($fontPath);
        $segment = $width / max(1, $length);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1);
            [$r, $g, $b] = $colors[array_rand($colors)];
            $color = imagecolorallocate($image, (int) $r, (int) $g, (int) $b);

            $x = (int) round($segment * $i) + random_int(2, max(3, (int) ($segment / 4)));
            $angle = $rotateEnabled ? random_int(-25, 25) : 0;

            if ($useTtf) {
                $y = (int) ($height / 2) + (int) ($fontSize / 3);
                imagettftext($image, $fontSize, $angle, $x, $y, $color, $fontPath, $char);
            } else {
                // Built-in bitmap fonts cannot rotate; jitter vertically instead
                // so the fallback path still resists simple pattern matching.
                $y = (int) (($height - imagefontheight(5)) / 2) + random_int(-4, 4);
                imagestring($image, 5, $x, max(0, $y), $char, $color);
            }
        }
    }
}
