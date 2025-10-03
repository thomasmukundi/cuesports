@extends('emails.layout')

@section('title', 'New Tournament Announcement')

@section('content')
    <h2>Hello {{ $name }}!</h2>
    
    <div style="text-align: center; background-color: ecfdf5; color: white; padding: 25px; border-radius: 12px; margin: 25px 0;">
        <h1 style="margin: 0; font-size: 28px;">NEW TOURNAMENT ALERT!</h1>
        <p style="margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;">Get ready to compete!</p>
    </div>
    
    <p>Exciting news! A new tournament has been announced and we think you'd be perfect for it.</p>
    
    <div style="background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 25px; margin: 25px 0;">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 24px; text-align: center;">
            🎱 {{ $tournament_title }}
        </h3>
        
        @if($tournament_info)
        <div style="background-color: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6366f1;">
            <p style="margin: 0; line-height: 1.6; color: #475569;">{{ $tournament_info }}</p>
        </div>
        @endif
        
        <div style="display: grid; gap: 15px; margin: 20px 0;">
            <div style="display: flex; align-items: center; padding: 12px; background-color: white; border-radius: 6px;">
                <span style="margin-right: 12px; font-size: 20px;">📅</span>
                <div>
                    <strong style="color: #1e293b;">Tournament Date:</strong>
                    <span style="color: #64748b;">{{ $tournament_date ?? 'TBD' }}</span>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; padding: 12px; background-color: white; border-radius: 6px;">
                <span style="margin-right: 12px; font-size: 20px;">⏰</span>
                <div>
                    <strong style="color: #1e293b;">Registration Deadline:</strong>
                    <span style="color: #dc2626;">{{ $registration_deadline }}</span>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; padding: 12px; background-color: white; border-radius: 6px;">
                <span style="margin-right: 12px; font-size: 20px;">🎯</span>
                <div>
                    <strong style="color: #1e293b;">Level:</strong>
                    <span style="color: #059669;">{{ ucfirst($tournament_level) }}</span>
                </div>
            </div>
            
            @if($entry_fee)
            <div style="display: flex; align-items: center; padding: 12px; background-color: white; border-radius: 6px;">
                <span style="margin-right: 12px; font-size: 20px;">💰</span>
                <div>
                    <strong style="color: #1e293b;">Entry Fee:</strong>
                    <span style="color: #7c3aed;">KES {{ number_format($entry_fee) }}</span>
                </div>
            </div>
            @endif
            
            @if($prize_pool)
            <div style="display: flex; align-items: center; padding: 12px; background-color: white; border-radius: 6px;">
                <span style="margin-right: 12px; font-size: 20px;">🏆</span>
                <div>
                    <strong style="color: #1e293b;">Prize Pool:</strong>
                    <span style="color: #dc2626; font-weight: bold;">KES {{ number_format($prize_pool) }}</span>
                </div>
            </div>
            @endif
            
            @if($max_participants)
            <div style="display: flex; align-items: center; padding: 12px; background-color: white; border-radius: 6px;">
                <span style="margin-right: 12px; font-size: 20px;">👥</span>
                <div>
                    <strong style="color: #1e293b;">Max Participants:</strong>
                    <span style="color: #64748b;">{{ $max_participants }} players</span>
                </div>
            </div>
            @endif
        </div>
    </div>
    
    <div class="warning" style="text-align: center;">
        <strong>⚡ Don't Miss Out!</strong> Registration spots are limited and fill up quickly. 
        Secure your spot now to avoid disappointment.
    </div>
    
    <div style="text-align: center; margin: 35px 0;">
        <a href="{{ $app_url ?? config('app.url') }}" class="button" style="font-size: 18px; padding: 15px 30px;">
            Register Now in App
        </a>
    </div>
    
    <div style="background-color: #ecfdf5; padding: 20px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #10b981;">
        <h4 style="margin-top: 0; color: #065f46;">📱 How to Register:</h4>
        <ol style="color: #047857; line-height: 1.8; margin-bottom: 0;">
            <li>Open your CueSports Africa mobile app</li>
            <li>Navigate to the "Tournaments" section</li>
            <li>Find "{{ $tournament_title }}" and tap "Register"</li>
            <li>Complete your registration and payment (if applicable)</li>
            <li>Get ready to compete and win! 🏆</li>
        </ol>
    </div>
    
    <div style="background-color: #f0f9ff; padding: 15px; border-radius: 6px; margin: 20px 0; text-align: center;">
        <p style="margin: 0; color: #64748b; font-size: 14px;">
            <strong>Questions?</strong> Contact us at 
            <a href="mailto:admin@seroxideentertainment.co.ke" style="color: #6366f1;">admin@seroxideentertainment.co.ke</a>
        </p>
    </div>
    
    <p>Good luck, and may the best player win! We're excited to see you compete.</p>
    
    <p style="margin-top: 30px;">
        Best of luck,<br>
        <strong>The CueSports Africa Tournament Team</strong><br>
        <small style="color: #64748b;">Africa's Premier Pool Tournament Platform</small>
    </p>
@endsection
