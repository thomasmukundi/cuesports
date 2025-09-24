<?php

namespace App\Events;

use App\Models\PoolMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchResultSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $match;
    public $submittedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(PoolMatch $match, $submittedBy)
    {
        $this->match = $match->load(['player1', 'player2', 'tournament']);
        $this->submittedBy = $submittedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        $otherPlayerId = $this->match->player_1_id === $this->submittedBy 
            ? $this->match->player_2_id 
            : $this->match->player_1_id;
            
        return new PrivateChannel('user.' . $otherPlayerId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'match.result.submitted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'match_id' => $this->match->id,
            'tournament' => $this->match->tournament->name,
            'player_1_points' => $this->match->player_1_points,
            'player_2_points' => $this->match->player_2_points,
            'submitted_by' => $this->submittedBy,
            'message' => 'Match results submitted. Please confirm or reject.',
        ];
    }
}
