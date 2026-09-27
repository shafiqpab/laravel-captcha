<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Support;

use Illuminate\Contracts\Session\Session as SessionStore;

final class CaptchaStore
{
    public function __construct(
        private readonly SessionStore $session,
        private readonly string $sessionKey = 'captcha',
        private readonly int $expireSeconds = 60,
        private readonly bool $caseSensitive = false,
        private readonly bool $oneTime = true,
    ) {
    }

    /**
     * Persist the hashed answer for the current session, replacing any
     * previously stored challenge.
     */
    public function put(string $answer, string $type): void
    {
        $this->session->put($this->sessionKey, [
            'hash' => $this->hash($answer),
            'type' => $type,
            'expires_at' => time() + $this->expireSeconds,
        ]);
    }

    /**
     * Validate user input against the stored answer.
     *
     * When "one_time" is enabled the stored challenge is destroyed after
     * this call regardless of the outcome, so it can never be reused.
     */
    public function check(string $input): bool
    {
        $data = $this->session->get($this->sessionKey);

        if (! is_array($data) || ! isset($data['hash'], $data['expires_at'])) {
            return false;
        }

        if (time() > (int) $data['expires_at']) {
            $this->forget();

            return false;
        }

        $isValid = hash_equals((string) $data['hash'], $this->hash($input));

        if ($this->oneTime) {
            $this->forget();
        }

        return $isValid;
    }

    public function forget(): void
    {
        $this->session->forget($this->sessionKey);
    }

    private function hash(string $value): string
    {
        $value = trim($value);
        $value = $this->caseSensitive ? $value : mb_strtolower($value);

        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
