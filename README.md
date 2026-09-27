# Laravel Captcha

A reusable, extensible CAPTCHA package for **Laravel 10+** and **PHP 8.1+**, with image, math and text generators — no Livewire, Inertia, Vue or React required.

## Features

- 🖼 **Image CAPTCHA** — random letters/numbers rendered with GD, distorted with noise, lines, dots and rotated characters
- ➕ **Math CAPTCHA** — arithmetic challenges like `5 + 8 = ?`
- 🔤 **Text CAPTCHA** — lightweight plain-text challenge, no GD required
- ⚙️ Configurable length, image size, font, expiry and difficulty (`easy` / `medium` / `hard`)
- 🔐 Session-based, hashed (`hash_hmac`) answer storage — the plain answer is never stored
- 🎲 Cryptographically secure randomness via `random_int()`
- ⏱ Automatic expiry and **one-time** validation (answers can't be replayed)
- 🔄 AJAX refresh with **zero page reload**, and zero extra JS dependencies
- ✅ Drop-in Laravel validation rule: `'captcha' => ['required', 'captcha']`
- 🧩 Clean, SOLID architecture — add new CAPTCHA types with `Captcha::extend()`

## Requirements

- PHP 8.1+
- Laravel 10, 11 or 12
- The `ext-gd` PHP extension (required only for the image type)

## Installation

```bash
composer require webcraft/laravel-captcha
```

The package auto-registers its service provider and `Captcha` facade via Laravel package discovery. No manual registration is required.

### Publish the config and translations (optional)

```bash
php artisan vendor:publish --tag=captcha-config
php artisan vendor:publish --tag=captcha-lang
```

This publishes:

- `config/captcha.php`
- `lang/vendor/captcha/en/captcha.php`

## Quick start

### 1. Add the widget to your Blade form

```blade
<form method="POST" action="/register">
    @csrf

    {{-- ... your other fields ... --}}

    {!! captcha() !!}
    <input type="text" name="captcha" placeholder="Enter the code above" required>

    @error('captcha')
        <div class="text-danger">{{ $message }}</div>
    @enderror

    <button type="submit">Submit</button>
</form>
```

`captcha()` renders the challenge (an image, a math expression, or text — based on `config('captcha.default')`) together with a refresh button that swaps in a new challenge over AJAX, with no page reload.

Render a specific type regardless of the config default:

```blade
{!! captcha('math') !!}
{!! captcha('text') !!}
{!! captcha('image') !!}
```

### 2. Validate it in your controller

```php
$request->validate([
    'captcha' => ['required', 'captcha'],
]);
```

That's it — the `captcha` rule checks the submitted value against the hashed answer stored in the session, then invalidates it (one-time use).

## Helpers

| Helper | Description |
|---|---|
| `captcha(?string $type = null)` | Returns an `HtmlString` with the full widget: challenge markup + refresh button + inline JS. |
| `captcha_src(?string $type = null)` | Generates a fresh challenge and returns just its embeddable value — a `data:image/png;base64,...` URI for the image type, or the question string for math/text. |

```blade
{{-- Build your own markup instead of captcha() --}}
<img src="{{ captcha_src() }}" alt="captcha" id="my-captcha-img">
```

## Facade / manual usage

```php
use Webcraft\LaravelCaptcha\Facades\Captcha;

$result = Captcha::generate('math');   // Webcraft\LaravelCaptcha\CaptchaResult
$result->question;                     // "5 + 8 = ?"
$result->answer;                       // "13" (never expose this to the client)

$isValid = Captcha::check($request->input('captcha'));
```

Or resolve it from the container: `app('captcha')`.

## Configuration (`config/captcha.php`)

```php
return [
    'default' => 'image',           // image | math | text
    'length' => 5,                  // answer length for image/text types
    'characters' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
    'case_sensitive' => false,
    'expire' => 60,                 // seconds before a challenge expires
    'one_time' => true,             // destroy the answer after first check()
    'session_key' => 'captcha',

    'route_prefix' => 'captcha',
    'middleware' => ['web'],

    // Image generator
    'width' => 150,
    'height' => 50,
    'font' => null,                 // absolute path to a .ttf font, or null for GD's built-in font
    'font_size' => 24,
    'rotate' => true,
    'difficulty' => 'medium',       // easy | medium | hard — controls noise/lines/dots density
    'lines' => null,                // null = use the difficulty preset
    'dots' => null,
    'noise' => true,
    'background_color' => [255, 255, 255],
    'text_colors' => [[51, 51, 51], [0, 51, 102], [102, 0, 0]],

    // Math generator
    'math' => [
        'min' => 1,
        'max' => 20,
        'operators' => ['+', '-', '*'],
    ],

    // Text generator
    'text' => [
        'template' => 'Type the following characters: :code',
    ],
];
```

## Using a custom TrueType font

Distorted image CAPTCHAs look and rotate best with a real font. Drop a `.ttf` file anywhere in your app (e.g. `storage/fonts/captcha.ttf`) and point to it:

```php
'font' => storage_path('fonts/captcha.ttf'),
```

Without a font configured, the package falls back to GD's built-in bitmap font (characters won't rotate, but jitter vertically instead for basic distortion).

