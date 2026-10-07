<x-mail.layout>
    <x-slot:preheader>
        A free idea for your next milestone from FanVault — The Creator Time Capsule.
    </x-slot:preheader>

    <!-- Top Badge -->
    <div style="margin-bottom: 16px;">
        <span style="display: inline-block; padding: 4px 10px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 9999px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #064e3b; text-transform: uppercase; letter-spacing: 0.5px;">
            💌 Direct Creator Partnership
        </span>
    </div>

    <!-- Letter Body (Styled for high readability and warmth) -->
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.7; color: #1e293b; white-space: pre-wrap; margin-bottom: 24px;">{!! e($customBody) !!}</div>

    <!-- Quick Action Card -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 20px; text-align: center;">
                <span style="font-size: 12px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; display: block; margin-bottom: 10px;">
                    Explore The Creator Time Capsule
                </span>
                <a href="{{ url('/') }}" target="_blank" style="display: inline-block; padding: 12px 28px; background-color: #064e3b; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; border-radius: 9999px; box-shadow: 0 4px 12px rgba(6,78,59,0.2);">
                    Visit FanVault (getfanvault.com) ➔
                </a>
                <p style="margin: 12px 0 0 0; font-size: 12px; color: #64748b;">
                    Zero technical setup required. We build and customize everything for your community.
                </p>
            </td>
        </tr>
    </table>

    <!-- Reply-To Reminder -->
    <div style="border-top: 1px solid #f1f5f9; padding-top: 16px; font-size: 12px; color: #64748b; line-height: 1.5;">
        <span>Sent directly by <strong>Oluwatobi Solomon</strong>, Founder of FanVault.</span><br />
        <span>Simply hit <strong>Reply</strong> to respond directly, or email <a href="mailto:oluwatobi@getfanvault.com" style="color: #047857; text-decoration: none; font-weight: 600;">oluwatobi@getfanvault.com</a>.</span>
    </div>
</x-mail.layout>
