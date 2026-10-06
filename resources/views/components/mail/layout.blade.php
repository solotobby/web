<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? 'FanVault' }}</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #fbfaf6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0f172a; line-height: 1.5; }
        @media only screen and (max-width: 620px) {
            .wrapper { width: 100% !important; padding: 12px !important; }
            .card { padding: 24px 18px !important; border-radius: 16px !important; }
            .badge-table { width: 100% !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #fbfaf6;">
    @isset($preheader)
    <!-- Hidden Preheader -->
    <div style="display: none; font-size: 1px; color: #fbfaf6; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
        {{ $preheader }}
        &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy;
    </div>
    @endisset

    <!-- Outer Wrapper -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fbfaf6; min-height: 100vh;">
        <tr>
            <td align="center" style="padding: 32px 12px 48px 12px;">
                <!-- Main Container -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px;" class="wrapper">
                    <!-- Brand Masthead Header -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-right: 12px;">
                                        <div style="width: 38px; height: 38px; background-color: #064e3b; border-radius: 10px; text-align: center; line-height: 38px; color: #ffffff; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 15px; font-weight: 700; letter-spacing: -0.5px;">
                                            FV
                                        </div>
                                    </td>
                                    <td align="left">
                                        <div style="font-family: Georgia, Cambria, 'Times New Roman', Times, serif; font-size: 22px; font-weight: 700; color: #0f172a; letter-spacing: -0.5px; line-height: 1.1;">
                                            FanVault
                                        </div>
                                        <div style="font-size: 11px; color: #64748b; font-weight: 500; margin-top: 2px;">
                                            Creator Milestone Capsules & Fan Mail
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Email Content Card -->
                    <tr>
                        <td>
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="card" style="background-color: #ffffff; border: 1px solid #e7e5df; border-radius: 20px; padding: 36px 32px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);">
                                <tr>
                                    <td>
                                        {{ $slot }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding-top: 28px; padding-bottom: 16px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 10px;">
                                        <span style="display: inline-block; padding: 4px 12px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 9999px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 600; color: #064e3b;">
                                            ● Encrypted Time Capsule Protocol v2.4
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="font-size: 12px; color: #64748b; line-height: 1.6;">
                                        FanVault · Permanent Community Keepsakes for Creators<br>
                                        <a href="https://getfanvault.com" style="color: #047857; text-decoration: none; font-weight: 600;">getfanvault.com</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top: 12px; font-size: 11px; color: #94a3b8; font-family: ui-monospace, SFMono-Regular, monospace;">
                                        Zero Physical Clutter · Direct Stripe Payouts · Stream Reveals
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
