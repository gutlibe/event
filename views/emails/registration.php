<?php
$start = utc_to_local($event['starts_at'], $event['timezone']);
$end = utc_to_local($event['ends_at'], $event['timezone']);
$promoted = $promoted ?? false;
$waitlistPosition = $waitlistPosition ?? null;

$heading = $confirmed
    ? ($promoted ? 'A seat opened up' : "You're in")
    : "You're on the waitlist";

$body = '<p>Hi '.e($registration['name']).',</p>';

if ($confirmed) {
    $body .= $promoted
        ? '<p>Good news — a spot opened up and we moved you off the waitlist. Your place at <strong>'.e($event['title']).'</strong> is confirmed.</p>'
        : '<p>Your RSVP for <strong>'.e($event['title']).'</strong> is confirmed. Here are the details:</p>';
} else {
    $body .= '<p>You are on the waitlist for <strong>'.e($event['title']).'</strong>.';
    if ($waitlistPosition !== null) {
        $body .= ' You are number <strong>'.$waitlistPosition.'</strong> in line.';
    }
    $body .= ' We will email you automatically if a seat opens up.</p>';
}

$body .= '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:18px 0;width:100%;background:#f7f0e4;border-radius:12px">'
    .'<tr><td style="padding:16px 18px;font-family:Instrument Sans,Arial,sans-serif;font-size:14px;line-height:1.7;color:#3b342a">'
    .'<strong>When</strong><br>'.e($start?->format('l, j F Y · g:i A') ?? '').($end ? ' – '.e($end->format('g:i A')) : '').' ('.e($event['timezone']).')<br><br>'
    .'<strong>Where</strong><br>'.e($event['venue_name'] ?: 'Venue to be announced')
    .(! empty($event['venue_address']) ? '<br>'.e($event['venue_address']) : '')
    .'<br><br><strong>Reference</strong><br><span style="font-family:ui-monospace,monospace;letter-spacing:.12em">'.e($registration['reference']).'</span>'
    .'</td></tr></table>';

if (! $confirmed) {
    $body .= '<p style="font-size:13px;color:#6d6457">No action needed for now. We will be in touch if your status changes.</p>';
}

$body .= '<p style="margin-top:20px">'.($confirmed ? 'See you there!' : 'Fingers crossed,').'<br>'.e(env('APP_NAME', 'Gather')).'</p>';

echo email_shell(
    $heading,
    $body,
    url('/e/'.$event['slug'].'/registered/'.$registration['reference']),
    'View your ticket',
    'An .ics calendar file is attached.'
);
