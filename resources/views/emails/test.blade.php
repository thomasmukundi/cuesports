@extends('emails.layout')

@section('title', 'Test Email')

@section('content')
    <h2>Hello {{ $name }}!</h2>
    
    <div class="success">
        <strong>Success!</strong> This is a test email from {{ $app_name }}.
    </div>
    
    <p>If you're reading this, it means our email system is working correctly! 🎉</p>
    
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;">
        <h3 style="margin-top: 0;">Email Configuration Details:</h3>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Timestamp:</strong> {{ $timestamp }}</li>
            <li><strong>Environment:</strong> {{ $environment }}</li>
            <li><strong>Application:</strong> {{ $app_name }}</li>
        </ul>
    </div>
    
    <p>This test email confirms that:</p>
    <ul style="line-height: 1.8;">
        <li>✅ SMTP connection is working</li>
        <li>✅ Email templates are loading correctly</li>
        <li>✅ Mail configuration is properly set up</li>
        <li>✅ Email delivery is functional</li>
    </ul>
    
    <div class="warning">
        <strong>Note:</strong> This is an automated test email. No action is required from you.
    </div>
    
    <p>You can now confidently use the email system for user registration, password resets, and other notifications.</p>
    
    <p>Best regards,<br>
    The {{ $app_name }} Development Team</p>
@endsection
