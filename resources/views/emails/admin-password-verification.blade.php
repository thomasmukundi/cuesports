@extends('emails.layout')

@section('title', 'Admin Password Change Verification')

@section('content')
    <h2>Hello {{ $name }}!</h2>
    
    <div class="warning">
        <strong>🔐 Admin Password Change Request</strong>
    </div>
    
    <p>A request has been made to change the admin password for your CueSports Kenya account.</p>
    
    <div class="code-box">
        <p style="margin-bottom: 10px; color: #374151; font-weight: bold;">Your verification code is:</p>
        <div class="verification-code">{{ $verification_code }}</div>
        <p style="margin-top: 10px; color: #64748b; font-size: 14px;">This code will expire in 15 minutes</p>
    </div>
    
    <div style="background-color: #fef3c7; padding: 20px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #f59e0b;">
        <h4 style="margin-top: 0; color: #92400e;">⚠️ Security Notice</h4>
        <ul style="margin-bottom: 0; color: #92400e; line-height: 1.6;">
            <li>Only use this code if you initiated the password change request</li>
            <li>Never share this code with anyone</li>
            <li>If you didn't request this change, please contact support immediately</li>
            <li>This code is only valid for 15 minutes</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $app_url ?? config('app.url') }}/admin/change-password" class="button">
            🔐 Complete Password Change
        </a>
    </div>
    
    <div style="background-color: #f0f9ff; padding: 15px; border-radius: 6px; margin: 20px 0; text-align: center;">
        <p style="margin: 0; color: #64748b; font-size: 14px;">
            <strong>Need Help?</strong> Contact support at 
            <a href="mailto:admin@seroxideentertainment.co.ke" style="color: #6366f1;">admin@seroxideentertainment.co.ke</a>
        </p>
    </div>
    
    <p>For your security, this verification code will automatically expire in 15 minutes.</p>
    
    <p style="margin-top: 30px;">
        Best regards,<br>
        <strong>The CueSports Kenya Security Team</strong><br>
        <small style="color: #64748b;">Kenya's Premier Pool Tournament Platform</small>
    </p>
@endsection
