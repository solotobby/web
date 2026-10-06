<x-mail.layout>
    <x-slot:preheader>
        Your fan letter No. {{ \App\Support\Capsule::formatNumber($postcard->number) }} is officially sealed in the vault!
    </x-slot:preheader>

    <!-- Top Badge -->
    <div style="margin-bottom: 16px;">
        <span style="display: inline-block; padding: 4px 10px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 9999px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #064e3b; text-transform: uppercase; letter-spacing: 0.5px;">
            📬 Sealed Fan Keepsake Pass
        </span>
    </div>

    <!-- Heading -->
    <h1 style="margin: 0 0 10px 0; font-family: Georgia, Cambria, 'Times New Roman', serif; font-size: 26px; line-height: 1.25; color: #0f172a; font-weight: 700;">
        Your Letter is Officially Sealed!
    </h1>

    <p style="margin: 0 0 24px 0; font-size: 15px; color: #475569; line-height: 1.6;">
        Hi {{ $postcard->name }}, your words have been permanently archived in the digital time capsule. When the milestone is unlocked, the creator will open and read community letters live on stream.
    </p>

    <!-- Certificate / Capsule Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 2px dashed #a7f3d0; border-radius: 16px; margin-bottom: 28px;">
        <tr>
            <td style="padding: 22px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td align="left" style="padding-bottom: 14px;">
                            <span style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                                Official Capsule Number
                            </span>
                            <div style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 22px; font-weight: 800; color: #064e3b; margin-top: 2px;">
                                No. {{ \App\Support\Capsule::formatNumber($postcard->number) }}
                            </div>
                        </td>
                        <td align="right" valign="top" style="padding-bottom: 14px;">
                            <span style="display: inline-block; padding: 3px 8px; background-color: {{ $postcard->founding ? '#fefce8' : '#ecfdf5' }}; border: 1px solid {{ $postcard->founding ? '#fde047' : '#a7f3d0' }}; border-radius: 9999px; font-size: 11px; font-weight: 700; color: {{ $postcard->founding ? '#854d0e' : '#064e3b' }};">
                                {{ $postcard->founding ? '🏛️ Founding Pass' : '🌿 Archival Seal' }}
                            </span>
                        </td>
                    </tr>

                    @if($postcard->creator)
                    <tr>
                        <td colspan="2" style="padding-top: 12px; border-top: 1px solid #e2e8f0;">
                            <div style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                                Creator Vault & Milestone
                            </div>
                            <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                                {{ $postcard->creator->name }} <span style="font-size: 13px; font-weight: 400; color: #64748b;">({{ $postcard->creator->handle }})</span>
                            </div>
                            <div style="font-size: 13px; color: #047857; font-weight: 600; margin-top: 2px;">
                                🎯 {{ $postcard->milestone?->title ?? ($postcard->creator->milestone_title ?: 'Community Milestone Stream') }}
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                Scheduled Reveal: <strong style="color: #0f172a;">{{ $postcard->milestone?->formattedUnlockDate() ?? $postcard->creator->formattedUnlockDate() }}</strong>
                            </div>
                        </td>
                    </tr>
                    @endif

                    @if(!empty($postcard->teaser))
                    <tr>
                        <td colspan="2" style="padding-top: 12px; border-top: 1px solid #e2e8f0;">
                            <div style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                                Your Public Teaser
                            </div>
                            <div style="font-family: Georgia, serif; font-style: italic; font-size: 14px; color: #334155; margin-top: 4px; line-height: 1.5;">
                                “{{ $postcard->teaser }}”
                            </div>
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td colspan="2" style="padding-top: 12px; border-top: 1px solid #e2e8f0;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left">
                                        <span style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                                            Contribution Receipt
                                        </span>
                                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                                            ${{ number_format($amountCents / 100, 2) }} USD
                                        </div>
                                    </td>
                                    <td align="right" valign="bottom">
                                        <span style="display: inline-block; font-size: 11px; color: #047857; font-weight: 600;">
                                            ✓ Paid & Confirmed
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Call to Action Button -->
    @php
        $passUrl = route('message', $postcard) . ($claimToken ? '?claim='.$claimToken : '');
    @endphp
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td align="center">
                <a href="{{ $passUrl }}" target="_blank" style="display: inline-block; background-color: #064e3b; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 28px; border-radius: 12px; letter-spacing: -0.2px; text-align: center; box-shadow: 0 4px 12px rgba(6, 78, 59, 0.2);">
                    View Your Sealed Fan Pass ➔
                </a>
            </td>
        </tr>
    </table>

    <!-- Security & Access Notice -->
    <div style="background-color: #f1f5f9; border-radius: 12px; padding: 16px; font-size: 12px; color: #475569; line-height: 1.6;">
        <strong style="color: #0f172a;">🔐 Private Access Note:</strong> This email contains your personal key to read your full letter and photo. Keep this email safe. Your letter is locked and cannot be tampered with until the milestone unlock date.
    </div>
</x-mail.layout>
