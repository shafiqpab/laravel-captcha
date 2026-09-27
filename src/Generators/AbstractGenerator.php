<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Generators;

use Webcraft\LaravelCaptcha\Contracts\CaptchaGenerator;

abstract class AbstractGenerator implements CaptchaGenerator
{
    public function __construct(protected array $config)
    {
    }

    /**
     * Cryptographically secure random string built from the given pool.
     */
    protected function randomString(int $length, string $pool): string
    {
        $poolLength = mb_strlen($pool);

        if ($poolLength < 1) {
            throw new \InvalidArgumentException('The character pool must not be empty.');
        }

        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= mb_substr($pool, random_int(0, $poolLength - 1), 1);
        }

        return $result;
    }

    /**
     * Cryptographically secure random integer within an inclusive range.
     */
    protected function randomInt(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}
