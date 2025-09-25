<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'username',
        'phone',
        'profile_image',
        'community_id',
        'county_id',
        'region_id',
        'total_points',
        'last_login',
        'is_admin',
        'email_verified_at',
        'fcm_token',
        'fcm_token_updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login' => 'datetime',
        ];
    }

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'registered_users', 'player_id', 'tournament_id')
            ->withPivot('payment_status', 'status', 'payment_intent_id')
            ->withTimestamps();
    }

    public function matchesAsPlayer1()
    {
        return $this->hasMany(PoolMatch::class, 'player_1_id');
    }

    public function matchesAsPlayer2()
    {
        return $this->hasMany(PoolMatch::class, 'player_2_id');
    }

    public function allMatches()
    {
        return PoolMatch::where('player_1_id', $this->id)
            ->orWhere('player_2_id', $this->id);
    }

    public function wonMatches()
    {
        return $this->hasMany(PoolMatch::class, 'winner_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'player_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function winnerRecords()
    {
        return $this->hasMany(Winner::class, 'player_id');
    }

    public function updateTotalPoints(): void
    {
        $this->total_points = $this->allMatches()
            ->where('status', 'completed')
            ->get()
            ->sum(function ($match) {
                return $match->player_1_id == $this->id 
                    ? $match->player_1_points 
                    : $match->player_2_points;
            });
        $this->save();
    }

    public function isAdmin(): bool
    {
        return $this->email === 'admin@cuesports.com' || 
               $this->email === 'test-admin@cuesports.com' || 
               $this->email === 'test-admin-flow@cuesports.com' ||
               $this->email === 'mukundithomas8@gmail.com' ||
               str_contains($this->email, 'admin-') && str_ends_with($this->email, '@cuesports.com');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get the profile image URL attribute.
     * Converts relative paths to full URLs for API responses.
     */
    public function getProfileImageUrlAttribute()
    {
        if (!$this->profile_image) {
            return null;
        }

        // Already an absolute URL
        if (str_starts_with($this->profile_image, 'http')) {
            return $this->profile_image;
        }

        // If legacy '/storage/...' path is stored, keep existing behavior for backward compatibility
        if (str_starts_with($this->profile_image, '/storage/')) {
            return rtrim(config('app.url'), '/') . $this->profile_image;
        }

        // Otherwise treat as a disk-relative key and use Storage URL (works for s3-compatible or local)
        try {
            return \Storage::url($this->profile_image);
        } catch (\Throwable $e) {
            // Fallback to app URL if Storage::url fails for any reason
            return rtrim(config('app.url'), '/') . '/storage/' . ltrim($this->profile_image, '/');
        }
    }
}
