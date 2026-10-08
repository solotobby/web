<x-mail.layout>
    <x-slot:preheader>
        Your Creator Time Capsule is officially live! Fans can now leave memories and predictions for your milestone stream.
    </x-slot:preheader>

    <!-- Top Badge -->
    <div style="margin-bottom: 16px;">
        <span style="display: inline-block; padding: 4px 10px; background-color: #ecfdf5; border-radius: 4px; font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.5px;">
            🎉 Creator Capsule Activated
        </span>
    </div>

    <!-- Heading -->
    <h1 style="margin: 0 0 10px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 22px; line-height: 1.3; color: #111827; font-weight: 700;">
        Welcome to FanVault, {{ $creator->name }}!
    </h1>

    <p style="margin: 0 0 20px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
        Your Creator Time Capsule is ready. Your community can now seal messages, milestone memories, and predictions for your upcoming reveal stream.
    </p>

    <!-- Public Link Box (Paystack Style Card) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 18px 20px;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600; margin-bottom: 4px;">
                    Your Public Time Capsule Link
                </div>
                <a href="{{ url('/with/'.$creator->slug) }}" target="_blank" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 15px; font-weight: 700; color: #059669; text-decoration: none; word-break: break-all;">
                    {{ url('/with/'.$creator->slug) }}
                </a>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #6b7280; line-height: 1.5;">
                    💡 <strong>Pro Tip:</strong> Add this link to your stream overlay, video description, or channel bio so fans can seal keepsakes today.
                </p>
            </td>
        </tr>
    </table>

    <!-- Connected Platforms & Milestone Info -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 16px 20px;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600; margin-bottom: 6px;">
                    Connected Platforms
                </div>
                <div>
                    @foreach($creator->platformsList() as $plt)
                        <span style="display: inline-block; padding: 2px 8px; background-color: #f3f4f6; border-radius: 4px; font-size: 12px; font-weight: 600; color: #374151; margin-right: 4px; margin-bottom: 4px;">
                            {{ $plt }}
                        </span>
                    @endforeach
                </div>

                @if($creator->milestone_title)
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f3f4f6;">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600;">
                        Target Milestone
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: #111827; margin-top: 2px;">
                        🎯 {{ $creator->milestone_title }}
                    </div>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                        Scheduled Reveal: <strong style="color: #059669;">{{ $creator->formattedUnlockDate() }}</strong>
                    </div>
                </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- How Your Vault Works (3 Simple Steps) -->
    <div style="margin-bottom: 26px;">
        <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #111827; font-weight: 700; margin-bottom: 12px;">
            How Your Vault Works
        </div>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td valign="top" style="padding-bottom: 12px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td width="26" valign="top">
                                <div style="width: 20px; height: 20px; background-color: #059669; color: #ffffff; border-radius: 50%; font-size: 11px; font-weight: 700; text-align: center; line-height: 20px;">1</div>
                            </td>
                            <td valign="top" style="padding-left: 8px;">
                                <div style="font-size: 13px; font-weight: 600; color: #111827;">Fans Seal Keepsakes</div>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 1px;">Fans write personal letters, predictions, and attach photos sealed until stream day.</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td valign="top" style="padding-bottom: 12px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td width="26" valign="top">
                                <div style="width: 20px; height: 20px; background-color: #059669; color: #ffffff; border-radius: 50%; font-size: 11px; font-weight: 700; text-align: center; line-height: 20px;">2</div>
                            </td>
                            <td valign="top" style="padding-left: 8px;">
                                <div style="font-size: 13px; font-weight: 600; color: #111827;">You Keep 80% of All Contributions</div>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 1px;">You set your pricing. Contributions go directly to your ledger with automated Stripe payouts.</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td width="26" valign="top">
                                <div style="width: 20px; height: 20px; background-color: #059669; color: #ffffff; border-radius: 50%; font-size: 11px; font-weight: 700; text-align: center; line-height: 20px;">3</div>
                            </td>
                            <td valign="top" style="padding-left: 8px;">
                                <div style="font-size: 13px; font-weight: 600; color: #111827;">Milestone Stream Reveal</div>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 1px;">When you hit your goal, unseal the vault live using the OBS Stream Reader and read community letters live!</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- CTA Button -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 22px;">
        <tr>
            <td align="center">
                <a href="{{ $studioUrl }}" target="_blank" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 13px 28px; border-radius: 6px; text-align: center;">
                    Open Your Creator Studio ➔
                </a>
            </td>
        </tr>
    </table>

    <div style="font-size: 12px; color: #6b7280; text-align: center; line-height: 1.5;">
        Need assistance or want to setup OBS cue cards? Simply reply to this email or visit your Studio.
    </div>
</x-mail.layout>