## Adding a new CAPTCHA type

Implement `Webcraft\LaravelCaptcha\Contracts\CaptchaGenerator` and register it — no changes to the package internals required:

```php
use Webcraft\LaravelCaptcha\CaptchaResult;
use Webcraft\LaravelCaptcha\Generators\AbstractGenerator;

class WordScrambleGenerator extends AbstractGenerator
{
    public function type(): string
    {
        return 'scramble';
    }

    public function generate(): CaptchaResult
    {
        $word = 'LARAVEL';
        $scrambled = str_shuffle($word);

        return new CaptchaResult(
            type: 'scramble',
            answer: $word,
            question: "Unscramble this word: {$scrambled}"
        );
    }
}
```

Register it in a service provider's `boot()` method:

```php
use Webcraft\LaravelCaptcha\Facades\Captcha;

Captcha::extend('scramble', fn (array $config) => new WordScrambleGenerator($config));
```

Use it immediately:

```blade
{!! captcha('scramble') !!}
```

## How validation works

1. `captcha()` (or `captcha_src()`, or `Captcha::generate()`) creates a challenge and stores a **salted HMAC hash** of the answer in the session — never the plain answer.
2. The user submits the form with their guess in a `captcha` input.
3. The `captcha` validation rule calls `Captcha::check()`, which:
   - Fails immediately if the challenge has **expired** (`captcha.expire` seconds).
   - Compares hashes using `hash_equals()` (timing-safe).
   - Destroys the stored challenge afterwards when `captcha.one_time` is `true` (default), so the same answer can never be replayed — even if it was correct.

## Refreshing without a page reload

The markup rendered by `captcha()` already wires up a refresh button that calls `GET /captcha/refresh/{type}` via `fetch()` and swaps the image `src` or question text in place — no additional JavaScript needed. The endpoint returns JSON:

```json
{ "type": "image", "src": "data:image/png;base64,..." }
{ "type": "math", "question": "7 * 3 = ?" }
```

## Testing

```bash
composer install
vendor/bin/phpunit
```

The test suite uses [Orchestra Testbench](https://github.com/orchestral/testbench) to boot a minimal Laravel application around the package.

## Architecture overview

```
Captcha (manager)
 ├── resolves ───▶ CaptchaGenerator (interface)
 │                   ├── ImageCaptchaGenerator  (GD)
 │                   ├── MathCaptchaGenerator
 │                   ├── TextCaptchaGenerator
 │                   └── ...your custom generators via extend()
 ├── delegates to ─▶ CaptchaStore   (session persistence, hashing, expiry, one-time use)
 └── used by ──────▶ CaptchaRenderer (Blade HTML + refresh JS)
                     CaptchaController (AJAX refresh JSON endpoint)
                     'captcha' validation rule
```

Each generator has a single responsibility and depends only on the `CaptchaGenerator` contract, so the manager, controller, renderer and validation rule never need to change when a new CAPTCHA type is added — only a new generator class and one `extend()` call.

## Security notes

- All randomness uses `random_int()` (CSPRNG), never `rand()`/`mt_rand()`.
- Answers are stored as `hash_hmac('sha256', ..., config('app.key'))`, never in plain text.
- Comparisons use `hash_equals()` to avoid timing attacks.
- One-time validation prevents brute-force replay of a captured/guessed answer.
- Expiry prevents stale challenges from being solved offline and replayed later.

## License

MIT
