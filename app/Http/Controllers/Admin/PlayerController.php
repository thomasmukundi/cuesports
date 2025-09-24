<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    /**
     * Get pending player registrations
     */
    public function pendingRegistrations()
    {
        $registrations = TournamentRegistration::with(['user', 'tournament'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'registrations' => $registrations
        ]);
    }

    /**
     * Approve a player registration
     */
    public function approveRegistration($registrationId)
    {
        $registration = TournamentRegistration::findOrFail($registrationId);
        
        if ($registration->status !== 'pending') {
            return response()->json([
                'message' => 'Registration is not pending approval'
            ], 400);
        }

        $registration->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id()
        ]);

        return response()->json([
            'message' => 'Registration approved successfully',
            'registration' => $registration->load(['user', 'tournament'])
        ]);
    }

    /**
     * Reject a player registration
     */
    public function rejectRegistration(Request $request, $registrationId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $registration = TournamentRegistration::findOrFail($registrationId);
        
        if ($registration->status !== 'pending') {
            return response()->json([
                'message' => 'Registration is not pending approval'
            ], 400);
        }

        $registration->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $request->reason
        ]);

        return response()->json([
            'message' => 'Registration rejected successfully',
            'registration' => $registration->load(['user', 'tournament'])
        ]);
    }
}
