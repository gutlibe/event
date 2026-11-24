<?php
$start = utc_to_local($event['starts_at'], $event['timezone']);
$end = utc_to_local($event['ends_at'], $event['timezone']);

$body = '<p>Hi '.e($registration['name']).',</p>'
    .'<p>A reminder that <strong>'.e($event['title']).'</strong> is tomorrow. Here is what you need:</p>'
    .'<table role="presentation" cellpadding="0" cellspacing="0" style="margin:18px 0;width:100%;background:#f7f0e4;border-radius:12px">'
    .'<tr><td style="padding:16px 18px;font-family:Instrument Sans,Arial,sans-serif;font-size:14px;line-height:1.7;color:#3b342a">'
    .'<strong>When</strong><br>'.e($start?->format('l, j F Y · g:i A') ?? '').($end ? ' – '.e($end->format('g:i A')) : '').' ('.e($event['timezone']).')<br><br>'
    .'<strong>Where</strong><br>'.e($event['venue_name'] ?: 'Venue to be announced')
    .(! empty($event['venue_address']) ? '<br>'.e($event['venue_address']) : '')
    .'<br><br><strong>Your reference</strong><br><span style="font-family:ui-monospace,monospace;letter-spacing:.12em">'.e($registration['reference']).'</span>'
    .'</td></tr></table>'
    .'<p style="font-size:13px;color:#6d6457">Bring your reference if you have it, but we can also find you by name at the door.</p>';

echo email_shell(
    'See you tomorrow',
    $body,
    url('/e/'.$event['slug'].'/registered/'.$registration['reference']),
    'View your ticket',
    'An .ics calendar file is attached.'
);
