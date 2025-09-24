<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Verification;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CompleteRegistrationController extends Controller
{
    /**
     * Complete user registration with location data after email verification
     */
    public function completeRegistration(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'community_id' => 'required|exists:communities,id',
            'county_id' => 'required|exists:counties,id',
            'region_id' => 'required|exists:regions,id',
            'fcm_token' => 'nullable|string|max:255', // Optional FCM token for push notifications
        ]);

        // Find the verified sign-up verification for this email
        $verification = Verification::where('email', $validated['email'])
            ->where('verification_type', Verification::TYPE_SIGN_UP)
            ->where('is_used', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification not found or expired. Please register again.'
            ], 400);
        }

        $registrationData = $verification->metadata['registration_data'] ?? null;
        
        if (!$registrationData) {
            return response()->json([
                'success' => false,
                'message' => 'Registration data not found. Please register again.'
            ], 400);
        }

        // Check if user already exists with this email
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'User already exists with this email.'
            ], 400);
        }

        try {
            // Create the complete user account
            $user = User::create([
                'name' => $registrationData['first_name'] . ' ' . $registrationData['last_name'],
                'first_name' => $registrationData['first_name'],
                'last_name' => $registrationData['last_name'],
                'username' => $registrationData['username'],
                'email' => $registrationData['email'],
                'password' => $registrationData['password'], // Already hashed
                'phone' => $registrationData['phone'],
                'community_id' => $validated['community_id'],
                'county_id' => $validated['county_id'],
                'region_id' => $validated['region_id'],
                'total_points' => 0,
                'email_verified_at' => now(),
                'fcm_token' => $validated['fcm_token'] ?? null, // Store FCM token if provided
                'fcm_token_updated_at' => $validated['fcm_token'] ? now() : null,
            ]);

            // Generate JWT token for the authenticated user
            $token = auth('api')->login($user);

            // Send welcome email
            try {
                $emailService = new EmailService();
                $emailService->sendWelcomeEmail($user->email, $user->name);
            } catch (\Exception $e) {
                Log::warning('Failed to send welcome email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
            }

            // Clean up the verification record
            $verification->delete();

            Log::info('User registration completed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'community_id' => $validated['community_id'],
                'fcm_token_registered' => !empty($validated['fcm_token'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration completed successfully',
                'user' => $user,
                'token' => $token,
                'fcm_registered' => !empty($validated['fcm_token']),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to complete registration', [
                'email' => $validated['email'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ], 500);
        }
    }
}
