@extends('emails.layout')

@section('title', 'Welcome to CueSports Africa')

@section('content')
    <h2>Welcome {{ $name }}!</h2>
    
    <div class="success">
        <strong>Congratulations!</strong> Your account has been successfully created and verified.
    </div>
    
    <p>You're now part of Africa's premier pool tournament community! Here's what you can do:</p>
    
    <ul style="line-height: 1.8; margin: 20px 0;">
        <li><strong>Join Tournaments:</strong> Participate in community, county, regional, and national tournaments</li>
        <li><strong>Track Your Progress:</strong> Monitor your ranking and tournament history</li>
        <li><strong>Connect with Players:</strong> Find and challenge other players in your area</li>
        <li><strong>Earn Achievements:</strong> Unlock badges and climb the leaderboards</li>
    </ul>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $app_url ?? config('app.url') }}" class="button">Start Playing Now</a>
    </div>
    
    <p>Ready to make your mark in the world of competitive pool? Download our mobile app and start your journey to becoming a champion!</p>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">
        <strong>Need Help?</strong><br>
        Check out our getting started guide or contact our support team if you have any questions.
    </div>
    
    <p>Good luck and may the best player win!</p>
    
    <p>Best regards,<br>
    The {{ $app_name }} Team</p>
@endsection
