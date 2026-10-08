<x-mail.layout>
    <x-slot:preheader>
        Your Creator Time Capsule is officially live! Fans can now leave memories and predictions for your milestone stream.
    </x-slot:preheader>

    <!-- Dribbble Top Centered Icon & Heading -->
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="width: 56px; height: 56px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 50%; text-align: center; line-height: 56px; font-size: 24px; margin: 0 auto 16px auto; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.12);">
            🎉
        </div>
        <h1 style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 24px; line-height: 1.25; color: #0f172a; font-weight: 800; letter-spacing: -0.5px;">
            Welcome to FanVault, {{ $creator->name }}!
        </h1>
        <p style="margin: 0; font-size: 14px; color: #64748b;">
            Your Creator Time Capsule is officially activated and ready for your community.
        </p>
    </div>

    <!-- Public Link Box (Dribbble Clean Card) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 18px 20px;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; margin-bottom: 4px;">
                    Your Public Time Capsule Link
                </div>
                <a href="{{ url('/with/'.$creator->slug) }}" target="_blank" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 15px; font-weight: 700; color: #059669; text-decoration: none; word-break: break-all;">
                    {{ url('/with/'.$creator->slug) }}
                </a>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                    💡 <strong>Pro Tip:</strong> Add this link to your stream overlay, video description, or channel bio so fans can seal keepsakes today.
                </p>
            </td>
        </tr>
    </table>

    <!-- Connected Platforms & Milestone Info -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 16px 20px;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; margin-bottom: 6px;">
                    Connected Platforms
                </div>
                <div>
                    @foreach($creator->platformsList() as $plt)
                        <span style="display: inline-block; padding: 2px 8px; background-color: #f1f5f9; border-radius: 4px; font-size: 12px; font-weight: 600; color: #334155; margin-right: 4px; margin-bottom: 4px;">
                            {{ $plt }}
                        </span>
                    @endforeach
                </div>

                @if($creator->milestone_title)
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                        Target Milestone
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                        🎯 {{ $creator->milestone_title }}
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        Scheduled Reveal: <strong style="color: #059669;">{{ $creator->formattedUnlockDate() }}</strong>
                    </div>
                </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- How Your Vault Works (3 Simple Steps) -->
    <div style="margin-bottom: 26px;">
        <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #0f172a; font-weight: 700; margin-bottom: 12px;">
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
                                <div style="font-size: 13px; font-weight: 600; color: #0f172a;">Fans Seal Keepsakes</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 1px;">Fans write personal letters, predictions, and attach photos sealed until stream day.</div>
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
                                <div style="font-size: 13px; font-weight: 600; color: #0f172a;">You Keep 80% of All Contributions</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 1px;">You set your pricing. Contributions go directly to your ledger with automated Stripe payouts.</div>
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
                                <div style="font-size: 13px; font-weight: 600; color: #0f172a;">Milestone Stream Reveal</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 1px;">When you hit your goal, unseal the vault live using the OBS Stream Reader and read community letters live!</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- CTA Button -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
        <tr>
            <td align="center">
                <a href="{{ $studioUrl }}" target="_blank" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 34px; border-radius: 8px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25); letter-spacing: -0.2px; text-align: center;">
                    Open Your Creator Studio ➔
                </a>
            </td>
        </tr>
    </table>

    <!-- Dribbble-style Fallback Link Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 12px 16px; font-size: 12px; color: #64748b; line-height: 1.55;">
                <strong style="color: #0f172a; display: block; margin-bottom: 3px;">Having trouble with the button?</strong>
                Copy and paste this link into your web browser:<br>
                <a href="{{ $studioUrl }}" target="_blank" style="color: #059669; word-break: break-all; text-decoration: underline;">
                    {{ $studioUrl }}
                </a>
            </td>
        </tr>
    </table>

    <div style="font-size: 12px; color: #94a3b8; text-align: center; line-height: 1.5;">
        Need assistance or want to setup OBS cue cards? Simply reply to this email or visit your Studio.
    </div>
</x-mail.layout>
