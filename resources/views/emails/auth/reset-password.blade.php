<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $store }} — Reset your password</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style>
        table, td { border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    </style>
    <![endif]-->
    <style>
        body { margin: 0; padding: 0; width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { color: #6F8736; }
        .btn a { display: inline-block; text-decoration: none; }
    </style>
</head>
<body style="margin:0; padding:0; width:100%; background-color:#F7F4EA; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; font-family: Inter, 'Segoe UI', Arial, Helvetica, sans-serif;">
    <span style="display:none !important; visibility:hidden; mso-hide:all; font-size:1px; color:#F7F4EA; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        Password reset requested for your {{ $store }} account — the link expires in {{ $expireMinutes }} minutes.
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
                            <h1 style="margin:10px 0 0 0; font-size:26px; line-height:1.3; color:#F7F4EA; font-weight:700;">Reset Your Password</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 40px 20px 40px;">

                            @if (!empty($name))
                                <p style="margin:0 0 16px 0; font-size:16px; line-height:1.6; color:#28322A;">Hi {{ $name }},</p>
                            @endif

                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.7; color:#28322A;">
                                We received a request to reset the password for your
                                <strong>{{ $store }}</strong> account
                                @if (!empty($email)) associated with <strong style="color:#6F8736;">{{ $email }}</strong> @endif.
                            </p>

                            <p style="margin:0 0 24px 0; font-size:15px; line-height:1.7; color:#28322A;">
                                If this was you, simply click the button below to choose a new password. If you didn't make this request, you can safely ignore this email.
                            </p>

                            <!-- Button -->
                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:0 0 24px 0;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn">
                                            <tr>
                                                <td align="center" style="border-radius:999px; background-color:#B58A3A;">
                                                    <a href="{{ $url }}" target="_blank" rel="noopener"
                                                       style="display:inline-block; padding:15px 44px; border-radius:999px; background-color:#B58A3A; color:#FFFFFF !important; font-size:15px; font-weight:600; letter-spacing:0.4px; text-decoration:none;">
                                                        Reset My Password
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiry callout -->
                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#EEF2E3; border-radius:14px; margin:0 0 24px 0;">
                                <tr>
                                    <td style="padding:18px 22px; font-size:13px; line-height:1.6; color:#28322A;">
                                        <strong style="color:#263D25;">&#9200; This link expires in {{ $expireMinutes }} minutes.</strong><br>
                                        For security, reset links can only be used once. If it expires, simply request a new one from the sign-in page.
                                    </td>
                                </tr>
                            </table>

                            <!-- Plain text fallback -->
                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#F7F4EA; border-radius:14px; margin:0 0 24px 0;">
                                <tr>
                                    <td style="padding:18px 22px;">
                                        <p style="margin:0 0 8px 0; font-size:12px; color:#6B736B;">Button not working? Copy and paste this link into your browser:</p>
                                        <p style="margin:0; font-size:12px; line-height:1.6; color:#263D25; word-break:break-all;">
                                            <a href="{{ $url }}" target="_blank" rel="noopener" style="color:#263D25; text-decoration:underline;">{{ $url }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security note -->
                            <p style="margin:0 0 8px 0; font-size:13px; line-height:1.7; color:#6B736B;">
                                &#128274; Never share this link or your password with anyone. {{ $store }} will never ask for your password by email or phone.
                            </p>
                            <p style="margin:0; font-size:13px; line-height:1.7; color:#6B736B;">
                                If you didn't request a password reset, please <a href="{{ $loginUrl }}" style="color:#6F8736; font-weight:600; text-decoration:underline;">sign in</a> and review your account settings.
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
                            You are receiving this email because a password reset was requested for this account.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>