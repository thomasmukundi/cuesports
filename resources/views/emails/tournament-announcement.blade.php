@extends('emails.layout')

@section('title', 'New Tournament Available - ' . ($tournament_name ?? 'CueSports Kenya'))

@section('content')
    <h2>Hello {{ $name }}!</h2>
    
    <div class="success">
        <strong>🏆 New Tournament Alert!</strong> A new tournament is now available for registration.
    </div>
    
    <div style="background-color: #f8f9fa; padding: 25px; border-radius: 10px; margin: 20px 0; border: 2px solid #6366f1;">
        <h3 style="margin-top: 0; color: #6366f1; font-size: 24px;">{{ $tournament_name }}</h3>
        
        <div style="display: grid; gap: 10px; margin: 15px 0;">
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                <strong>📅 Registration Deadline:</strong>
                <span>{{ $registration_deadline }}</span>
            </div>
            
            @if($tournament_date ?? false)
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                <strong>🎯 Tournament Date:</strong>
                <span>{{ $tournament_date }}</span>
            </div>
            @endif
            
            @if($entry_fee ?? false)
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                <strong>💰 Entry Fee:</strong>
                <span>KES {{ number_format($entry_fee, 2) }}</span>
            </div>
            @endif
            
            @if($prize_pool ?? false)
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                <strong>🏆 Prize Pool:</strong>
                <span>KES {{ number_format($prize_pool, 2) }}</span>
            </div>
            @endif
            
            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                <strong>📍 Level:</strong>
                <span>{{ ucfirst($tournament_level ?? 'Community') }}</span>
            </div>
            
            @if($max_participants ?? false)
            <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                <strong>👥 Max Participants:</strong>
                <span>{{ $max_participants }} players</span>
            </div>
            @endif
        </div>
        
        @if($tournament_description ?? false)
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0; color: #4b5563; line-height: 1.6;">{{ $tournament_description }}</p>
        </div>
        @endif
    </div>
    
    <div class="warning">
        <strong>⏰ Don't Miss Out!</strong> Registration spots are limited and fill up quickly. Register now to secure your place in this exciting tournament.
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $app_url ?? config('app.url') }}" class="button" style="font-size: 18px; padding: 15px 30px;">Register Now in App</a>
    </div>
    
    <div style="background-color: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h4 style="margin-top: 0; color: #0ea5e9;">📱 How to Register:</h4>
        <ol style="margin: 10px 0; padding-left: 20px; line-height: 1.8;">
            <li>Open your CueSports Kenya mobile app</li>
            <li>Go to the "Tournaments" section</li>
            <li>Find "{{ $tournament_name }}" in the list</li>
            <li>Tap "Register" and complete the payment</li>
            <li>You're all set! Good luck! 🍀</li>
        </ol>
    </div>
    
    <p>This is your chance to compete against the best players in Kenya and climb the leaderboards. Show your skills and aim for the top prize!</p>
    
    <div style="background-color: #fef3c7; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #f59e0b;">
        <strong>💡 Pro Tip:</strong> Make sure your profile and location information are up to date in the app for the best tournament experience.
    </div>
    
    <p>Good luck and may the best player win!</p>
    
    <p>Best regards,<br>
    The {{ $app_name }} Tournament Team</p>
@endsection
