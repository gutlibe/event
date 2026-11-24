<?php

declare(strict_types=1);

namespace App;

final class Reference
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function make(): string
    {
        $random = '';

        for ($i = 0; $i < 6; $i++) {
            $random .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return 'EVT-'.$random;
    }

    public static function unique(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $reference = self::make();

            if (! Database::first('SELECT id FROM registrations WHERE reference = ?', [$reference])) {
                return $reference;
            }
        }

        throw new \RuntimeException('Could not generate a unique registration reference.');
    }
}
