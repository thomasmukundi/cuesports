<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Winner extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'position',
        'level',
        'level_name',
        'level_id',
        'tournament_id',
        'prize_awarded',
        'prize_amount',
        'points'
    ];

    protected $casts = [
        'prize_awarded' => 'boolean',
        'prize_amount' => 'decimal:2',
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
