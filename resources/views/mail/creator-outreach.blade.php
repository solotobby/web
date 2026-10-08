<x-mail.layout>
    <x-slot:preheader>
        Private FanVault Time Capsule concept prepared for {{ $prospect->creator }} — a free idea for your next milestone.
    </x-slot:preheader>

    <!-- Top Badge Row -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
        <tr>
            <td align="left" valign="middle">
                <span style="display: inline-block; padding: 4px 12px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 9999px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #064e3b; text-transform: uppercase; letter-spacing: 0.5px;">
                    ⚡ Exclusive Creator Concept
                </span>
            </td>
            <td align="right" valign="middle">
                <span style="display: inline-block; padding: 4px 10px; background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 9999px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 10px; font-weight: 600; color: #475569; text-transform: uppercase;">
                    {{ $prospect->speciality }} · Priority {{ $prospect->recommended_priority }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Hero Headline -->
    <h1 style="margin: 0 0 12px 0; font-family: Georgia, Cambria, 'Times New Roman', serif; font-size: 26px; line-height: 1.25; color: #0f172a; font-weight: 700; letter-spacing: -0.5px;">
        Turn Your Next Milestone Into A Live Community Reveal Stream.
    </h1>

    <p style="margin: 0 0 24px 0; font-size: 15px; color: #475569; line-height: 1.6;">
        Hi {{ explode(' ', $prospect->creator)[0] }} — we designed an exclusive FanVault time capsule concept specifically for <strong>{{ $prospect->creator }}</strong>.
    </p>

    <!-- THE TIME CAPSULE ARTIFACT BOX (Cool Cyber/Vault Card) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #090d16; border: 1px solid #1e293b; border-radius: 18px; margin-bottom: 28px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.25);">
        <tr>
            <td style="padding: 20px 22px; border-bottom: 1px solid #1e293b; background-color: #0c121e;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td align="left" valign="middle">
                            <span style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #34d399; letter-spacing: 0.5px;">
                                🔒 CAPSULE NO. #{{ sprintf('%03d', $prospect->prospect_number) }}
                            </span>
                            <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 16px; font-weight: 800; color: #ffffff; margin-top: 3px;">
                                {{ $prospect->creator }} Community Vault
                            </div>
                        </td>
                        <td align="right" valign="middle">
                            <span style="display: inline-block; padding: 4px 10px; background-color: #10b98120; border: 1px solid #10b98150; border-radius: 9999px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 10px; font-weight: 700; color: #34d399; text-transform: uppercase;">
                                ● Ready To Activate
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px 22px;">
                <!-- 3-Step Interactive Visual Flow -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td width="30%" align="center" valign="top" style="padding: 8px 6px;">
                            <div style="font-size: 22px; margin-bottom: 6px;">✍️</div>
                            <div style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #34d399; text-transform: uppercase;">1. Fans Seal Today</div>
                            <div style="font-size: 11px; color: #94a3b8; line-height: 1.4; margin-top: 4px;">Predictions, photos, memories & notes</div>
                        </td>
                        <td width="5%" align="center" valign="middle" style="color: #475569; font-size: 16px; font-weight: bold;">
                            ➔
                        </td>
                        <td width="30%" align="center" valign="top" style="padding: 8px 6px;">
                            <div style="font-size: 22px; margin-bottom: 6px;">🔐</div>
                            <div style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #38bdf8; text-transform: uppercase;">2. Stays Secret</div>
                            <div style="font-size: 11px; color: #94a3b8; line-height: 1.4; margin-top: 4px;">Zero spoilers until unlock date arrives</div>
                        </td>
                        <td width="5%" align="center" valign="middle" style="color: #475569; font-size: 16px; font-weight: bold;">
                            ➔
                        </td>
                        <td width="30%" align="center" valign="top" style="padding: 8px 6px;">
                            <div style="font-size: 22px; margin-bottom: 6px;">🎬</div>
                            <div style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #fbbf24; text-transform: uppercase;">3. Reveal On Stream</div>
                            <div style="font-size: 11px; color: #94a3b8; line-height: 1.4; margin-top: 4px;">Unboxed live for unforgettable reactions</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Personalized Message Body -->
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.75; color: #1e293b; margin-bottom: 26px;">
        {!! nl2br(e($customBody)) !!}
    </div>

    <!-- 3 Key Reasons Why Creators Love FanVault -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 26px;">
        <tr>
            <td style="padding: 18px 20px;">
                <span style="font-family: ui-monospace, monospace; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 700; display: block; margin-bottom: 12px;">
                    Why This Beats Another Generic Tweet / Merch Drop:
                </span>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="padding-bottom: 10px; font-size: 13px; color: #334155; line-height: 1.5;">
                            🎥 <strong style="color: #0f172a;">1-2 Hours of Organic Stream Content:</strong> Live reactions reading fan letters and unboxing predictions on stream.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 10px; font-size: 13px; color: #334155; line-height: 1.5;">
                            💎 <strong style="color: #0f172a;">A Permanent Community Keepsake:</strong> Every letter is sealed and preserved as a lasting piece of your creator timeline.
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 13px; color: #334155; line-height: 1.5;">
                            ⚡ <strong style="color: #0f172a;">100% Free & Zero Tech Work:</strong> We handle the custom setup, hosting, and community design for you at zero cost.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Call to Action Button -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
        <tr>
            <td align="center">
                <a href="https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}" target="_blank" style="display: inline-block; padding: 14px 34px; background-color: #064e3b; color: #ffffff; text-decoration: none; font-weight: 800; font-size: 15px; border-radius: 12px; box-shadow: 0 4px 16px rgba(6,78,59,0.3); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; letter-spacing: 0.2px;">
                    🚀 View {{ $prospect->creator }}'s Time Capsule Concept ➔
                </a>
                <div style="margin-top: 10px; font-size: 12px; color: #64748b; font-family: ui-monospace, monospace;">
                    Private draft: <a href="https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}" style="color: #047857; text-decoration: underline;">getfanvault.com/with/{{ Str::slug($prospect->creator) }}</a>
                </div>
            </td>
        </tr>
    </table>

    <!-- Founder Personal Signature & Reply Note -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
        <tr>
            <td valign="top" width="48" style="padding-right: 14px;">
                <div style="width: 44px; height: 44px; rounded: 12px; background-color: #064e3b; color: #34d399; text-align: center; line-height: 44px; font-family: ui-monospace, monospace; font-size: 15px; font-weight: 800; border-radius: 12px;">
                    OS
                </div>
            </td>
            <td valign="top" style="font-size: 13px; color: #475569; line-height: 1.55;">
                <div style="font-weight: 800; color: #0f172a; font-size: 14px;">Oluwatobi Solomon</div>
                <div style="color: #64748b; font-size: 12px;">Founder, FanVault · The Creator Time Capsule</div>
                <div style="margin-top: 4px;">
                    <a href="mailto:oluwatobi@getfanvault.com" style="color: #047857; text-decoration: none; font-weight: 600;">oluwatobi@getfanvault.com</a>
                    · <a href="https://getfanvault.com" style="color: #64748b; text-decoration: none;">getfanvault.com</a>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 14px;">
                <div style="padding: 12px 16px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; font-size: 12px; color: #064e3b; line-height: 1.5;">
                    💡 <strong>Quick Note:</strong> Just hit <strong>Reply</strong> to this email! It goes straight to my personal inbox at <strong style="text-decoration: underline;">oluwatobi@getfanvault.com</strong> — I'd be happy to show you a quick demo or set up a test capsule for {{ $prospect->creator }} in 5 minutes.
                </div>
            </td>
        </tr>
    </table>
</x-mail.layout>
