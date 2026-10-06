<x-mail.layout>
    <x-slot:preheader>
        Your creator community vault is officially live! Fans can now seal letters for your milestone stream.
    </x-slot:preheader>

    <!-- Top Welcome Badge -->
    <div style="margin-bottom: 16px;">
        <span style="display: inline-block; padding: 4px 10px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 9999px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #064e3b; text-transform: uppercase; letter-spacing: 0.5px;">
            🎉 Creator Vault Activated
        </span>
    </div>

    <!-- Heading -->
    <h1 style="margin: 0 0 10px 0; font-family: Georgia, Cambria, 'Times New Roman', serif; font-size: 26px; line-height: 1.25; color: #0f172a; font-weight: 700;">
        Welcome to FanVault, {{ $creator->name }}!
    </h1>

    <p style="margin: 0 0 20px 0; font-size: 15px; color: #475569; line-height: 1.6;">
        Congratulations! Your creator community time capsule vault has been created and is ready to collect heartfelt letters, memories, and predictions from your fans.
    </p>

    <!-- Public Door Link Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 2px dashed #a7f3d0; border-radius: 16px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 20px;">
                <span style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; display: block; margin-bottom: 4px;">
                    Your Public Fan Door
                </span>
                <a href="{{ url('/with/'.$creator->slug) }}" target="_blank" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 16px; font-weight: 700; color: #047857; text-decoration: none; word-break: break-all;">
                    {{ url('/with/'.$creator->slug) }}
                </a>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                    💡 <strong>Pro Tip:</strong> Add this link to your stream overlay, video description, or channel bio so fans can seal letters today.
                </p>
            </td>
        </tr>
    </table>

    <!-- Platforms & Milestone Info -->
    <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 24px;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td style="padding-bottom: 10px;">
                    <span style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; color: #64748b; font-weight: 600;">Connected Platforms</span>
                    <div style="margin-top: 4px;">
                        @foreach($creator->platformsList() as $plt)
                            <span style="display: inline-block; padding: 2px 8px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; font-size: 12px; font-weight: 700; color: #064e3b; margin-right: 4px; margin-bottom: 4px;">
                                {{ $plt }}
                            </span>
                        @endforeach
                    </div>
                </td>
            </tr>
            @if($creator->milestone_title)
            <tr>
                <td style="padding-top: 10px; border-top: 1px solid #f1f5f9;">
                    <span style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; color: #64748b; font-weight: 600;">Target Milestone</span>
                    <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                        🎯 {{ $creator->milestone_title }}
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        Unlock Date: <strong style="color: #047857;">{{ $creator->formattedUnlockDate() }}</strong>
                    </div>
                </td>
            </tr>
            @endif
        </table>
    </div>

    <!-- 3-Step Creator Guide -->
    <div style="margin-bottom: 28px;">
        <h2 style="margin: 0 0 12px 0; font-size: 14px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #0f172a; font-weight: 700;">
            How Your Vault Works
        </h2>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td valign="top" style="padding-bottom: 12px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td width="28" valign="top">
                                <div style="width: 22px; height: 22px; background-color: #064e3b; color: #ffffff; border-radius: 9999px; font-size: 11px; font-weight: 700; text-align: center; line-height: 22px; font-family: ui-monospace, monospace;">1</div>
                            </td>
                            <td valign="top" style="padding-left: 8px;">
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Fans Seal Keepsakes</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Fans write personal letters, predictions, and attach photos sealed until stream day.</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td valign="top" style="padding-bottom: 12px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td width="28" valign="top">
                                <div style="width: 22px; height: 22px; background-color: #064e3b; color: #ffffff; border-radius: 9999px; font-size: 11px; font-weight: 700; text-align: center; line-height: 22px; font-family: ui-monospace, monospace;">2</div>
                            </td>
                            <td valign="top" style="padding-left: 8px;">
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">You Keep 80% of All Contributions</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">You set your own pricing. Contributions go directly to your ledger with automated Stripe payouts.</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td width="28" valign="top">
                                <div style="width: 22px; height: 22px; background-color: #064e3b; color: #ffffff; border-radius: 9999px; font-size: 11px; font-weight: 700; text-align: center; line-height: 22px; font-family: ui-monospace, monospace;">3</div>
                            </td>
                            <td valign="top" style="padding-left: 8px;">
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Milestone Stream Reveal</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">When you hit your goal, unseal the vault live using the OBS Stream Reader and read community letters live!</div>
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
                <a href="{{ $studioUrl }}" target="_blank" style="display: inline-block; background-color: #064e3b; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; padding: 15px 32px; border-radius: 12px; letter-spacing: -0.2px; text-align: center; box-shadow: 0 4px 14px rgba(6, 78, 59, 0.25);">
                    Open Your Creator Studio ➔
                </a>
            </td>
        </tr>
    </table>

    <div style="font-size: 12px; color: #94a3b8; text-align: center; line-height: 1.5;">
        Need assistance or want to setup OBS cue cards? Simply reply to this email or visit your Studio.
    </div>
</x-mail.layout>
