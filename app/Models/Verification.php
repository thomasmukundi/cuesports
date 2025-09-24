<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Services\EmailService;

class Verification extends Model
{
    use HasFactory;

    protected $fillable = [
        'verification_type',
        'code',
        'email',
        'user_id',
        'expires_at',
        'is_used',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
        'metadata' => 'array',
    ];

    // Verification types
    const TYPE_SIGN_UP = 'sign_up';
    const TYPE_RESET_PASSWORD = 'reset_password';
    const TYPE_CHANGE_EMAIL = 'change_email';

    /**
     * Generate a new 6-digit verification code
     */
    public static function generateCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create or update verification code for email
     */
    public static function createOrUpdate(string $email, string $type, ?int $userId = null, array $metadata = []): self
    {
        // Delete any existing unused verification for this email and type
        self::where('email', $email)
            ->where('verification_type', $type)
            ->where('is_used', false)
            ->delete();

        $verification = self::create([
            'verification_type' => $type,
            'code' => self::generateCode(),
            'email' => $email,
            'user_id' => $userId,
            'expires_at' => Carbon::now()->addMinutes(15), // 15 minutes expiry
            'is_used' => false,
            'metadata' => $metadata,
        ]);

        // Send email using EmailService
        $verification->sendEmail();

        return $verification;
    }

    /**
     * Send verification email based on type
     */
    public function sendEmail(): bool
    {
        $emailService = new EmailService();
        $userName = $this->metadata['name'] ?? $this->metadata['registration_data']['first_name'] ?? 'User';

        try {
            switch ($this->verification_type) {
                case self::TYPE_SIGN_UP:
                    return $emailService->sendVerificationCode($this->email, $this->code, $userName);
                
                case self::TYPE_RESET_PASSWORD:
                    return $emailService->sendPasswordResetCode($this->email, $this->code, $userName);
                
                case self::TYPE_CHANGE_EMAIL:
                    return $emailService->sendVerificationCode($this->email, $this->code, $userName);
                
                default:
                    \Log::warning('Unknown verification type: ' . $this->verification_type);
                    return false;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email', [
                'verification_id' => $this->id,
                'email' => $this->email,
                'type' => $this->verification_type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verify code for email and type
     */
    public static function verify(string $email, string $code, string $type): ?self
    {
        return self::where('email', $email)
            ->where('code', $code)
            ->where('verification_type', $type)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Mark verification as used
     */
    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }

    /**
     * Check if verification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active (unused and not expired) verifications
     */
    public function scopeActive($query)
    {
        return $query->where('is_used', false)
                    ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Clean up expired verifications (can be called via scheduled task)
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', Carbon::now()->subHours(24))->delete();
    }
}
