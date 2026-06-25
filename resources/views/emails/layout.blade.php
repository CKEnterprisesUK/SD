@php
    use App\Models\PortalSetting;
    use Illuminate\Support\Str;

    $portalSettings = PortalSetting::current();

    $portalName = $portalSettings->portal_name ?? config('app.name', 'SiteDesk');
    $companyName = $portalSettings->company_name ?? $portalName;

    $logoPath = $portalSettings->logo_path ?? null;
    $logoUrl = null;

    if (is_string($logoPath) && $logoPath !== '') {
        $logoUrl = Str::startsWith($logoPath, ['http://', 'https://'])
            ? $logoPath
            : asset($logoPath);
    }

    $emailTitle = $title ?? $subject ?? $portalName;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $emailTitle }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
        }

        table {
            border-collapse: collapse;
        }

        a {
            color: #111827;
        }

        .wrapper {
            width: 100%;
            background: #f3f4f6;
            padding: 24px 0;
        }

        .container {
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d1d5db;
        }

        .header {
            padding: 24px;
            border-bottom: 1px solid #d1d5db;
            background: #ffffff;
        }

        .logo {
            max-width: 180px;
            max-height: 70px;
            height: auto;
            width: auto;
            display: block;
        }

        .brand-fallback {
            display: inline-block;
            border: 2px solid #111827;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 18px;
            letter-spacing: 0.02em;
        }

        .content {
            padding: 28px 24px;
            background: #ffffff;
        }

        .content h1,
        .content h2,
        .content h3 {
            color: #111827;
            margin-top: 0;
        }

        .content p {
            margin: 0 0 16px;
        }

        .button {
            display: inline-block;
            background: #111827;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 18px;
            font-weight: 700;
            font-size: 14px;
        }

        .panel {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            padding: 16px;
            margin: 18px 0;
        }

        .footer {
            padding: 20px 24px;
            border-top: 1px solid #d1d5db;
            background: #f9fafb;
            color: #6b7280;
            font-size: 12px;
        }

        .small {
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <table role="presentation" width="100%">
            <tr>
                <td align="center">
                    <table role="presentation" class="container" width="100%">
                        <tr>
                            <td class="header">
                                @if ($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="{{ $portalName }}" class="logo">
                                @else
                                    <span class="brand-fallback">
                                        {{ $portalName }}
                                    </span>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td class="content">
                                @hasSection('content')
                                    @yield('content')
                                @elseif (isset($slot))
                                    {{ $slot }}
                                @else
                                    @yield('body')
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td class="footer">
                                <p style="margin: 0 0 6px;">
                                    {{ $companyName }}
                                </p>

                                <p style="margin: 0;">
                                    This email was sent from {{ $portalName }}.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>