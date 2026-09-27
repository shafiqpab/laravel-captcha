<?php

declare(strict_types=1);

namespace Webcraft\LaravelCaptcha\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Webcraft\LaravelCaptcha\Captcha;

final class CaptchaController extends Controller
{
    /**
     * Generate a brand new challenge and return it as JSON so the
     * front-end can refresh the widget without a page reload.
     */
    public function refresh(Captcha $captcha, ?string $type = null): JsonResponse
    {
        $result = $captcha->generate($type);

        return response()->json($result->toArray());
    }
}
