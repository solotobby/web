<x-mail.layout>
    <x-slot:preheader>
        Your secure magic link to open your FanVault Creator Studio (valid for 30 minutes).
    </x-slot:preheader>

    <!-- Top Badge -->
    <div style="margin-bottom: 16px;">
        <span style="display: inline-block; padding: 4px 10px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 9999px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #064e3b; text-transform: uppercase; letter-spacing: 0.5px;">
            🔑 Creator Studio Access
        </span>
    </div>

    <!-- Heading -->
    <h1 style="margin: 0 0 10px 0; font-family: Georgia, Cambria, 'Times New Roman', serif; font-size: 26px; line-height: 1.25; color: #0f172a; font-weight: 700;">
        Open Your Creator Studio
    </h1>

    <p style="margin: 0 0 20px 0; font-size: 15px; color: #475569; line-height: 1.6;">
        Hello {{ $creator->name }},
    </p>

    <p style="margin: 0 0 28px 0; font-size: 15px; color: #475569; line-height: 1.6;">
        Click the secure button below to sign in to your Creator Studio. For your security, this one-time magic link will expire in <strong>30 minutes</strong>.
    </p>

    <!-- Call to Action Button -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
        <tr>
            <td align="center">
                <a href="{{ $loginUrl }}" target="_blank" style="display: inline-block; background-color: #064e3b; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; padding: 15px 32px; border-radius: 12px; letter-spacing: -0.2px; text-align: center; box-shadow: 0 4px 14px rgba(6, 78, 59, 0.25);">
                    Open Creator Studio ➔
                </a>
            </td>
        </tr>
    </table>

    <!-- Door Information Card -->
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 24px;">
        <div style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
            Your Public Fan Door
        </div>
        <div style="font-size: 14px; color: #0f172a; font-weight: 600; margin-top: 4px;">
            <a href="{{ url('/with/'.$creator->slug) }}" target="_blank" style="color: #047857; text-decoration: none;">
                {{ url('/with/'.$creator->slug) }}
            </a>
        </div>
    </div>

    <div style="font-size: 12px; color: #94a3b8; line-height: 1.6; text-align: center;">
        If you did not request this magic link, you can safely ignore this email. No changes will be made to your vault.
    </div>
</x-mail.layout>
