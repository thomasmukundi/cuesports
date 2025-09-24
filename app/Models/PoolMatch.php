<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoolMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'match_name',
        'player_1_id',
        'player_2_id',
        'player_1_points',
        'player_2_points',
        'player_1_score',
        'player_2_score',
        'winner_id',
        'bye_player_id',
        'level',
        'level_name',
        'round_name',
        'tournament_id',
        'status',
        'group_id',
        'proposed_dates',
        'player_1_preferred_dates',
        'player_2_preferred_dates',
        'scheduled_date',
        'scheduled_time',
        'submitted_by',
        'result_submitted_by',
        'dispute_reason',
        'completed_at',
        'video_url'
    ];

    protected $casts = [
        'player_1_preferred_dates' => 'array',
        'player_2_preferred_dates' => 'array',
        'proposed_dates' => 'array',
        'scheduled_date' => 'datetime',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player1()
    {
        return $this->belongsTo(User::class, 'player_1_id');
    }

    public function player2()
    {
        return $this->belongsTo(User::class, 'player_2_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function byePlayer()
    {
        return $this->belongsTo(User::class, 'bye_player_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function messages()
    {
        return $this->hasMany(MatchMessage::class, 'match_id');
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'forfeit']);
    }

    public function determineWinner(): void
    {
        if ($this->player_1_points !== null && $this->player_2_points !== null) {
            $this->winner_id = $this->player_1_points > $this->player_2_points 
                ? $this->player_1_id 
                : $this->player_2_id;
        }
    }
}
