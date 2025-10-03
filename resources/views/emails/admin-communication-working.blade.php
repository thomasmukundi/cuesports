@extends('emails.layout')

@section('title', 'Important Message from CueSports Africa')

@section('content')
    <h2>Hello {{ $name ?? 'User' }}!</h2>
    
    <p>We hope this message finds you well. The CueSports Africa administration team has an important message for you.</p>
    
    <h3 style="color: #374151; font-size: 20px; margin: 25px 0 15px 0;">{{ $email_subject ?? 'Important Update' }}</h3>
    
    <div style="line-height: 1.8; color: #4b5563; font-size: 16px; margin: 20px 0;">
        {!! nl2br(e($email_content ?? 'No message content available.')) !!}
    </div>
    
    @if($action_required ?? false)
    <div class="warning">
        <strong>⚠️ Action Required:</strong> Please check your mobile app for any updates or required actions related to this message.
    </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $app_url ?? config('app.url') }}" class="button">
            📱 Open CueSports Africa App
        </a>
    </div>
    
    <div style="background-color: #e8f4fd; padding: 20px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #0ea5e9;">
        <h4 style="margin-top: 0; color: #0369a1;">📢 Stay Connected</h4>
        <p style="margin-bottom: 0; color: #0c4a6e;">
            Keep your app updated and notifications enabled to receive the latest tournament announcements, 
            match schedules, and important updates from CueSports Africa.
        </p>
    </div>
    
    <div style="background-color: #f0f9ff; padding: 15px; border-radius: 6px; margin: 20px 0; text-align: center;">
        <p style="margin: 0; color: #64748b; font-size: 14px;">
            <strong>Need Help?</strong> Contact our support team at 
            <a href="mailto:admin@seroxideentertainment.co.ke" style="color: #6366f1;">admin@seroxideentertainment.co.ke</a>
        </p>
    </div>
    
    <p>Thank you for being part of the CueSports Africa community. Together, we're building the premier pool tournament platform in Africa!</p>
    
    <p style="margin-top: 30px;">
        Best regards,<br>
        <strong>The CueSports Africa Administration Team</strong><br>
        <small style="color: #64748b;">Africa's Premier Pool Tournament Platform</small>
    </p>
@endsection
