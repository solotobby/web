<x-mail.layout>
    <x-slot:preheader>
        Private FanVault Time Capsule concept prepared for {{ $prospect->creator }} — a free idea for your next milestone.
    </x-slot:preheader>

    <!-- Top Badge Row -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 18px;">
        <tr>
            <td align="left" valign="middle">
                <span style="display: inline-block; padding: 4px 10px; background-color: #ecfdf5; border-radius: 4px; font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.5px;">
                    Creator Concept
                </span>
            </td>
            <td align="right" valign="middle">
                <span style="font-size: 11px; color: #6b7280; font-weight: 500;">
                    {{ $prospect->speciality }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Hero Headline -->
    <h1 style="margin: 0 0 14px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; line-height: 1.35; color: #111827; font-weight: 700; letter-spacing: -0.3px;">
        Turn Your Next Milestone Into A Live Community Reveal Stream
    </h1>

    <p style="margin: 0 0 20px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
        Hi {{ explode(' ', $prospect->creator)[0] }} — we put together a private FanVault time capsule concept specifically for <strong>{{ $prospect->creator }}</strong>.
    </p>

    <!-- Capsule Summary Card (Paystack Clean Light Box) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 16px 20px; border-bottom: 1px solid #f3f4f6;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td align="left" valign="middle">
                            <span style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.5px;">
                                🔒 Capsule No. #{{ sprintf('%03d', $prospect->prospect_number) }}
                            </span>
                            <div style="font-size: 15px; font-weight: 700; color: #111827; margin-top: 2px;">
                                {{ $prospect->creator }} Community Vault
                            </div>
                        </td>
                        <td align="right" valign="middle">
                            <span style="display: inline-block; padding: 3px 8px; background-color: #ecfdf5; border-radius: 4px; font-size: 10px; font-weight: 600; color: #059669; text-transform: uppercase;">
                                ● Ready to Activate
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 18px 20px;">
                <!-- 3-Step Flow in Paystack Clean Layout -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td width="30%" align="center" valign="top" style="padding: 4px;">
                            <div style="font-size: 20px; margin-bottom: 4px;">✍️</div>
                            <div style="font-size: 12px; font-weight: 700; color: #111827;">1. Fans Seal Today</div>
                            <div style="font-size: 11px; color: #6b7280; line-height: 1.4; margin-top: 2px;">Predictions, photos & notes</div>
                        </td>
                        <td width="5%" align="center" valign="middle" style="color: #9ca3af; font-size: 14px;">
                            ➔
                        </td>
                        <td width="30%" align="center" valign="top" style="padding: 4px;">
                            <div style="font-size: 20px; margin-bottom: 4px;">🔐</div>
                            <div style="font-size: 12px; font-weight: 700; color: #111827;">2. Stays Secret</div>
                            <div style="font-size: 11px; color: #6b7280; line-height: 1.4; margin-top: 2px;">Zero spoilers until unlock date</div>
                        </td>
                        <td width="5%" align="center" valign="middle" style="color: #9ca3af; font-size: 14px;">
                            ➔
                        </td>
                        <td width="30%" align="center" valign="top" style="padding: 4px;">
                            <div style="font-size: 20px; margin-bottom: 4px;">🎬</div>
                            <div style="font-size: 12px; font-weight: 700; color: #111827;">3. Reveal On Stream</div>
                            <div style="font-size: 11px; color: #6b7280; line-height: 1.4; margin-top: 2px;">Unboxed live for reactions</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Personalized Message Body -->
    <div style="font-size: 15px; line-height: 1.65; color: #374151; margin-bottom: 24px;">
        {!! nl2br(e($customBody)) !!}
    </div>

    <!-- Why Creators Love FanVault (Paystack Callout Style) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 26px;">
        <tr>
            <td style="padding: 16px 20px;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 700; margin-bottom: 10px;">
                    Why This Beats Another Generic Tweet / Merch Drop:
                </div>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="padding-bottom: 8px; font-size: 13px; color: #4b5563; line-height: 1.5;">
                            🎥 <strong style="color: #111827;">1–2 Hours of Stream Content:</strong> Live reactions reading fan letters and unboxing predictions on stream.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 8px; font-size: 13px; color: #4b5563; line-height: 1.5;">
                            💎 <strong style="color: #111827;">A Permanent Community Keepsake:</strong> Every letter is sealed and preserved as a lasting piece of your journey.
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 13px; color: #4b5563; line-height: 1.5;">
                            ⚡ <strong style="color: #111827;">100% Free & Zero Tech Work:</strong> We handle the custom setup, hosting, and design for you at zero cost.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Primary Action Button (Paystack Clean Button) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
        <tr>
            <td align="center">
                <a href="https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}" target="_blank" style="display: inline-block; padding: 13px 28px; background-color: #059669; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; border-radius: 6px; text-align: center;">
                    View {{ $prospect->creator }}'s Time Capsule Concept ➔
                </a>
                <div style="margin-top: 10px; font-size: 12px; color: #6b7280;">
                    Direct draft link: <a href="https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}" style="color: #059669; text-decoration: underline;">getfanvault.com/with/{{ Str::slug($prospect->creator) }}</a>
                </div>
            </td>
        </tr>
    </table>

    <!-- Founder Personal Signature & Reply Note -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top: 1px solid #f3f4f6; padding-top: 20px;">
        <tr>
            <td style="font-size: 14px; color: #4b5563; line-height: 1.5;">
                <div>Warm regards,</div>
                <div style="font-weight: 700; color: #111827; font-size: 15px; margin-top: 4px;">Oluwatobi Solomon</div>
                <div style="color: #6b7280; font-size: 12px;">Founder, FanVault</div>
                <div style="margin-top: 2px;">
                    <a href="mailto:oluwatobi@getfanvault.com" style="color: #059669; text-decoration: none; font-weight: 600;">oluwatobi@getfanvault.com</a>
                    &nbsp;·&nbsp;
                    <a href="https://getfanvault.com" style="color: #6b7280; text-decoration: none;">getfanvault.com</a>
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 14px;">
                <div style="padding: 12px 16px; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; font-size: 12px; color: #166534; line-height: 1.5;">
                    💡 <strong>Quick Note:</strong> Just hit <strong>Reply</strong> to this email! It goes straight to my personal inbox at <strong style="text-decoration: underline;">oluwatobi@getfanvault.com</strong> — happy to answer any questions or set up a test capsule for {{ $prospect->creator }} in 5 minutes.
                </div>
            </td>
        </tr>
    </table>
</x-mail.layout>
