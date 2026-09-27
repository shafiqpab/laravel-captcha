<?php

use Illuminate\Support\Facades\Route;
use Webcraft\LaravelCaptcha\Http\Controllers\CaptchaController;

Route::middleware(config('captcha.middleware', ['web']))
    ->prefix(config('captcha.route_prefix', 'captcha'))
    ->group(function () {
        Route::get('/refresh/{type?}', [CaptchaController::class, 'refresh'])
            ->name('captcha.refresh');
    });
