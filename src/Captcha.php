<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha;

use InvalidArgumentException;
use Webcraft\LaravelCaptcha\Contracts\CaptchaGenerator;
use Webcraft\LaravelCaptcha\Generators\ImageCaptchaGenerator;
use Webcraft\LaravelCaptcha\Generators\MathCaptchaGenerator;
use Webcraft\LaravelCaptcha\Generators\TextCaptchaGenerator;
use Webcraft\LaravelCaptcha\Support\CaptchaStore;

/**
 * Facade-friendly manager class bound in the container as "captcha".
 *
 * New CAPTCHA types can be added without modifying this class by calling
 * extend(), keeping the manager open for extension / closed for
 * modification (the "O" in SOLID).
 */
final class Captcha
{
    /** @var array<string, callable(array): CaptchaGenerator> */
    private array $customGenerators = [];

    public function __construct(
        private readonly CaptchaStore $store,
        private readonly array $config,
    ) {
    }

    /**
     * Generate a new challenge, persist its answer in the session and
     * return the result object to render.
     */
    public function generate(?string $type = null): CaptchaResult
    {
        $type = $type ?: ($this->config['default'] ?? 'image');

        $generator = $this->resolveGenerator($type);
        $result = $generator->generate();

        $this->store->put($result->answer, $result->type);

        return $result;
    }

    /**
     * Validate user input against the currently stored challenge.
     */
    public function check(string $input): bool
    {
        return $this->store->check($input);
    }

    /**
     * Discard any pending challenge (e.g. after a successful form submit).
     */
    public function forget(): void
    {
        $this->store->forget();
    }

    /**
     * Register a custom CAPTCHA generator, e.g.:
     *
     *   app('captcha')->extend('slider', fn (array $config) => new SliderGenerator($config));
     */
    public function extend(string $type, callable $resolver): void
    {
        $this->customGenerators[$type] = $resolver;
    }

    private function resolveGenerator(string $type): CaptchaGenerator
    {
        if (isset($this->customGenerators[$type])) {
            $generator = call_user_func($this->customGenerators[$type], $this->config);

            if (! $generator instanceof CaptchaGenerator) {
                throw new InvalidArgumentException("The generator registered for [{$type}] must implement CaptchaGenerator.");
            }

            return $generator;
        }

        return match ($type) {
            'image' => new ImageCaptchaGenerator($this->config),
            'math' => new MathCaptchaGenerator($this->config),
            'text' => new TextCaptchaGenerator($this->config),
            default => throw new InvalidArgumentException("Unknown captcha type [{$type}]."),
        };
    }
}
