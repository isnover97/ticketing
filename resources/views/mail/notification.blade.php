<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $notification->subject }}</title></head>
<body style="margin:0;padding:0;background:#ffffff;color:#3d485c;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#ffffff;">
<tr><td style="padding:18px 16px 0;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr><td style="padding:0 0 10px;color:#171717;font-size:24px;line-height:30px;font-weight:600;">PASCAL RESOURCES ENERGY INC.</td></tr>
<tr><td><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td width="50%" height="8" bgcolor="#4bc3e5" style="height:8px;font-size:0;line-height:0;">&nbsp;</td><td width="50%" height="8" bgcolor="#ff0000" style="height:8px;font-size:0;line-height:0;">&nbsp;</td></tr></table></td></tr>
<tr><td style="padding:11px 0 19px;color:#666666;font-size:18px;line-height:24px;">{{ $notification->ticket ? 'IT Support Notification' : 'Account Notification' }}</td></tr>
</table>
</td></tr>
<tr><td style="border-top:1px solid #eeeeee;padding:26px 16px 18px;font-size:20px;line-height:30px;">
<?php foreach (preg_split('/\R\s*\R/', trim($notification->body)) as $paragraph): ?>
<p style="margin:0 0 20px;"><?php foreach (preg_split('~(https?://[^\s<>]+)~', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE) as $part): ?>@if(filter_var($part, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($part, PHP_URL_SCHEME)), ['http', 'https'], true))<a href="{{ $part }}" style="color:#0079b8;text-decoration:underline;overflow-wrap:anywhere;">{{ $part }}</a>@else{!! nl2br(e($part)) !!}@endif<?php endforeach; ?></p>
<?php endforeach; ?>
@if($notification->ticket)
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:8px;border:1px solid #dfe5ec;background:#f0f7ff;">
<tr><td style="border-left:5px solid #4bc3e5;padding:18px 21px;color:#4b5c76;font-size:16px;line-height:26px;">
<p style="margin:0 0 12px;">Sign in to view your ticket, follow its progress, or reply to the support team.</p>
<p style="margin:0 0 10px;"><strong>Ticket:</strong> {{ $notification->ticket->ticket_number }}</p>
<p style="margin:0 0 10px;"><strong>Subject:</strong> {{ $notification->ticket->title }}</p>
<p style="margin:0;"><strong>Ticket link - </strong><a href="{{ \App\Support\HelpdeskEmailUrl::route('helpdesk.ticket', $notification->ticket) }}" style="color:#0079b8;text-decoration:underline;overflow-wrap:anywhere;">View ticket</a></p>
</td></tr></table>
@endif
</td></tr></table>
</body></html>

