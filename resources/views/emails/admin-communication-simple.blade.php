<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #6366f1;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #6366f1;
            margin-bottom: 10px;
        }
        .message-content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #6366f1;
        }
        .message-title {
            margin-top: 0;
            color: #374151;
            font-size: 18px;
            font-weight: bold;
        }
        .message-body {
            line-height: 1.6;
            color: #4b5563;
        }
        .action-required {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
        }
        .button {
            display: inline-block;
            background-color: #6366f1;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            background-color: #f0f9ff;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #0ea5e9;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ $app_name ?? 'CueSports Kenya' }}</div>
            <p>Official Communication</p>
        </div>
        
        <h2>Hello {{ $name }}!</h2>
        
        <div class="message-content">
            <h3 class="message-title">{{ $subject }}</h3>
            <div class="message-body">
                {!! nl2br(e($message)) !!}
            </div>
        </div>
        
        @if($action_required ?? false)
        <div class="action-required">
            <strong>Action Required:</strong> Please check your mobile app for any updates or required actions.
        </div>
        @endif
        
        <div style="text-align: center;">
            <a href="{{ $app_url ?? config('app.url') }}" class="button">Open CueSports App</a>
        </div>
        
        <p>Stay connected and keep competing! Download our mobile app if you haven't already to stay updated with the latest tournaments, matches, and announcements.</p>
        
        <div class="footer">
            <strong>Need Help?</strong><br>
            If you have any questions or need assistance, please contact our support team at <a href="mailto:admin@seroxideentertainment.co.ke">admin@seroxideentertainment.co.ke</a>
        </div>
        
        <p>Thank you for being part of the CueSports Kenya community!</p>
        
        <p>Best regards,<br>
        The {{ $app_name ?? 'CueSports Kenya' }} Administration Team</p>
    </div>
</body>
</html>
