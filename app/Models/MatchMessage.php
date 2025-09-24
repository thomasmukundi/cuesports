<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'sender_id',
        'message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the match that owns the message
     */
    public function match()
    {
        return $this->belongsTo(PoolMatch::class, 'match_id');
    }

    /**
     * Get the user who sent the message
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
