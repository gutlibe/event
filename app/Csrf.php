<?php

declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        return (string) ($_SESSION['_csrf'] ?? '');
    }

    public static function verify(): bool
    {
        $token = (string) ($_POST['_token'] ?? '');
        $session = self::token();

        return $session !== '' && $token !== '' && hash_equals($session, $token);
    }
}
