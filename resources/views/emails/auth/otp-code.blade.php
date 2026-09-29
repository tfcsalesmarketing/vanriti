<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $store }} — Your verification code</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        table, td { border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    </style>
    <style>
        body { margin: 0; padding: 0; width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { color: #6F8736; }
        .code-cell { letter-spacing: 10px; }
    </style>
</head>
<body style="margin:0; padding:0; width:100%; background-color:#F7F4EA; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; font-family: Inter, 'Segoe UI', Arial, Helvetica, sans-serif;">
    <span style="display:none !important; visibility:hidden; mso-hide:all; font-size:1px; color:#F7F4EA; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        Your {{ $store }} verification code is {{ $code }}. It expires in {{ $expireMinutes }} minutes.
    </span>

    <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#F7F4EA;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <!-- Masthead -->
                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width:600px; margin:0 auto;">
                    <tr>
                        <td align="center" style="padding-bottom:24px;">
                            <img src="{{ $logo }}" alt="{{ $store }}" width="160" height="auto" style="display:inline-block; width:160px; max-width:160px; height:auto; border:0; outline:none; text-decoration:none;">
                        </td>
                    </tr>
                </table>

                <!-- Main Card -->
                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width:600px; margin:0 auto; background-color:#FFFFFF; border-radius:18px; overflow:hidden; box-shadow:0 12px 40px rgba(38,61,37,0.08);">
                    <tr>
                        <td align="center" style="background-color:#263D25; padding:36px 40px 30px 40px;">
                            <div style="font-size:11px; letter-spacing:3px; color:#B58A3A; font-weight:600; text-transform:uppercase;">Pure By Nature</div>
                            <h1 style="margin:10px 0 0 0; font-size:26px; line-height:1.3; color:#F7F4EA; font-weight:700;">
                                {{ $reset ? 'Reset Your Password' : 'Your Verification Code' }}
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 40px 20px 40px;">

                            <p style="margin:0 0 8px 0; font-size:15px; line-height:1.7; color:#28322A;">
                                @if ($reset)
                                    We received a request to reset the password for your <strong>{{ $store }}</strong> account. Enter this code to choose a new password.
                                @else
                                    Use the code below to verify your <strong>{{ $store }}</strong> account.
                                @endif
                            </p>

                            <!-- Code -->
                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:0 0 24px 0;">
                                <tr>
                                    <td align="center" style="background-color:#EEF2E3; border-radius:14px; padding:26px 20px;">
                                        <div class="code-cell" style="font-size:38px; line-height:1.2; font-weight:700; color:#263D25; letter-spacing:10px; text-indent:10px;">
                                            {{ $code }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiry callout -->
                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#EEF2E3; border-radius:14px; margin:0 0 24px 0;">
                                <tr>
                                    <td style="padding:18px 22px; font-size:13px; line-height:1.6; color:#28322A;">
                                        <strong style="color:#263D25;">&#9200; This code expires in {{ $expireMinutes }} minutes.</strong><br>
                                        For your security it can only be used once. If it expires, simply request a new one.
                                    </td>
                                </tr>
                            </table>

                            <!-- Security note -->
                            <p style="margin:0 0 8px 0; font-size:13px; line-height:1.7; color:#6B736B;">
                                &#128274; {{ $store }} will never ask you for this code, your password, or your card details by email. Never share it with anyone.
                            </p>
                            <p style="margin:0; font-size:13px; line-height:1.7; color:#6B736B;">
                                If you didn't request this, you can safely ignore this email. You can also
                                <a href="{{ $requestUrl }}" style="color:#6F8736; font-weight:600; text-decoration:underline;">request a new code</a>
                                or <a href="{{ $loginUrl }}" style="color:#6F8736; font-weight:600; text-decoration:underline;">sign in</a>.
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td height="1" style="background-color:#E6E2D6;"><div style="height:1px; line-height:1px; font-size:1px;">&nbsp;</div></td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding:28px 40px 36px 40px;">
                            <div style="font-size:12px; letter-spacing:2px; text-transform:uppercase; color:#263D25; font-weight:700; margin-bottom:10px;">{{ strtoupper($store) }}</div>
                            <div style="font-size:13px; line-height:1.7; color:#6B736B;">
                                Pure by nature &middot; crafted with care for mind, body &amp; soul.<br>
                                Questions? Email us at
                                <a href="mailto:{{ $supportEmail }}" style="color:#6F8736; font-weight:600; text-decoration:none;">{{ $supportEmail }}</a>
                            </div>
                            <div style="font-size:12px; color:#6B736B; margin-top:14px;">&copy; {{ date('Y') }} {{ $store }}. All rights reserved.</div>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width:600px; margin:0 auto;">
                    <tr>
                        <td align="center" style="padding:20px 16px 0 16px; font-size:11px; line-height:1.6; color:#6B736B;">
                            You are receiving this email because a verification was requested for this account.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
