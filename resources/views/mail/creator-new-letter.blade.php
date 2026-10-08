<x-mail.layout>
    <x-slot:preheader>
        ✨ New contribution sealed in your Time Capsule from {{ $postcard->name }} (+${{ number_format($creatorCutCents / 100, 2) }})
    </x-slot:preheader>

    <!-- Paystack Top Badge -->
    <div style="margin-bottom: 16px;">
        <span style="display: inline-block; padding: 4px 10px; background-color: #ecfdf5; border-radius: 4px; font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.5px;">
            ✨ Contribution Credited
        </span>
    </div>

    <!-- Heading -->
    <h1 style="margin: 0 0 10px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 22px; line-height: 1.3; color: #111827; font-weight: 700;">
        New Contribution Sealed in Your Time Capsule!
    </h1>

    <p style="margin: 0 0 20px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
        Hi {{ $creator->name }}, a community member just sealed a contribution in your Time Capsule for your upcoming milestone celebration.
    </p>

    <!-- Paystack-style Notification Summary Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 20px;">
                <!-- Earnings Highlight -->
                <div style="text-align: center; padding-bottom: 16px; border-bottom: 1px solid #f3f4f6;">
                    <div style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                        Your Share Credited (80%)
                    </div>
                    <div style="font-size: 26px; font-weight: 800; color: #059669; margin-top: 4px;">
                        +${{ number_format($creatorCutCents / 100, 2) }} USD
                    </div>
                </div>

                <!-- Key-Value Rows -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 12px;">
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280;">Superfan</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #111827;">
                            {{ $postcard->name }}
                            @if(!empty($postcard->location))
                                <span style="font-size: 12px; font-weight: 400; color: #6b7280;">({{ $postcard->location }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280; border-top: 1px solid #f3f4f6;">Pass Number</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 700; color: #111827; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; border-top: 1px solid #f3f4f6;">
                            No. {{ \App\Support\Capsule::formatNumber($postcard->number) }}
                        </td>
                    </tr>
                    @if($postcard->milestone || $creator->milestone_title)
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280; border-top: 1px solid #f3f4f6;">Milestone</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #059669; border-top: 1px solid #f3f4f6;">
                            🎯 {{ $postcard->milestone?->title ?? $creator->milestone_title }}
                        </td>
                    </tr>
                    @endif
                    @if(!empty($postcard->teaser))
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280; border-top: 1px solid #f3f4f6;">Fan Teaser</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-style: italic; color: #374151; border-top: 1px solid #f3f4f6;">
                            “{{ $postcard->teaser }}”
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #6b7280; border-top: 1px solid #f3f4f6;">Total Fan Contribution</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #111827; border-top: 1px solid #f3f4f6;">
                            ${{ number_format($amountCents / 100, 2) }} USD
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Paystack Call to Action Button -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td align="center">
                <a href="{{ route('creators.studio') }}" target="_blank" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 13px 28px; border-radius: 6px; text-align: center;">
                    Open Creator Studio ➔
                </a>
            </td>
        </tr>
    </table>

    <div style="font-size: 12px; color: #6b7280; line-height: 1.5; text-align: center;">
        Your community vault is collecting letters for your stream. Full letters unlock automatically when you hit your milestone unlock date.
    </div>
</x-mail.layout>
