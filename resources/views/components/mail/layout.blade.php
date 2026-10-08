<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject ?? 'FanVault' }}</title>
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <style type="text/css">
        /* RESET & BASE CLIENT STYLES */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f4f5f7 !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0f172a; line-height: 1.6; }

        /* OUTLOOK TIMES NEW ROMAN FIX */
        body, table, td, p, a, li, blockquote {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
        }

        /* IOS & GMAIL LINK COLOR PRESERVATION */
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }
        u + #body a { color: inherit; text-decoration: none; font-size: inherit; font-family: inherit; font-weight: inherit; line-height: inherit; }
        #MessageViewBody a { color: inherit; text-decoration: none; font-size: inherit; font-family: inherit; font-weight: inherit; line-height: inherit; }

        /* RESPONSIVE BREAKPOINT FOR MOBILE CLIENTS */
        @media only screen and (max-width: 620px) {
            .mobile-shell { padding: 20px 10px 36px 10px !important; }
            .mobile-card-cell { padding: 28px 18px !important; border-radius: 12px !important; }
            .mobile-full-width { width: 100% !important; max-width: 100% !important; min-width: 100% !important; }
            .mobile-center { text-align: center !important; }
            .mobile-btn { display: block !important; width: 100% !important; padding: 14px 10px !important; }
        }
    </style>
</head>
<body id="body" style="margin: 0; padding: 0; background-color: #f4f5f7; width: 100% !important; -webkit-font-smoothing: antialiased;">
    @isset($preheader)
    <!-- Bulletproof Hidden Preheader -->
    <div style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; max-height: 0; max-width: 0; overflow: hidden; mso-hide: all; font-size: 0px; line-height: 0px;">
        {{ $preheader }}
        &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy;
    </div>
    @endisset

    <!-- Outer Canvas Wrapper Table -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f4f5f7" style="background-color: #f4f5f7; width: 100%; min-width: 100%; margin: 0; padding: 0;">
        <tr>
            <td align="center" valign="top" class="mobile-shell" style="padding: 40px 16px 52px 16px; vertical-align: top;">
                <!--[if (gte mso 9)|(IE)]>
                <table role="presentation" width="580" align="center" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td align="center" valign="top">
                <![endif]-->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="mobile-full-width" style="max-width: 580px; width: 100%; margin: 0 auto;">
                    <!-- Brand Header (Centered FV Lockup) -->
                    <tr>
                        <td align="center" valign="top" style="padding-bottom: 24px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td align="center" valign="middle" style="padding-right: 10px;">
                                        <!-- Solid Table Badge (Never Distorts) -->
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="38" height="38" bgcolor="#059669" style="width: 38px; height: 38px; background-color: #059669; border-radius: 10px; border-collapse: separate; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);">
                                            <tr>
                                                <td align="center" valign="middle" style="color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 16px; font-weight: 800; line-height: 38px;">
                                                    FV
                                                </td>
                                            </tr>
                                        </table>
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

                    <!-- Email Main Card (White Box with 16px Rounded Corners & Safe Padding on TD) -->
                    <tr>
                        <td align="left" valign="top">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#ffffff" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; border-collapse: separate; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);">
                                <tr>
                                    <td align="left" valign="top" class="mobile-card-cell" style="padding: 40px 36px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.65; color: #334155;">
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
                                            &copy; {{ date('Y') }} FanVault Inc. All rights reserved.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <!--[if (gte mso 9)|(IE)]>
                        </td>
                    </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
