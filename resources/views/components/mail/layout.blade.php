<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? 'FanVault' }}</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
        td { vertical-align: top; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f8fafc !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0f172a; line-height: 1.6; }
        @media only screen and (max-width: 620px) {
            .mobile-shell { padding: 18px 12px 36px 12px !important; }
            .wrapper { width: 100% !important; max-width: 100% !important; }
            .card { padding: 28px 20px !important; border-radius: 12px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; -webkit-font-smoothing: antialiased;">
    @isset($preheader)
    <!-- Bulletproof Hidden Preheader -->
    <div style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; max-height: 0; max-width: 0; overflow: hidden; mso-hide: all; font-size: 0px; line-height: 0px;">
        {{ $preheader }}
        &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy;
    </div>
    @endisset

    <!-- Outer Canvas (Soft Modern Slate #f8fafc) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; width: 100%; margin: 0; padding: 0; border-collapse: collapse;">
        <tr>
            <td align="center" valign="top" class="mobile-shell" style="padding: 40px 16px 52px 16px; vertical-align: top;">
                <!-- Main Container (580px) -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; margin: 0 auto;" class="wrapper">
                    <!-- Centered Brand Header (Dribbble Clean Brand Mark) -->
                    <tr>
                        <td align="center" valign="top" style="padding-bottom: 24px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td align="center" valign="middle" style="padding-right: 10px;">
                                        <div style="width: 38px; height: 38px; background-color: #059669; border-radius: 10px; text-align: center; line-height: 38px; color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 16px; font-weight: 800; letter-spacing: -0.5px; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);">
                                            FV
                                        </div>
                                    </td>
                                    <td align="left" valign="middle">
                                        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; line-height: 1.1;">
                                            FanVault
                                        </div>
                                        <div style="font-size: 11px; color: #64748b; font-weight: 600; letter-spacing: 0.2px; margin-top: 2px;">
                                            The Creator Time Capsule
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Email Card (Dribbble White Box with 16px Rounded Corners & Soft Shadow) -->
                    <tr>
                        <td align="left" valign="top">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="card" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px 36px; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);">
                                <tr>
                                    <td align="left" valign="top" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.65; color: #334155;">
                                        {{ $slot }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Clean Dribbble-style Footer -->
                    <tr>
                        <td align="center" valign="top" style="padding-top: 28px; padding-bottom: 16px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" valign="top" style="font-size: 12px; color: #94a3b8; line-height: 1.65; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                                        <div style="font-weight: 600; color: #64748b; margin-bottom: 6px;">
                                            Have questions or need assistance? Reach out to <a href="mailto:support@getfanvault.com" style="color: #059669; text-decoration: none; font-weight: 600;">support@getfanvault.com</a>
                                        </div>
                                        <div style="color: #94a3b8; font-size: 11px;">
                                            <a href="https://getfanvault.com" style="color: #64748b; text-decoration: none; font-weight: 600;">FanVault</a>
                                            &nbsp;·&nbsp;
                                            <a href="https://getfanvault.com" style="color: #94a3b8; text-decoration: underline;">Privacy Policy</a>
                                            &nbsp;·&nbsp;
                                            <a href="https://getfanvault.com" style="color: #94a3b8; text-decoration: underline;">Terms of Service</a>
                                        </div>
                                        <div style="margin-top: 10px; font-size: 11px; color: #cbd5e1;">
                                            © {{ date('Y') }} FanVault Inc. All rights reserved.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
