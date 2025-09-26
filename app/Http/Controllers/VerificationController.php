<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class VerificationController extends Controller
{
    /**
     * Send verification code to email
     */
    public function sendCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:sign_up,reset_password,change_email',
            'user_id' => 'nullable|exists:users,id',
        ]);

        try {
            // For password reset, find user by email to get user_id
            $userId = $validated['user_id'] ?? null;
            if ($validated['type'] === 'reset_password' && !$userId) {
                $user = User::where('email', $validated['email'])->first();
                $userId = $user ? $user->id : null;
            }

            // Create or update verification code
            $verification = Verification::createOrUpdate(
                $validated['email'],
                $validated['type'],
                $userId
            );

            // Email is automatically sent by the Verification model
            // Also log the code for development/testing
            Log::info("Verification code for {$validated['email']}: {$verification->code}");

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent successfully',
                'expires_at' => $verification->expires_at->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send verification code', [
                'email' => $validated['email'],
                'type' => $validated['type'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code'
            ], 500);
        }
    }

    /**
     * Verify code
     */
    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'type' => 'required|in:sign_up,reset_password,change_email',
        ]);

        $verification = Verification::verify(
            $validated['email'],
            $validated['code'],
            $validated['type']
        );

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code'
            ], 400);
        }

        // Handle different verification types first, then mark as used
        switch ($validated['type']) {
            case Verification::TYPE_SIGN_UP:
                $verification->markAsUsed();
                return $this->handleSignUpVerification($verification);
            
            case Verification::TYPE_RESET_PASSWORD:
                // Don't mark as used yet - will be marked in resetPassword method
                return $this->handlePasswordResetVerification($verification);
            
            case Verification::TYPE_CHANGE_EMAIL:
                $verification->markAsUsed();
                return $this->handleEmailChangeVerification($verification);
            
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification type'
                ], 400);
        }
    }

    /**
     * Handle sign-up verification
     */
    private function handleSignUpVerification(Verification $verification)
    {
        // For sign-up verification, return the stored registration data
        // User still needs to complete location selection before getting authenticated
        $registrationData = $verification->metadata['registration_data'] ?? null;
        
        if (!$registrationData) {
            return response()->json([
                'success' => false,
                'message' => 'Registration data not found. Please register again.'
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully. Please select your location.',
            'verification_type' => 'sign_up',
            'email' => $verification->email,
            'registration_data' => [
                'first_name' => $registrationData['first_name'],
                'last_name' => $registrationData['last_name'],
                'username' => $registrationData['username'],
                'email' => $registrationData['email'],
                'phone' => $registrationData['phone'],
            ],
        ]);
    }

    /**
     * Handle password reset verification
     */
    private function handlePasswordResetVerification(Verification $verification)
    {
        // For password reset, we don't need a separate token
        // The verification is already validated, just return success
        return response()->json([
            'success' => true,
            'message' => 'Verification successful. You can now reset your password.',
            'verification_type' => 'reset_password',
        ]);
    }

    /**
     * Handle email change verification
     */
    private function handleEmailChangeVerification(Verification $verification)
    {
        if (!$verification->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification for email change'
            ], 400);
        }

        // Update user's email
        $user = User::find($verification->user_id);
        if ($user) {
            $user->update(['email' => $verification->email]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email changed successfully',
            'verification_type' => 'change_email',
        ]);
    }

    /**
     * Resend verification code
     */
    public function resendCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:sign_up,reset_password,change_email',
        ]);

        // Check if there's a recent verification request (rate limiting)
        $recentVerification = Verification::where('email', $validated['email'])
            ->where('verification_type', $validated['type'])
            ->where('created_at', '>', now()->subMinutes(1))
            ->first();

        if ($recentVerification) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another code'
            ], 429);
        }

        // Use the same logic as sendCode
        return $this->sendCode($request);
    }

    /**
     * Reset password using verification code
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Normalize email to avoid case/whitespace mismatches
        $email = trim(mb_strtolower($validated['email']));

        // Debug: Log the verification attempt
        Log::info('Password reset verification attempt', [
            'email' => $email,
            'code' => $validated['code'],
            'type' => 'reset_password'
        ]);

        // Check if any verification exists for this email
        $allVerifications = Verification::where('email', $email)
            ->where('verification_type', 'reset_password')
            ->orderBy('created_at', 'desc')
            ->get();
        
        Log::info('All reset_password verifications for email', [
            'email' => $validated['email'],
            'verifications' => $allVerifications->map(function($v) {
                return [
                    'code' => $v->code,
                    'expires_at' => $v->expires_at,
                    'is_used' => $v->is_used,
                    'created_at' => $v->created_at,
                    'expired' => $v->expires_at->isPast()
                ];
            })
        ]);

        // For password reset, we need to check for codes that might have been used in verifyCode
        // but are still valid for password reset (within expiration time)
        $verification = Verification::where('email', $email)
            ->where('code', $validated['code'])
            ->where('verification_type', 'reset_password')
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verification) {
            Log::warning('Verification failed', [
                'email' => $validated['email'],
                'code' => $validated['code'],
                'type' => 'reset_password'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code'
            ], 400);
        }

        Log::info('Verification successful', [
            'email' => $email,
            'verification_id' => $verification->id
        ]);

        // Use transaction for atomicity
        \DB::beginTransaction();
        try {
            // Find user by normalized email
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            if (!$user) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $newHashed = \Illuminate\Support\Facades\Hash::make($validated['new_password']);
            $updated = $user->update(['password' => $newHashed]);

            Log::info('Password update result', [
                'user_id' => $user->id,
                'email' => $user->email,
                'updated' => (bool) $updated
            ]);

            if (!$updated) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update password'
                ], 500);
            }

            // Mark verification as used only after successful password update
            $verification->markAsUsed();

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Password reset failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Check verification status
     */
    public function checkStatus(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:sign_up,reset_password,change_email',
        ]);

        $verification = Verification::where('email', $validated['email'])
            ->where('verification_type', $validated['type'])
            ->where('is_used', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'No active verification found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'expires_at' => $verification->expires_at->toISOString(),
            'is_expired' => $verification->isExpired(),
        ]);
    }
}
