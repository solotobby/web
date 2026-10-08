<x-mail.layout>
    <x-slot:preheader>
        Your secure magic link to open your FanVault Creator Studio (valid for 30 minutes).
    </x-slot:preheader>

    <!-- Top Badge -->
    <div style="margin-bottom: 16px;">
        <span style="display: inline-block; padding: 4px 10px; background-color: #ecfdf5; border-radius: 4px; font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.5px;">
            🔑 Secure Magic Link
        </span>
    </div>

    <!-- Heading -->
    <h1 style="margin: 0 0 10px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 22px; line-height: 1.3; color: #111827; font-weight: 700;">
        Open Your Creator Studio
    </h1>

    <p style="margin: 0 0 18px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
        Hello {{ $creator->name }},
    </p>

    <p style="margin: 0 0 24px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
        Click the secure button below to sign in to your Creator Studio. This one-time link will expire in <strong>30 minutes</strong>.
    </p>

    <!-- Paystack Call to Action Button -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td align="center">
                <a href="{{ $loginUrl }}" target="_blank" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 13px 28px; border-radius: 6px; text-align: center;">
                    Sign In to Creator Studio ➔
                </a>
            </td>
        </tr>
    </table>

    <!-- Door Information Card -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px;">
        <tr>
            <td style="padding: 14px 18px;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600;">
                    Your Public Fan Door
                </div>
                <div style="font-size: 13px; font-weight: 600; margin-top: 2px;">
                    <a href="{{ url('/with/'.$creator->slug) }}" target="_blank" style="color: #059669; text-decoration: underline;">
                        {{ url('/with/'.$creator->slug) }}
                    </a>
                </div>
            </td>
        </tr>
    </table>

    <div style="font-size: 12px; color: #9ca3af; line-height: 1.5; text-align: center;">
        If you did not request this magic link, you can safely ignore this email. No changes will be made to your vault.
    </div>
</x-mail.layout>
