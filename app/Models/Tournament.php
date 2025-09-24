<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'special',
        'community_prize',
        'county_prize',
        'regional_prize',
        'national_prize',
        'area_scope',
        'area_name',
        'tournament_charge',
        'entry_fee',
        'max_participants',
        'start_date',
        'end_date',
        'registration_deadline',
        'status',
        'automation_mode',
        'winners',
        'created_by'
    ];

    protected $casts = [
        'special' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_deadline' => 'date',
        'community_prize' => 'decimal:2',
        'county_prize' => 'decimal:2',
        'regional_prize' => 'decimal:2',
        'national_prize' => 'decimal:2',
        'tournament_charge' => 'decimal:2',
        'entry_fee' => 'decimal:2',
    ];

    public function registeredUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'registered_users', 'tournament_id', 'player_id')
            ->withPivot('payment_status', 'status', 'payment_intent_id')
            ->withTimestamps();
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(PoolMatch::class);
    }

    public function winners(): HasMany
    {
        return $this->hasMany(Winner::class);
    }

    public function approvedPlayers()
    {
        return $this->registeredUsers()
            ->wherePivot('payment_status', 'paid')
            ->wherePivot('status', 'approved');
    }
}
