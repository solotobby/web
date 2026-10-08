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


    <!-- Personalized Message Body -->
    <div style="font-size: 15px; line-height: 1.65; color: #374151; margin-bottom: 24px;">
        {!! nl2br(e($customBody)) !!}
    </div>

    <!-- Why Creators Love FanVault Callout -->
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

    <!-- Primary Action Button (Dribbble Clean Button & Fallback Box) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
        <tr>
            <td align="center">
                <a href="https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}" target="_blank" style="display: inline-block; padding: 14px 34px; background-color: #059669; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 15px; border-radius: 8px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25); letter-spacing: -0.2px; text-align: center;">
                    View {{ $prospect->creator }}'s Time Capsule Concept ➔
                </a>
            </td>
        </tr>
    </table>

    <!-- Dribbble-style Fallback Link Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 26px;">
        <tr>
            <td style="padding: 12px 16px; font-size: 12px; color: #64748b; line-height: 1.55;">
                <strong style="color: #0f172a; display: block; margin-bottom: 3px;">Having trouble with the button?</strong>
                Copy and paste this private draft URL into your browser:<br>
                <a href="https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}" target="_blank" style="color: #059669; word-break: break-all; text-decoration: underline;">
                    https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}
                </a>
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
