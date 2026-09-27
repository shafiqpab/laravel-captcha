<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default CAPTCHA Type
    |--------------------------------------------------------------------------
    |
    | The generator used when no explicit type is passed to captcha() or
    | captcha_src(). Supported out of the box: "image", "math", "text".
    | Custom types can be registered via Captcha::extend().
    |
    */
    'default' => 'image',

    /*
    |--------------------------------------------------------------------------
    | Answer Length
    |--------------------------------------------------------------------------
    |
    | Number of characters generated for the "image" and "text" generators.
    |
    */
    'length' => 5,

    /*
    |--------------------------------------------------------------------------
    | Character Pool
    |--------------------------------------------------------------------------
    |
    | Ambiguous characters (0/O, 1/I/l) are excluded by default to keep the
    | CAPTCHA human friendly.
    |
    */
    'characters' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',

    /*
    |--------------------------------------------------------------------------
    | Case Sensitivity
    |--------------------------------------------------------------------------
    */
    'case_sensitive' => false,

    /*
    |--------------------------------------------------------------------------
    | Expiry (seconds)
    |--------------------------------------------------------------------------
    |
    | How long a generated CAPTCHA answer remains valid in the session.
    |
    */
    'expire' => 60,

    /*
    |--------------------------------------------------------------------------
    | One-Time Validation
    |--------------------------------------------------------------------------
    |
    | When true, the stored answer is destroyed after the first check()
    | attempt (pass or fail), preventing replay / brute-force retries.
    |
    */
    'one_time' => true,

    /*
    |--------------------------------------------------------------------------
    | Session Key
    |--------------------------------------------------------------------------
    */
    'session_key' => 'captcha',

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'captcha',
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Image Generator Settings
    |--------------------------------------------------------------------------
    */
    'width' => 150,
    'height' => 50,

    // Absolute path to a .ttf font file. Null falls back to GD's built-in font.
    'font' => null,
    'font_size' => 24,

    // Rotate individual characters for extra distortion.
    'rotate' => true,

    // Difficulty presets control the amount of lines/dots/noise: easy|medium|hard
    'difficulty' => 'medium',

    // Explicit overrides (null = use the difficulty preset value)
    'lines' => null,
    'dots' => null,
    'noise' => true,

    'background_color' => [255, 255, 255],

    'text_colors' => [
        [51, 51, 51],
        [0, 51, 102],
        [102, 0, 0],
        [0, 102, 0],
        [102, 51, 0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Math Generator Settings
    |--------------------------------------------------------------------------
    */
    'math' => [
        'min' => 1,
        'max' => 20,
        'operators' => ['+', '-', '*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Text Generator Settings
    |--------------------------------------------------------------------------
    */
    'text' => [
        // :code is replaced with the generated random string
        'template' => 'Type the following characters: :code',
    ],

];
