<?php

declare(strict_types=1);

use App\Csrf;

/**
 * Load a .env file into $_ENV. Values may be quoted; no inline comments.
 */
function load_env(string $path): void
{
    if (! is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if ($key !== '') {
            $_ENV[$key] = $value;
            putenv($key.'='.$value);
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);

    return ($value === false || $value === null || $value === '') ? $default : $value;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = env($key);

    if ($value === null) {
        return $default;
    }

    return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
}

function base_path(string $path = ''): string
{
    return BASE_PATH.($path !== '' ? '/'.ltrim($path, '/') : '');
}

function public_path(string $path = ''): string
{
    return BASE_PATH.'/public'.($path !== '' ? '/'.ltrim($path, '/') : '');
}

function storage_path(string $path = ''): string
{
    return BASE_PATH.'/storage'.($path !== '' ? '/'.ltrim($path, '/') : '');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string) env('APP_URL', 'http://localhost'), '/');

    return $path === '' ? $base.'/' : $base.'/'.ltrim($path, '/');
}

function redirect(string $to, int $status = 303): never
{
    $target = preg_match('~^https?://~i', $to) ? $to : url($to);
    header('Location: '.$target, true, $status);
    exit;
}

function back(string $fallback = '/'): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';

    if ($referer !== '' && str_starts_with($referer, (string) env('APP_URL', ''))) {
        redirect($referer, 303);
    }

    redirect($fallback, 303);
}

function abort(int $status = 404, string $message = ''): never
{
    http_response_code($status);

    $view = match ($status) {
        403 => 'errors/403',
        419 => 'errors/419',
        default => 'errors/404',
    };

    if (! is_file(base_path('views/'.$view.'.php'))) {
        $view = 'errors/404';
    }

    $title = $message !== '' ? $message : match ($status) {
        403 => 'Not allowed',
        419 => 'Session expired',
        default => 'Page not found',
    };

    require base_path('views/'.$view.'.php');
    exit;
}

function csrf_token(): string
{
    return (string) ($_SESSION['_csrf'] ?? '');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="'.e(csrf_token()).'">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="'.e(strtoupper($method)).'">';
}

function old(string $key, mixed $default = ''): mixed
{
    return $GLOBALS['__old'][$key] ?? $default;
}

function errors(): array
{
    return $GLOBALS['__errors'] ?? [];
}

function error_for(string $key): ?string
{
    $errors = errors();

    return isset($errors[$key]) ? (string) (is_array($errors[$key]) ? $errors[$key][0] : $errors[$key]) : null;
}

function flash(string $key, mixed $default = null): mixed
{
    return $GLOBALS['__flash'][$key] ?? $default;
}

function flash_set(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function flash_now(string $key, mixed $value): void
{
    $GLOBALS['__flash'][$key] = $value;
}

/**
 * Render a view inside a layout. Views are plain PHP files.
 */
function render(string $name, array $data = []): string
{
    $name = str_replace('.', '/', $name);

    extract($data, EXTR_SKIP);

    ob_start();
    require base_path('views/'.$name.'.php');

    return (string) ob_get_clean();
}

function view(string $name, array $data = [], string $layout = 'layouts/public'): void
{
    $data['title'] = $data['title'] ?? (string) env('APP_NAME', 'Gather');
    $data['errors'] = errors();
    $data['flash'] = $GLOBALS['__flash'] ?? [];

    $content = render($name, $data);

    if ($layout === '') {
        echo $content;

        return;
    }

    extract($data, EXTR_SKIP);
    require base_path('views/'.$layout.'.php');
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require base_path('views/partials/'.$name.'.php');
}

function now_utc(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('UTC'));
}

function tz(string $name): DateTimeZone
{
    try {
        return new DateTimeZone($name);
    } catch (Exception) {
        return new DateTimeZone('UTC');
    }
}

function utc_to_local(?string $utc, string $timezone): ?DateTimeImmutable
{
    if ($utc === null || $utc === '') {
        return null;
    }

    return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(tz($timezone));
}

function fmt_datetime(?string $utc, string $timezone, string $format = 'D, j M Y · g:i A'): string
{
    $local = utc_to_local($utc, $timezone);

    return $local ? $local->format($format) : '';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $letters !== '' ? $letters : '?';
}

