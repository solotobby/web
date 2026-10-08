<x-mail.layout>
    <x-slot:preheader>
        Your fan letter No. {{ \App\Support\Capsule::formatNumber($postcard->number) }} is officially sealed in the vault!
    </x-slot:preheader>

    <!-- Dribbble Centered Success Header -->
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="width: 56px; height: 56px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 50%; text-align: center; line-height: 56px; margin: 0 auto 16px auto; font-size: 24px; color: #059669; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.12);">
            ✓
        </div>
        <h1 style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 24px; line-height: 1.25; color: #0f172a; font-weight: 800; letter-spacing: -0.5px;">
            Your Letter is Officially Sealed!
        </h1>
        <p style="margin: 0; font-size: 14px; color: #64748b;">
            Hi {{ $postcard->name }}, your words are permanently archived in the digital vault.
        </p>
    </div>

    <!-- Structured Receipt Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 22px;">
                <!-- Amount Banner -->
                <div style="text-align: center; padding-bottom: 16px; border-bottom: 1px solid #edf2f7;">
                    <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                        Amount Paid
                    </div>
                    <div style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                        ${{ number_format($amountCents / 100, 2) }} USD
                    </div>
                </div>

                <!-- Structured Key-Value Details Table -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 14px;">
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b;">Capsule Number</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 700; color: #0f172a; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
                            No. {{ \App\Support\Capsule::formatNumber($postcard->number) }}
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b; border-top: 1px solid #f1f5f9;">Keepsake Tier</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #059669; border-top: 1px solid #f1f5f9;">
                            {{ $postcard->founding ? '🏛️ Founding Pass' : '🌿 Archival Seal' }}
                        </td>
                    </tr>
                    @if($postcard->creator)
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b; border-top: 1px solid #f1f5f9;">Creator</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #0f172a; border-top: 1px solid #f1f5f9;">
                            {{ $postcard->creator->name }}
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b; border-top: 1px solid #f1f5f9;">Milestone</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #059669; border-top: 1px solid #f1f5f9;">
                            {{ $postcard->milestone?->title ?? ($postcard->creator->milestone_title ?: 'Community Milestone Stream') }}
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b; border-top: 1px solid #f1f5f9;">Scheduled Reveal</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #0f172a; border-top: 1px solid #f1f5f9;">
                            {{ $postcard->milestone?->formattedUnlockDate() ?? $postcard->creator->formattedUnlockDate() }}
                        </td>
                    </tr>
                    @endif
                    @if(!empty($postcard->teaser))
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b; border-top: 1px solid #f1f5f9;">Public Teaser</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-style: italic; color: #334155; border-top: 1px solid #f1f5f9;">
                            “{{ $postcard->teaser }}”
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b; border-top: 1px solid #f1f5f9;">Status</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #059669; border-top: 1px solid #f1f5f9;">
                            ✓ Paid & Sealed
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Dribbble Call to Action Button -->
    @php
        $passUrl = route('message', $postcard) . ($claimToken ? '?claim='.$claimToken : '');
    @endphp
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
        <tr>
            <td align="center">
                <a href="{{ $passUrl }}" target="_blank" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 34px; border-radius: 8px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25); letter-spacing: -0.2px; text-align: center;">
                    View Your Sealed Fan Pass ➔
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
                <a href="{{ $passUrl }}" target="_blank" style="color: #059669; word-break: break-all; text-decoration: underline;">
                    {{ $passUrl }}
                </a>
            </td>
        </tr>
    </table>

    <!-- Access Notice (Dribbble Subtle Reassurance Card) -->
    <div style="padding: 14px 18px; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 12px; color: #166534; line-height: 1.55;">
        🔐 <strong>Private Access Note:</strong> This email contains your personal key to read your full letter and photo. Keep this email safe. Your letter is locked and cannot be tampered with until the milestone unlock date.
    </div>
</x-mail.layout>
