@extends('emails.layout')

@section('content')

<h2 style="margin:0 0 20px;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:700;color:#111827;line-height:1.3;">
    Reset your password
</h2>

<p style="margin:0 0 10px;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#374151;line-height:1.6;">
    Hi {{ $name }},
</p>

<p style="margin:0 0 32px;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#374151;line-height:1.6;">
    We received a request to reset the password for your <strong>{{ $appName }}</strong> account.
    Click the button below to set a new password.
</p>

{{-- CTA — Outlook (MSO) uses table+bgcolor for reliable button rendering --}}
<!--[if mso]>
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:32px;"><tr>
<td align="center"><table role="presentation" cellpadding="0" cellspacing="0"><tr>
<td bgcolor="#2254DD" style="border-radius:6px;padding:14px 36px;">
<a href="{{ $resetUrl }}" target="_blank"
   style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;text-decoration:none;display:inline-block;white-space:nowrap;">
    Reset My Password
</a>
</td></tr></table></td></tr></table>
<![endif]-->
<!--[if !mso]><!-->
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:32px;">
    <tr>
        <td align="center">
            <a href="{{ $resetUrl }}" target="_blank"
               style="background-color:#2254DD;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;text-decoration:none;padding:8px 50px;border-radius:6px;display:inline-block;white-space:nowrap;">
                Reset My Password
            </a>
        </td>
    </tr>
</table>
<!--<![endif]-->

<p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#9ca3af;">
    Or copy this link into your browser:
</p>
<p style="margin:0 0 28px;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#d1d5db;word-break:break-all;line-height:1.5;">
    {{ $resetUrl }}
</p>

<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#9ca3af;line-height:1.6;">
    This link expires in <strong style="color:#6b7280;">1 hour</strong>.
    If you didn&rsquo;t request a password reset, you can safely ignore this email &mdash; your password won&rsquo;t change.
</p>

@endsection

@section('footer_note')
    You received this email because a password reset was requested for your {{ $appName }} account.<br>
    This is a transactional email.
@endsection
