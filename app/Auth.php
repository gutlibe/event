<?php

declare(strict_types=1);

namespace App;

use App\Models\User;

final class Auth
{
    private static ?array $user = null;

    public static function boot(): void
    {
        if (empty($_SESSION['user_id'])) {
            return;
        }

        self::$user = User::find((int) $_SESSION['user_id']);

        if (self::$user === null) {
            unset($_SESSION['user_id']);
        }
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function check(): bool
    {
        return self::$user !== null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if ($user === null || ! password_verify($password, (string) $user['password'])) {
            return false;
        }

        self::login($user);

        return true;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        self::$user = null;

        session_regenerate_id(true);
        unset($_SESSION['_csrf']);
    }

    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }

        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';
        flash_set('error', 'Please sign in to continue.');
        redirect('/login');
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            redirect('/dashboard');
        }
    }

    public static function intended(string $default = '/dashboard'): string
    {
        $intended = $_SESSION['_intended'] ?? $default;
        unset($_SESSION['_intended']);

        return is_string($intended) && str_starts_with($intended, '/') ? $intended : $default;
    }
}
