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
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f4f5f7 !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #111827; line-height: 1.6; }
        @media only screen and (max-width: 620px) {
            .mobile-shell { padding: 16px 10px 32px 10px !important; }
            .wrapper { width: 100% !important; max-width: 100% !important; }
            .card { padding: 24px 20px !important; border-radius: 8px !important; }
            .column-step { width: 100% !important; display: block !important; margin-bottom: 12px !important; }
            .step-divider { display: none !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; -webkit-font-smoothing: antialiased;">
    @isset($preheader)
    <!-- Bulletproof Hidden Preheader -->
    <div style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; max-height: 0; max-width: 0; overflow: hidden; mso-hide: all; font-size: 0px; line-height: 0px;">
        {{ $preheader }}
        &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy;
    </div>
    @endisset

    <!-- Outer Canvas Table (Paystack Style Soft Gray Background) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f5f7; width: 100%; margin: 0; padding: 0; border-collapse: collapse;">
        <tr>
            <td align="center" valign="top" class="mobile-shell" style="padding: 36px 12px 48px 12px; vertical-align: top;">
                <!-- Main Container (580px Standard) -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; margin: 0 auto;" class="wrapper">
                    <!-- Clean Minimalist Header (Paystack Brand Mark) -->
                    <tr>
                        <td align="left" valign="top" style="padding-bottom: 20px; padding-left: 4px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left" valign="middle" style="padding-right: 12px;">
                                        <div style="width: 34px; height: 34px; background-color: #059669; border-radius: 8px; text-align: center; line-height: 34px; color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; font-weight: 700; letter-spacing: -0.5px;">
                                            FV
                                        </div>
                                    </td>
                                    <td align="left" valign="middle">
                                        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 18px; font-weight: 700; color: #111827; letter-spacing: -0.3px; line-height: 1.2;">
                                            FanVault
                                        </div>
                                        <div style="font-size: 11px; color: #6b7280; font-weight: 500; margin-top: 1px;">
                                            The Creator Time Capsule
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Email Main Card (Paystack White Box with Crisp 8px Corners & Border) -->
                    <tr>
                        <td align="left" valign="top">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="card" style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 36px 32px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                                <tr>
                                    <td align="left" valign="top" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.6; color: #374151;">
                                        {{ $slot }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Minimalist Footer (Paystack Style Clean Lines) -->
                    <tr>
                        <td align="center" valign="top" style="padding-top: 24px; padding-bottom: 16px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" valign="top" style="font-size: 12px; color: #9ca3af; line-height: 1.6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                                        <div style="font-weight: 600; color: #6b7280; margin-bottom: 4px;">
                                            FanVault · Permanent Community Keepsakes for Creators
                                        </div>
                                        <div>
                                            <a href="https://getfanvault.com" style="color: #059669; text-decoration: none; font-weight: 600;">getfanvault.com</a>
                                            &nbsp;·&nbsp;
                                            <a href="mailto:support@getfanvault.com" style="color: #6b7280; text-decoration: none;">support@getfanvault.com</a>
                                        </div>
                                        <div style="margin-top: 8px; font-size: 11px; color: #9ca3af;">
                                            Zero Physical Clutter · Direct Stripe Payouts · Milestone Stream Reveals
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
