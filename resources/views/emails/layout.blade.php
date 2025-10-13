<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            padding: 25px;
            border: 1px solid #dee2e6;
            box-shadow: none;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #212529;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #6c757d;
            font-size: 14px;
        }
        .content {
            margin-bottom: 30px;
        }
        .code-box {
            background-color: #f8f9fa;
            border: 2px solid #6366f1;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .verification-code {
            font-size: 32px;
            font-weight: bold;
            color: #6366f1;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 12px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ $app_name ?? config('app.name') }}</div>
            <div class="subtitle">Kenya's Premier Pool Tournament Platform</div>
        </div>
        
        <div class="content">
            @yield('content')
        </div>
        
        <div class="footer">
            <p>This email was sent from {{ $app_name ?? config('app.name') }}</p>
            <p>If you didn't request this email, please ignore it.</p>
            <p>&copy; {{ date('Y') }} {{ $app_name ?? config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
