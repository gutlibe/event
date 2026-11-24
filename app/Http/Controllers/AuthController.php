<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Auth;
use App\Database;
use App\Mailer;
use App\Models\PasswordReset;
use App\Models\User;
use App\RateLimiter;
use App\ValidationException;
use App\Validator;

final class AuthController
{
    public function showRegister(): void
    {
        view('auth/register', ['title' => 'Create your account'], 'layouts/auth');
    }

    public function register(): void
    {
        if (RateLimiter::tooMany('register:'.RateLimiter::ip(), 10, 60)) {
            flash_set('error', 'Too many attempts. Please wait a minute and try again.');
            redirect('/register');
        }

        $data = Validator::validate($_POST, [
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'password' => 'required|min:8|confirmed',
        ]);

        if (User::findByEmail((string) $data['email']) !== null) {
            throw new ValidationException(['email' => 'An account with this email already exists.'], $_POST);
        }

        $id = User::create((string) $data['name'], (string) $data['email'], (string) $data['password']);
        $user = User::find($id);

        if ($user !== null) {
            Auth::login($user);
        }

        flash_set('success', 'Welcome to Gather. Create your first event.');
        redirect('/dashboard');
    }

    public function showLogin(): void
    {
        view('auth/login', ['title' => 'Sign in'], 'layouts/auth');
    }

    public function login(): void
    {
        $data = Validator::validate($_POST, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $key = 'login:'.mb_strtolower((string) $data['email']).':'.RateLimiter::ip();

        if (RateLimiter::tooMany($key, 5, 60)) {
            throw new ValidationException(['email' => 'Too many attempts. Please wait a minute.'], ['email' => $data['email']]);
        }

        if (! Auth::attempt((string) $data['email'], (string) $data['password'])) {
            throw new ValidationException(['email' => 'These credentials do not match our records.'], ['email' => $data['email']]);
        }

        RateLimiter::clear($key);
        redirect(Auth::intended());
    }

    public function logout(): void
    {
        Auth::logout();
        flash_set('success', 'You have been signed out.');
        redirect('/');
    }

    public function showForgot(): void
    {
        view('auth/forgot', ['title' => 'Reset your password'], 'layouts/auth');
    }

    public function sendReset(): void
    {
        $data = Validator::validate($_POST, ['email' => 'required|email']);
        $user = User::findByEmail((string) $data['email']);

        if ($user !== null) {
            $token = PasswordReset::createFor((string) $user['email']);
            $link = url('/reset-password?token='.urlencode($token).'&email='.urlencode((string) $user['email']));

            Mailer::send(
                (string) $user['email'],
                'Reset your Gather password',
                \render('emails.reset', ['user' => $user, 'link' => $link])
            );
        }

        flash_set('success', 'If that email is registered, a reset link is on its way.');
        redirect('/forgot-password');
    }

    public function showReset(): void
    {
        $email = (string) ($_GET['email'] ?? '');
        $token = (string) ($_GET['token'] ?? '');

        view('auth/reset', [
            'title' => 'Choose a new password',
            'email' => $email,
            'token' => $token,
        ], 'layouts/auth');
    }

    public function reset(): void
    {
        $data = Validator::validate($_POST, [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $reset = PasswordReset::find((string) $data['email'], (string) $data['token']);

        if ($reset === null) {
            throw new ValidationException(['token' => 'This reset link is invalid or has expired.'], $_POST);
        }

        $user = User::findByEmail((string) $data['email']);

        if ($user === null) {
            throw new ValidationException(['email' => 'We could not find that account.'], $_POST);
        }

        User::updatePassword((int) $user['id'], (string) $data['password']);
        PasswordReset::delete((string) $data['email']);

        flash_set('success', 'Your password has been updated. Sign in with the new one.');
        redirect('/login');
    }
}
