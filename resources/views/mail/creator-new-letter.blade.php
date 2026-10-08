<x-mail.layout>
    <x-slot:preheader>
        ✨ New contribution sealed in your Time Capsule from {{ $postcard->name }} (+${{ number_format($creatorCutCents / 100, 2) }})
    </x-slot:preheader>

    <!-- Dribbble Top Centered Icon & Heading -->
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="width: 56px; height: 56px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 50%; text-align: center; line-height: 56px; font-size: 24px; margin: 0 auto 16px auto; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.12);">
            ✨
        </div>
        <h1 style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 24px; line-height: 1.25; color: #0f172a; font-weight: 800; letter-spacing: -0.5px;">
            New Contribution Sealed in Your Time Capsule!
        </h1>
        <p style="margin: 0; font-size: 14px; color: #64748b;">
            Hi {{ $creator->name }}, a community member just supported your upcoming milestone celebration.
        </p>
    </div>

    <!-- Structured Earnings Summary Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 22px;">
                <!-- Earnings Highlight -->
                <div style="text-align: center; padding-bottom: 16px; border-bottom: 1px solid #edf2f7;">
                    <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                        Your Share Credited (80%)
                    </div>
                    <div style="font-size: 28px; font-weight: 800; color: #059669; margin-top: 4px;">
                        +${{ number_format($creatorCutCents / 100, 2) }} USD
                    </div>
                </div>

                <!-- Key-Value Rows -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 14px;">
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b;">Superfan</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #0f172a;">
                            {{ $postcard->name }}
                            @if(!empty($postcard->location))
                                <span style="font-size: 12px; font-weight: 400; color: #64748b;">({{ $postcard->location }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b; border-top: 1px solid #f1f5f9;">Pass Number</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 700; color: #0f172a; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; border-top: 1px solid #f1f5f9;">
                            No. {{ \App\Support\Capsule::formatNumber($postcard->number) }}
                        </td>
                    </tr>
                    @if($postcard->milestone || $creator->milestone_title)
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b; border-top: 1px solid #f1f5f9;">Milestone</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #059669; border-top: 1px solid #f1f5f9;">
                            🎯 {{ $postcard->milestone?->title ?? $creator->milestone_title }}
                        </td>
                    </tr>
                    @endif
                    @if(!empty($postcard->teaser))
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b; border-top: 1px solid #f1f5f9;">Fan Teaser</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-style: italic; color: #334155; border-top: 1px solid #f1f5f9;">
                            “{{ $postcard->teaser }}”
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td align="left" style="padding: 8px 0; font-size: 13px; color: #64748b; border-top: 1px solid #f1f5f9;">Total Fan Contribution</td>
                        <td align="right" style="padding: 8px 0; font-size: 13px; font-weight: 600; color: #0f172a; border-top: 1px solid #f1f5f9;">
                            ${{ number_format($amountCents / 100, 2) }} USD
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Dribbble Call to Action Button -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
        <tr>
            <td align="center">
                <a href="{{ route('creators.studio') }}" target="_blank" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 34px; border-radius: 8px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25); letter-spacing: -0.2px; text-align: center;">
                    Open Creator Studio ➔
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
                <a href="{{ route('creators.studio') }}" target="_blank" style="color: #059669; word-break: break-all; text-decoration: underline;">
                    {{ route('creators.studio') }}
                </a>
            </td>
        </tr>
    </table>

    <div style="font-size: 12px; color: #64748b; line-height: 1.5; text-align: center;">
        Your community vault is collecting letters for your stream. Full letters unlock automatically when you hit your milestone unlock date.
    </div>
</x-mail.layout>
