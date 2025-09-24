@extends('emails.layout')

@section('title', 'Password Reset Code')

@section('content')
    <h2>Hello {{ $name }}!</h2>
    
    <p>We received a request to reset your password for your {{ $app_name }} account. Use the code below to reset your password:</p>
    
    <div class="code-box">
        <div class="verification-code">{{ $code }}</div>
        <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">Enter this code in the app to reset your password</p>
    </div>
    
    <div class="warning">
        <strong>Security Notice:</strong> This code will expire in 15 minutes. If you didn't request a password reset, please ignore this email and your password will remain unchanged.
    </div>
    
    <p>For your security, never share this code with anyone. Our team will never ask for your verification code.</p>
    
    <p>If you continue to have problems, please contact our support team.</p>
    
    <p>Best regards,<br>
    The {{ $app_name }} Team</p>
@endsection
