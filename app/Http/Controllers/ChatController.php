<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\PoolMatch;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Get messages for a match
     */
    public function getMatchMessages($matchId)
    {
        $match = PoolMatch::findOrFail($matchId);
        $user = Auth::user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $messages = ChatMessage::where('match_id', $matchId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();
        
        return response()->json($messages);
    }

    /**
     * Send a message in match chat
     */
    public function sendMessage(Request $request, $matchId)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000'
        ]);
        
        $match = PoolMatch::findOrFail($matchId);
        $user = Auth::user();
        
        // Check if user is part of this match
        if ($match->player_1_id !== $user->id && $match->player_2_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        DB::beginTransaction();
        try {
            $message = ChatMessage::create([
                'match_id' => $matchId,
                'sender_id' => $user->id,
                'message' => $validated['message']
            ]);
            
            // Notify the other player
            $otherPlayerId = $match->player_1_id === $user->id 
                ? $match->player_2_id 
                : $match->player_1_id;
            
            Notification::create([
                'player_id' => $otherPlayerId,
                'type' => 'other',
                'message' => "{$user->name} sent you a message: " . substr($validated['message'], 0, 50) . (strlen($validated['message']) > 50 ? '...' : ''),
                'data' => [
                    'match_id' => $matchId,
                    'message_id' => $message->id,
                    'chat_message' => $validated['message']
                ]
            ]);
            
            DB::commit();
            
            // Load sender relationship for response
            $message->load('sender');
            
            // Here you would broadcast the message via websocket
            // broadcast(new MessageSent($message))->toOthers();
            
            return response()->json($message, 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to send message'], 500);
        }
    }

    /**
     * Get all conversations for a user
     */
    public function getConversations()
    {
        $user = Auth::user();
        
        // Get all matches where user is a participant
        $matches = PoolMatch::where(function($q) use ($user) {
            $q->where('player_1_id', $user->id)
              ->orWhere('player_2_id', $user->id);
        })->with(['player1', 'player2', 'tournament'])
          ->withCount('chatMessages')
          ->having('chat_messages_count', '>', 0)
          ->orderBy('updated_at', 'desc')
          ->get();
        
        // Transform to conversation format
        $conversations = $matches->map(function($match) use ($user) {
            $otherPlayer = $match->player_1_id === $user->id 
                ? $match->player2 
                : $match->player1;
            
            $lastMessage = $match->chatMessages()
                ->orderBy('created_at', 'desc')
                ->first();
            
            return [
                'match_id' => $match->id,
                'tournament' => $match->tournament->name,
                'other_player' => [
                    'id' => $otherPlayer->id,
                    'name' => $otherPlayer->name,
                    'username' => $otherPlayer->username
                ],
                'last_message' => $lastMessage ? [
                    'message' => $lastMessage->message,
                    'sender_id' => $lastMessage->sender_id,
                    'created_at' => $lastMessage->created_at
                ] : null,
                'unread_count' => $match->chatMessages()
                    ->where('sender_id', '!=', $user->id)
                    ->where('is_read', false)
                    ->count()
            ];
        });
        
        return response()->json($conversations);
    }
}
