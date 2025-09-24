<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegistration extends Model
{
    use HasFactory;

    protected $table = 'registered_users';

    protected $fillable = [
        'player_id',
        'tournament_id',
        'payment_status',
        'status',
        'payment_intent_id',
        'registration_date'
    ];

    protected $casts = [
        'registration_date' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
