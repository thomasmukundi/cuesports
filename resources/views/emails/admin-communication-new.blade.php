@extends('emails.layout')

@section('title', 'Important Message from CueSports Kenya')

@section('content')
    <h2>Hello {{ $name }}!</h2>
    
    <div class="success">
        <strong>Message from CueSports Kenya Administration</strong>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6366f1;">
        <h3 style="margin-top: 0; color: #374151;">{{ $subject }}</h3>
        <div style="line-height: 1.6; color: #4b5563;">
            {!! nl2br(e($message)) !!}
        </div>
    </div>
    
    @if($action_required ?? false)
    <div class="warning">
        <strong>Action Required:</strong> Please check your mobile app for any updates or required actions.
    </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $app_url ?? config('app.url') }}" class="button">Open CueSports App</a>
    </div>
    
    <p>Stay connected and keep competing! Download our mobile app if you haven't already to stay updated with the latest tournaments, matches, and announcements.</p>
    
    <div style="background-color: #f0f9ff; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #0ea5e9;">
        <strong>Need Help?</strong><br>
        If you have any questions or need assistance, please contact our support team at <a href="mailto:admin@seroxideentertainment.co.ke">admin@seroxideentertainment.co.ke</a>
    </div>
    
    <p>Thank you for being part of the CueSports Kenya community!</p>
    
    <p>Best regards,<br>
    The {{ $app_name }} Administration Team</p>
@endsection
