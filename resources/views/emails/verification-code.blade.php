@extends('emails.layout')

@section('title', 'Email Verification Code')

@section('content')
    <h2>Hello {{ $name }}!</h2>
    
    <p>Thank you for registering with {{ $app_name }}. To complete your registration, please use the verification code below:</p>
    
    <div class="code-box">
        <div class="verification-code">{{ $code }}</div>
        <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">Enter this code in the app to verify your email</p>
    </div>
    
    <div class="warning">
        <strong>Important:</strong> This code will expire in 10 minutes for security reasons.
    </div>
    
    <p>If you didn't create an account with {{ $app_name }}, you can safely ignore this email.</p>
    
    <p>Welcome to the exciting world of competitive pool tournaments!</p>
    
    <p>Best regards,<br>
    The {{ $app_name }} Team</p>
@endsection
