<?php

namespace App\Events;

use App\Models\Tournament;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tournament;
    public $winners;

    /**
     * Create a new event instance.
     */
    public function __construct(Tournament $tournament, array $winners)
    {
        $this->tournament = $tournament;
        $this->winners = $winners;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('tournaments');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'tournament.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'tournament_id' => $this->tournament->id,
            'tournament_name' => $this->tournament->name,
            'winners' => $this->winners,
            'message' => "Tournament {$this->tournament->name} has been completed!",
        ];
    }
}
