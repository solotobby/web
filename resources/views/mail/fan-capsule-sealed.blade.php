<x-mail.layout>
    <x-slot:preheader>
        Your fan letter No. {{ \App\Support\Capsule::formatNumber($postcard->number) }} is officially sealed in the vault!
    </x-slot:preheader>

    <!-- Paystack Centered Success Header -->
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="width: 44px; height: 44px; background-color: #ecfdf5; border-radius: 50%; text-align: center; line-height: 44px; margin: 0 auto 12px auto; font-size: 20px; color: #059669;">
            ✓
        </div>
        <h1 style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 22px; line-height: 1.3; color: #111827; font-weight: 700;">
            Your Letter is Officially Sealed!
        </h1>
        <p style="margin: 0; font-size: 14px; color: #4b5563; line-height: 1.5;">
            Hi {{ $postcard->name }}, your words are permanently archived in the digital vault.
        </p>
    </div>

    <!-- Paystack-style Receipt Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 20px;">
                <!-- Amount Banner -->
                <div style="text-align: center; padding-bottom: 16px; border-bottom: 1px solid #f3f4f6;">
                    <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                        Amount Paid
                    </div>
                    <div style="font-size: 26px; font-weight: 800; color: #111827; margin-top: 4px;">
                        ${{ number_format($amountCents / 100, 2) }} USD
                    </div>
                </div>

                <!-- Structured Key-Value Details Table -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 12px;">
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280;">Capsule Number</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 700; color: #111827; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
                            No. {{ \App\Support\Capsule::formatNumber($postcard->number) }}
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280; border-top: 1px solid #f3f4f6;">Keepsake Tier</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #059669; border-top: 1px solid #f3f4f6;">
                            {{ $postcard->founding ? '🏛️ Founding Pass' : '🌿 Archival Seal' }}
                        </td>
                    </tr>
                    @if($postcard->creator)
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280; border-top: 1px solid #f3f4f6;">Creator</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #111827; border-top: 1px solid #f3f4f6;">
                            {{ $postcard->creator->name }}
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280; border-top: 1px solid #f3f4f6;">Milestone</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #059669; border-top: 1px solid #f3f4f6;">
                            {{ $postcard->milestone?->title ?? ($postcard->creator->milestone_title ?: 'Community Milestone Stream') }}
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280; border-top: 1px solid #f3f4f6;">Scheduled Reveal</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #111827; border-top: 1px solid #f3f4f6;">
                            {{ $postcard->milestone?->formattedUnlockDate() ?? $postcard->creator->formattedUnlockDate() }}
                        </td>
                    </tr>
                    @endif
                    @if(!empty($postcard->teaser))
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280; border-top: 1px solid #f3f4f6;">Public Teaser</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-style: italic; color: #374151; border-top: 1px solid #f3f4f6;">
                            “{{ $postcard->teaser }}”
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280; border-top: 1px solid #f3f4f6;">Status</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #059669; border-top: 1px solid #f3f4f6;">
                            ✓ Paid & Sealed
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Paystack Call to Action Button -->
    @php
        $passUrl = route('message', $postcard) . ($claimToken ? '?claim='.$claimToken : '');
    @endphp
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td align="center">
                <a href="{{ $passUrl }}" target="_blank" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 13px 28px; border-radius: 6px; text-align: center;">
                    View Your Sealed Fan Pass ➔
                </a>
            </td>
        </tr>
    </table>

    <!-- Access Notice (Paystack Subtle Card) -->
    <div style="padding: 12px 16px; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; font-size: 12px; color: #166534; line-height: 1.5;">
        🔐 <strong>Private Access Note:</strong> This email contains your personal key to read your full letter and photo. Keep this email safe. Your letter is locked and cannot be tampered with until the milestone unlock date.
    </div>
</x-mail.layout>
