<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $appName }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        body { margin: 0; padding: 0; background-color: #f0f2f5; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        td { padding: 0; }
        img { border: 0; display: block; max-width: 100%; -ms-interpolation-mode: bicubic; }
        @media only screen and (max-width: 620px) {
            .email-card { padding: 32px 24px !important; }
            .email-outer { padding: 32px 12px 28px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f0f2f5;">

    {{-- Preheader: hidden text shown in inbox preview line --}}
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;color:#f0f2f5;">
        {{ $preheader }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f0f2f5">
        <tr>
            <td class="email-outer" align="center" style="padding:48px 16px 40px;">

                <!--[if mso]>
                <table role="presentation" width="600" cellpadding="0" cellspacing="0"><tr><td>
                <![endif]-->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                    {{-- Logo --}}
                    <tr>
                        <td align="center" style="padding-bottom:28px;">
                            <a href="{{ rtrim(config('app.url'), '/') }}" target="_blank" style="display:inline-block;text-decoration:none;">
                                <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="auto" height="38" style="height:38px;max-width:180px;" />
                            </a>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td class="email-card" style="background:#ffffff;border-radius:10px;padding:44px 48px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding-top:28px;">
                            <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#aaaaaa;">
                                &copy; {{ date('Y') }} {{ $appName }}
                            </p>
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#bbbbbb;line-height:1.7;">
                                @yield('footer_note')
                            </p>
                        </td>
                    </tr>

                </table>
                <!--[if mso]>
                </td></tr></table>
                <![endif]-->

            </td>
        </tr>
    </table>

</body>
</html>
