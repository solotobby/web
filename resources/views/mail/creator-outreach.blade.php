<x-mail.layout>
    <x-slot:preheader>
        Private FanVault Time Capsule concept prepared for {{ $prospect->creator }} — a free idea for your next milestone.
    </x-slot:preheader>

    <!-- Top Badge Row -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
        <tr>
            <td align="left" valign="middle">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td bgcolor="#ecfdf5" style="background-color: #ecfdf5; padding: 4px 10px; border-radius: 4px; border: 1px solid #a7f3d0;">
                            <span style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.5px;">
                                Creator Concept
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
            <td align="right" valign="middle">
                <span style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; color: #64748b; font-weight: 600;">
                    {{ $prospect->speciality }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Hero Headline -->
    <h1 style="margin: 0 0 16px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; line-height: 1.35; color: #0f172a; font-weight: 700; letter-spacing: -0.3px;">
        Turn Your Next Milestone Into A Live Community Reveal Stream
    </h1>

    <p style="margin: 0 0 18px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
        Hi {{ explode(' ', $prospect->creator)[0] }} &mdash; we put together a private FanVault time capsule concept specifically for <strong>{{ $prospect->creator }}</strong>.
    </p>

    <!-- Personalized Message Body -->
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.65; color: #334155; margin-bottom: 24px;">
        {!! nl2br(e($customBody)) !!}
    </div>

    <!-- Why Creators Love FanVault Callout Box (Bulletproof Table) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f8fafc" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; border-collapse: separate; margin: 24px 0;">
        <tr>
            <td style="padding: 18px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 700; margin-bottom: 12px;">
                    Why This Beats Another Generic Tweet / Merch Drop:
                </div>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="padding-bottom: 10px; font-size: 13px; color: #475569; line-height: 1.55;">
                            &#127916; <strong style="color: #0f172a;">1&ndash;2 Hours of Stream Content:</strong> Live reactions reading fan letters and unboxing predictions on stream.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom: 10px; font-size: 13px; color: #475569; line-height: 1.55;">
                            &#128142; <strong style="color: #0f172a;">A Permanent Community Keepsake:</strong> Every letter is sealed and preserved as a lasting piece of your journey.
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 13px; color: #475569; line-height: 1.55;">
                            &#9889; <strong style="color: #0f172a;">100% Free &amp; Zero Tech Work:</strong> We handle the custom setup, hosting, and design for you at zero cost.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Primary Action Button (Bulletproof VML + HTML Table Button) -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0 22px 0;">
        <tr>
            <td align="center">
                <!--[if mso]>
                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}" style="height:48px;v-text-anchor:middle;width:350px;" arcsize="16%" stroke="f" fillcolor="#059669">
                <w:anchorlock/>
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:15px;font-weight:bold;">View {{ $prospect->creator }}'s Time Capsule Concept &rarr;</center>
                </v:roundrect>
                <![endif]-->
                <!--[if !mso]><!-- -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" bgcolor="#059669" style="border-radius: 8px; background-color: #059669; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);">
                            <a href="https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}" target="_blank" class="mobile-btn" style="display: inline-block; padding: 14px 34px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 8px; background-color: #059669; text-align: center; mso-padding-alt: 0;">
                                View {{ $prospect->creator }}'s Time Capsule Concept &rarr;
                            </a>
                        </td>
                    </tr>
                </table>
                <!--<![endif]-->
            </td>
        </tr>
    </table>

    <!-- Dribbble-style Fallback Link Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f8fafc" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; border-collapse: separate; margin-bottom: 26px;">
        <tr>
            <td style="padding: 14px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; color: #64748b; line-height: 1.55;">
                <strong style="color: #0f172a; display: block; margin-bottom: 3px;">Having trouble with the button?</strong>
                Copy and paste this private draft URL into your browser:<br>
                <a href="https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}" target="_blank" style="color: #059669; word-break: break-all; text-decoration: underline; font-weight: 500;">
                    https://getfanvault.com/with/{{ Str::slug($prospect->creator) }}
                </a>
            </td>
        </tr>
    </table>

    <!-- Founder Personal Signature & Reply Note -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top: 1px solid #f1f5f9; padding-top: 22px;">
        <tr>
            <td style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; color: #4b5563; line-height: 1.55;">
                <div>Warm regards,</div>
                <div style="font-weight: 700; color: #0f172a; font-size: 15px; margin-top: 4px;">Oluwatobi Solomon</div>
                <div style="color: #64748b; font-size: 12px;">Founder, FanVault</div>
                <div style="margin-top: 4px;">
                    <a href="mailto:oluwatobi@getfanvault.com" style="color: #059669; text-decoration: none; font-weight: 600;">oluwatobi@getfanvault.com</a>
                    &nbsp;&middot;&nbsp;
                    <a href="https://getfanvault.com" style="color: #64748b; text-decoration: none;">getfanvault.com</a>
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 16px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#ecfdf5" style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; border-collapse: separate;">
                    <tr>
                        <td style="padding: 12px 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; color: #065f46; line-height: 1.55;">
                            &#128161; <strong>Quick Note:</strong> Just hit <strong>Reply</strong> to this email! It goes straight to my personal inbox at <strong style="text-decoration: underline;">oluwatobi@getfanvault.com</strong> &mdash; happy to answer any questions or set up a test capsule for {{ $prospect->creator }} in 5 minutes.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</x-mail.layout>
