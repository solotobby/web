<x-mail.layout>
    <x-slot:preheader>
        Your secure magic link to open your FanVault Creator Studio (valid for 30 minutes).
    </x-slot:preheader>

    <!-- Centered Hero Icon (Dribbble Verify Email Design) -->
    <div style="text-align: center; margin-bottom: 22px;">
        <div style="width: 56px; height: 56px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 50%; text-align: center; line-height: 56px; font-size: 24px; margin: 0 auto 16px auto; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.12);">
            🔑
        </div>
        <h1 style="margin: 0 0 8px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 24px; line-height: 1.25; color: #0f172a; font-weight: 800; letter-spacing: -0.5px;">
            Open Your Creator Studio
        </h1>
        <p style="margin: 0; font-size: 14px; color: #64748b;">
            Hello <strong>{{ $creator->name }}</strong>, click below to verify and sign in.
        </p>
    </div>

    <p style="margin: 0 0 24px 0; font-size: 15px; color: #475569; line-height: 1.6; text-align: center;">
        Click the secure button below to access your Studio dashboard. For your security, this verification link will expire in <strong>30 minutes</strong>.
    </p>

    <!-- Prominent Primary CTA Button -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td align="center">
                <a href="{{ $loginUrl }}" target="_blank" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 34px; border-radius: 8px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25); letter-spacing: -0.2px;">
                    Open Creator Studio ➔
                </a>
            </td>
        </tr>
    </table>

    <!-- Dribbble-style Fallback Link Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 14px 18px; font-size: 12px; color: #64748b; line-height: 1.55;">
                <strong style="color: #0f172a; display: block; margin-bottom: 4px;">Having trouble with the button?</strong>
                Copy and paste this URL into your web browser:<br>
                <a href="{{ $loginUrl }}" target="_blank" style="color: #059669; word-break: break-all; text-decoration: underline;">
                    {{ $loginUrl }}
                </a>
            </td>
        </tr>
    </table>

    <!-- Public Fan Door Link Card -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border: 1px dashed #cbd5e1; border-radius: 10px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 12px 18px;">
                <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                    Your Public Fan Door:
                </span>
                <span style="font-size: 13px; font-weight: 600; margin-left: 6px;">
                    <a href="{{ url('/with/'.$creator->slug) }}" target="_blank" style="color: #059669; text-decoration: underline;">
                        {{ url('/with/'.$creator->slug) }}
                    </a>
                </span>
            </td>
        </tr>
    </table>

    <div style="font-size: 12px; color: #94a3b8; line-height: 1.5; text-align: center;">
        If you did not request this magic link, you can safely ignore this email. No changes will be made to your vault.
    </div>
</x-mail.layout>
