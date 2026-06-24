<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $emailTitle ?? 'Notification' }}</title>
</head>

@php
    $companyName = $settings->company_name
        ?? $settings->portal_name
        ?? config('app.name', 'SiteDesk');

    $logoUrl = null;

    if (!empty($settings?->logo_path)) {
        $logoUrl = str_starts_with($settings->logo_path, ['http://', 'https://'])
            ? $settings->logo_path
            : asset($settings->logo_path);
    }
@endphp

<body style="margin: 0; padding: 0; background: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #111827;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background: #f3f4f6; margin: 0; padding: 24px 0;">
        <tr>
            <td align="center" style="padding: 0 12px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width: 680px; background: #ffffff; border: 1px solid #d1d5db;">
                    <tr>
                        <td style="border-top: 6px solid #111827; padding: 22px 28px 18px 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="vertical-align: middle;">
                                        @if ($logoUrl)
                                            <img src="{{ $logoUrl }}"
                                                 alt="{{ $companyName }}"
                                                 style="display: block; max-height: 64px; max-width: 220px; width: auto; height: auto; border: 0;">
                                        @else
                                            <div style="font-size: 22px; line-height: 1.2; font-weight: bold; color: #111827;">
                                                {{ $companyName }}
                                            </div>
                                        @endif
                                    </td>

                                    <td align="right" style="vertical-align: middle; font-size: 12px; color: #4b5563;">
                                        {{ $emailLabel ?? 'Notification' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 28px 28px 28px;">
                            <h1 style="font-size: 24px; line-height: 1.25; margin: 0 0 16px 0; color: #111827;">
                                {{ $emailTitle ?? 'Notification' }}
                            </h1>

                            @if (!empty($emailIntro))
                                <p style="font-size: 15px; line-height: 1.6; margin: 0 0 20px 0; color: #374151;">
                                    {{ $emailIntro }}
                                </p>
                            @endif

                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td style="background: #f9fafb; border-top: 1px solid #d1d5db; padding: 18px 28px;">
                            @if (!empty($footerNote))
                                <p style="font-size: 12px; line-height: 1.5; color: #4b5563; margin: 0 0 8px 0;">
                                    {{ $footerNote }}
                                </p>
                            @endif

                            <p style="font-size: 12px; line-height: 1.5; color: #4b5563; margin: 0;">
                                Sent using SiteDesk —
                                <a href="https://ckenterprises.co.uk"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   style="color: #111827; text-decoration: underline;">
                                    A CK Enterprises UK Product
                                </a>.
                            </p>
                        </td>
                    </tr>
                </table>

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width: 680px;">
                    <tr>
                        <td align="center" style="padding: 12px 0 0 0; font-size: 11px; line-height: 1.4; color: #6b7280;">
                            This is an automated email. Please do not reply directly.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>