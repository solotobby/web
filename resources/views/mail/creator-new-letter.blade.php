<x-mail.layout>
    <x-slot:preheader>
        ✨ New contribution sealed in your Time Capsule from {{ $postcard->name }} (+${{ number_format($creatorCutCents / 100, 2) }})
    </x-slot:preheader>

    <!-- Top Badge -->
    <div style="margin-bottom: 16px;">
        <span style="display: inline-block; padding: 4px 10px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 9999px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; color: #064e3b; text-transform: uppercase; letter-spacing: 0.5px;">
            ✨ New Time Capsule Contribution Credited
        </span>
    </div>

    <!-- Heading -->
    <h1 style="margin: 0 0 10px 0; font-family: Georgia, Cambria, 'Times New Roman', serif; font-size: 26px; line-height: 1.25; color: #0f172a; font-weight: 700;">
        New Contribution Sealed in Your Time Capsule!
    </h1>

    <p style="margin: 0 0 24px 0; font-size: 15px; color: #475569; line-height: 1.6;">
        Hey {{ $creator->name }}, a community member just sealed a contribution in your Time Capsule for your upcoming milestone celebration.
    </p>

    <!-- Notification Summary Card -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 28px;">
        <tr>
            <td style="padding: 22px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td align="left">
                            <span style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                                Superfan
                            </span>
                            <div style="font-size: 17px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                                {{ $postcard->name }}
                                @if(!empty($postcard->location))
                                <span style="font-size: 13px; font-weight: 500; color: #64748b;">from {{ $postcard->location }}</span>
                                @endif
                            </div>
                        </td>
                        <td align="right" valign="top">
                            <span style="display: inline-block; font-family: ui-monospace, monospace; font-size: 13px; font-weight: 700; color: #064e3b; background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 6px;">
                                Pass #{{ \App\Support\Capsule::formatNumber($postcard->number) }}
                            </span>
                        </td>
                    </tr>

                    @if($postcard->milestone || $creator->milestone_title)
                    <tr>
                        <td colspan="2" style="padding-top: 14px; border-top: 1px solid #e2e8f0;">
                            <span style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                                Milestone Capsule
                            </span>
                            <div style="font-size: 14px; font-weight: 700; color: #047857; margin-top: 2px;">
                                🎯 {{ $postcard->milestone?->title ?? $creator->milestone_title }}
                            </div>
                        </td>
                    </tr>
                    @endif

                    @if(!empty($postcard->teaser))
                    <tr>
                        <td colspan="2" style="padding-top: 14px; border-top: 1px solid #e2e8f0;">
                            <span style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                                Fan's Sealed Teaser
                            </span>
                            <div style="font-family: Georgia, serif; font-style: italic; font-size: 14px; color: #1e293b; margin-top: 4px; line-height: 1.5;">
                                “{{ $postcard->teaser }}”
                            </div>
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td colspan="2" style="padding-top: 14px; border-top: 1px solid #e2e8f0;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left">
                                        <span style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600;">
                                            Fan Contribution
                                        </span>
                                        <div style="font-size: 14px; font-weight: 600; color: #475569; margin-top: 2px;">
                                            ${{ number_format($amountCents / 100, 2) }}
                                        </div>
                                    </td>
                                    <td align="right">
                                        <span style="font-size: 11px; font-family: ui-monospace, monospace; text-transform: uppercase; letter-spacing: 0.5px; color: #047857; font-weight: 600;">
                                            Your Share (80%)
                                        </span>
                                        <div style="font-size: 17px; font-weight: 800; color: #064e3b; margin-top: 2px;">
                                            +${{ number_format($creatorCutCents / 100, 2) }} USD
                                        </div>
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
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td align="center">
                <a href="{{ route('creators.studio') }}" target="_blank" style="display: inline-block; background-color: #064e3b; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 28px; border-radius: 12px; letter-spacing: -0.2px; text-align: center; box-shadow: 0 4px 12px rgba(6, 78, 59, 0.2);">
                    Open Creator Studio ➔
                </a>
            </td>
        </tr>
    </table>

    <div style="font-size: 12px; color: #64748b; line-height: 1.6; text-align: center;">
        Your community vault is collecting letters for your stream. Full letters unlock automatically when you hit your milestone unlock date.
    </div>
</x-mail.layout>
