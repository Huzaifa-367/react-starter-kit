<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? '' }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            -ms-text-size-adjust: none;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f3f4f6;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .header {
            padding: 32px;
            text-align: center;
            background: linear-gradient(135deg, {{ $primaryColor ?? '#4F46E5' }} 0%, #312E81 100%);
        }
        .header img {
            max-height: 48px;
            margin-bottom: 12px;
            display: inline-block;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 40px 32px;
            line-height: 1.6;
            font-size: 16px;
        }
        .content h2 {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .content p {
            margin-top: 0;
            margin-bottom: 24px;
        }
        .button-container {
            text-align: center;
            margin: 32px 0;
        }
        .button {
            display: inline-block;
            background-color: {{ $primaryColor ?? '#4F46E5' }};
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            font-weight: 600;
            font-size: 16px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2), 0 2px 4px -1px rgba(79, 70, 229, 0.1);
        }
        .footer {
            padding: 32px;
            background-color: #f9fafb;
            border-top: 1px solid #f3f4f6;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }
        .footer a {
            color: {{ $primaryColor ?? '#4F46E5' }};
            text-decoration: none;
        }
        .otp-box {
            background-color: #f3f4f6;
            border: 2px dashed #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0.1em;
            color: #111827;
            margin: 24px 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                @if(!empty($logoUrl))
                    <img src="{{ url($logoUrl) }}" alt="{{ $appName }}">
                @endif
                <h1>{{ $appName }}</h1>
            </div>
            <div class="content">
                @yield('content')
            </div>
            <div class="footer">
                <p>{{ $footerText }}</p>
                <p>If you have any questions, contact us at <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
            </div>
        </div>
    </div>
</body>
</html>
