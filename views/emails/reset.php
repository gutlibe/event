<?php
$body = '<p>Hi '.e($user['name']).',</p>'
    .'<p>Someone asked to reset the password for your '.e(env('APP_NAME', 'Gather')).' account. If that was you, use the button below.</p>'
    .'<p style="font-size:13px;color:#6d6457">This link expires in one hour. If you did not ask for a reset, you can ignore this email — your password will not change.</p>';

echo email_shell('Reset your password', $body, $link, 'Choose a new password');