/**
 * Vite tags. Uses the dev server when it is running, otherwise the build manifest.
 */
function vite_tags(): string
{
    static $tags = null;

    if ($tags !== null) {
        return $tags;
    }

    $dev = env_bool('VITE_DEV', false) && dev_server_up();

    if ($dev) {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $host = preg_replace('/:\d+$/', '', $host) ?: 'localhost';
        $port = (string) env('VITE_PORT', '5173');
        $base = 'http://'.$host.':'.$port;

        return $tags = '<script type="module" src="'.e($base).'/@vite/client"></script>'
            .'<script type="module" src="'.e($base).'/resources/js/app.js"></script>';
    }

    $manifestPath = public_path('build/.vite/manifest.json');

    if (! is_file($manifestPath)) {
        return $tags = '';
    }

    $manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];
    $entry = $manifest['resources/js/app.js'] ?? null;

    if ($entry === null) {
        return $tags = '';
    }

    $out = '';

    foreach ($entry['css'] ?? [] as $css) {
        $out .= '<link rel="stylesheet" href="'.e(url('build/'.$css)).'">';
    }

    $out .= '<script type="module" src="'.e(url('build/'.$entry['file'])).'"></script>';

    return $tags = $out;
}

function dev_server_up(): bool
{
    static $up = null;

    if ($up !== null) {
        return $up;
    }

    $socket = @fsockopen('127.0.0.1', (int) env('VITE_PORT', 5173), $errno, $errstr, 0.05);

    if ($socket) {
        fclose($socket);

        return $up = true;
    }

    return $up = false;
}

function format_bytes(int $bytes): string
{
    foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
        if ($bytes < 1024) {
            return round($bytes, 1).' '.$unit;
        }

        $bytes /= 1024;
    }

    return round($bytes, 1).' TB';
}

function is_today(string $utc, string $timezone): bool
{
    $local = utc_to_local($utc, $timezone);

    return $local?->format('Y-m-d') === (new DateTimeImmutable('now', tz($timezone)))->format('Y-m-d');
}

function seats_label(?int $left, mixed $capacity): string
{
    if ($capacity === null) {
        return 'Open RSVP';
    }

    if ($left === null) {
        return 'Limited seats';
    }

    if ($left <= 0) {
        return 'Waitlist only';
    }

    return $left.' '.($left === 1 ? 'seat' : 'seats').' left';
}

/**
 * Minimal branded HTML shell shared by transactional emails.
 */
function email_shell(string $heading, string $body, ?string $ctaUrl = null, ?string $ctaLabel = null, string $footnote = ''): string
{
    $app = e((string) env('APP_NAME', 'Gather'));
    $cta = '';

    if ($ctaUrl !== null && $ctaLabel !== null) {
        $cta = '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0 8px"><tr><td style="border-radius:999px;background:#1c1812">'
            .'<a href="'.e($ctaUrl).'" style="display:inline-block;padding:14px 26px;color:#fdfaf4;font-family:Instrument Sans,Arial,sans-serif;font-size:14px;font-weight:600;text-decoration:none">'.e($ctaLabel).'</a>'
            .'</td></tr></table>';
    }

    return '<!doctype html><html><body style="margin:0;padding:32px 16px;background:#f7f0e4">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px">'
        .'<tr><td style="padding:0 4px 18px"><span style="font-family:Georgia,serif;font-size:20px;font-weight:700;color:#1c1812">'.$app.'</span></td></tr>'
        .'<tr><td style="background:#fffdf9;border:1px solid #ecdfca;border-radius:20px;padding:34px 30px">'
        .'<h1 style="margin:0 0 14px;font-family:Georgia,serif;font-size:26px;line-height:1.2;color:#1c1812">'.e($heading).'</h1>'
        .'<div style="font-family:Instrument Sans,Arial,sans-serif;font-size:15px;line-height:1.65;color:#3b342a">'.$body.'</div>'
        .$cta
        .'</td></tr>'
        .'<tr><td style="padding:18px 6px 0;font-family:Instrument Sans,Arial,sans-serif;font-size:12px;line-height:1.6;color:#8d8375">'
        .($footnote !== '' ? e($footnote).'<br>' : '')
        .'You received this email because you registered for an event on '.$app.'.'
        .'</td></tr></table></td></tr></table></body></html>';
}
