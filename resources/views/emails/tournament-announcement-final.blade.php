@extends('emails.layout')

@section('title', 'New Tournament Announcement')

@section('content')
    <h2>Hello {{ $name }},</h2>
    
    <div style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; margin: 20px 0;">
        <h1 style="margin: 0; font-size: 24px; color: #212529; text-align: center;">New Tournament Available</h1>
        <p style="margin: 8px 0 0 0; font-size: 16px; color: #6c757d; text-align: center;">Registration is now open</p>
    </div>
    
    <p>A new tournament has been announced and is now available for registration.</p>
    
    <div style="background-color: #ffffff; border: 1px solid #dee2e6; padding: 20px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #212529; font-size: 20px; text-align: center; border-bottom: 1px solid #dee2e6; padding-bottom: 10px;">
            {{ $tournament_title }}
        </h3>
        
        @if($tournament_info)
        <div style="background-color: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 3px solid #6c757d;">
            <p style="margin: 0; line-height: 1.5; color: #495057;">{{ $tournament_info }}</p>
        </div>
        @endif
        
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 8px 0; font-weight: bold; color: #495057;">Tournament Date:</td>
                <td style="padding: 8px 0; color: #6c757d;">{{ $tournament_date ?? 'TBD' }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 8px 0; font-weight: bold; color: #495057;">Registration Deadline:</td>
                <td style="padding: 8px 0; color: #dc3545;">{{ $registration_deadline }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 8px 0; font-weight: bold; color: #495057;">Level:</td>
                <td style="padding: 8px 0; color: #6c757d;">{{ ucfirst($tournament_level) }}</td>
            </tr>
            @if($entry_fee)
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 8px 0; font-weight: bold; color: #495057;">Entry Fee:</td>
                <td style="padding: 8px 0; color: #6c757d;">KES {{ number_format($entry_fee) }}</td>
            </tr>
            @endif
            @if($prize_pool)
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 8px 0; font-weight: bold; color: #495057;">Prize Pool:</td>
                <td style="padding: 8px 0; color: #28a745; font-weight: bold;">KES {{ number_format($prize_pool) }}</td>
            </tr>
            @endif
            @if($max_participants)
            <tr>
                <td style="padding: 8px 0; font-weight: bold; color: #495057;">Max Participants:</td>
                <td style="padding: 8px 0; color: #6c757d;">{{ $max_participants }} players</td>
            </tr>
            @endif
        </table>
    </div>
    
    <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; text-align: center;">
        <strong>Important:</strong> Registration spots are limited and fill up quickly. Register now to secure your place.
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $app_url ?? config('app.url') }}" class="button" style="background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">
            Register in Mobile App
        </a>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0; border: 1px solid #dee2e6;">
        <h4 style="margin-top: 0; color: #495057; font-size: 16px;">Registration Instructions:</h4>
        <ol style="color: #6c757d; line-height: 1.6; margin-bottom: 0; padding-left: 20px;">
            <li>Open your CueSports Africa mobile app</li>
            <li>Navigate to the "Tournaments" section</li>
            <li>Find "{{ $tournament_title }}" and tap "Register"</li>
            <li>Complete your registration and payment (if applicable)</li>
            <li>Prepare for competition</li>
        </ol>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 12px; margin: 20px 0; text-align: center; border: 1px solid #dee2e6;">
        <p style="margin: 0; color: #6c757d; font-size: 14px;">
            <strong>Questions?</strong> Contact us at 
            <a href="mailto:kollintventures@gmail.com" style="color: #007bff; text-decoration: none;">kollintventures@gmail.com</a>
        </p>
    </div>
    
    <p>We look forward to your participation in this tournament.</p>
    
    <p style="margin-top: 25px;">
        Best regards,<br>
        <strong>The CueSports Africa Tournament Team</strong><br>
        <small style="color: #6c757d;">Kenya's Premier Pool Tournament Platform</small>
    </p>
@endsection
