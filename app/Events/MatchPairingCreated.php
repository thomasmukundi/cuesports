<?php

namespace App\Events;

use App\Models\PoolMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchPairingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $match;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(PoolMatch $match, string $message)
    {
        $this->match = $match->load(['player1', 'player2', 'tournament']);
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel('user.' . $this->match->player_1_id),
            new PrivateChannel('user.' . $this->match->player_2_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'match.pairing.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'match_id' => $this->match->id,
            'tournament' => $this->match->tournament->name,
            'opponent' => [
                'player1' => [
                    'id' => $this->match->player1->id,
                    'name' => $this->match->player1->name,
                    'username' => $this->match->player1->username,
                ],
                'player2' => [
                    'id' => $this->match->player2->id,
                    'name' => $this->match->player2->name,
                    'username' => $this->match->player2->username,
                ],
            ],
            'level' => $this->match->level,
            'round' => $this->match->round_name,
            'message' => $this->message,
        ];
    }
}
