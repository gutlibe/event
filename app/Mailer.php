<?php

declare(strict_types=1);

namespace App;

final class Mailer
{
    /**
     * Deliver an email. With MAIL_MAILER=log (the local default) the full
     * message is appended to storage/logs/mail.log instead of being sent.
     */
    public static function send(string $to, string $subject, string $html, array $attachments = []): void
    {
        if (env('MAIL_MAILER', 'log') !== 'log') {
            return;
        }

        $fromAddress = (string) env('MAIL_FROM_ADDRESS', 'hello@event.test');
        $fromName = (string) env('MAIL_FROM_NAME', env('APP_NAME', 'Gather'));
        $boundary = 'gather_'.bin2hex(random_bytes(12));

        $headers = [
            'From: '.self::encodeName($fromName).' <'.$fromAddress.'>',
            'To: <'.$to.'>',
            'Subject: '.self::encodeHeader($subject),
            'Date: '.gmdate('D, d M Y H:i:s').' +0000',
            'Message-ID: <'.bin2hex(random_bytes(10)).'@'.parse_url((string) env('APP_URL', 'http://event.test'), PHP_URL_HOST).'>',
            'MIME-Version: 1.0',
        ];

        if ($attachments === []) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: quoted-printable';
            $body = quoted_printable_encode($html);
        } else {
            $headers[] = 'Content-Type: multipart/mixed; boundary="'.$boundary.'"';

            $body = '--'.$boundary."\r\n"
                ."Content-Type: text/html; charset=UTF-8\r\n"
                ."Content-Transfer-Encoding: quoted-printable\r\n\r\n"
                .quoted_printable_encode($html)."\r\n";

            foreach ($attachments as $attachment) {
                $filename = $attachment['name'] ?? 'attachment';
                $mime = $attachment['mime'] ?? 'application/octet-stream';
                $body .= '--'.$boundary."\r\n"
                    .'Content-Type: '.$mime.'; name="'.$filename."\"\r\n"
                    ."Content-Transfer-Encoding: base64\r\n"
                    .'Content-Disposition: attachment; filename="'.$filename."\"\r\n\r\n"
                    .chunk_split(base64_encode((string) $attachment['content']))."\r\n";
            }

            $body .= '--'.$boundary."--\r\n";
        }

        $message = '['.gmdate('Y-m-d H:i:s').' UTC] '.$subject.' -> '.$to."\n"
            .implode("\n", $headers)."\n\n".$body;

        $log = storage_path('logs/mail.log');

        if (! is_dir(dirname($log))) {
            mkdir(dirname($log), 0775, true);
        }

        file_put_contents($log, $message."\n\n".str_repeat('=', 78)."\n\n", FILE_APPEND | LOCK_EX);
    }

    private static function encodeHeader(string $value): string
    {
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    private static function encodeName(string $name): string
    {
        return mb_encode_mimeheader($name, 'UTF-8', 'B', "\r\n");
    }
}
