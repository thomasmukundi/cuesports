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
        Log::info('Complete registration request received', [
            'email' => $request->email,
            'has_fcm_token' => !empty($request->fcm_token),
            'fcm_token_length' => $request->fcm_token ? strlen($request->fcm_token) : 0,
            'fcm_token_in_request' => $request->has('fcm_token'),
            'request_data' => $request->except(['fcm_token']) // Log everything except FCM token for privacy
        ]);

        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'community_id' => 'required|exists:communities,id',
                'county_id' => 'required|exists:counties,id',
                'region_id' => 'required|exists:regions,id',
                'fcm_token' => 'nullable|string|max:255', // Optional FCM token for push notifications
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed in complete registration', [
                'email' => $request->email,
                'errors' => $e->errors(),
                'request_data' => $request->except(['fcm_token'])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Handle FCM token - set to null if not provided or empty
        if (!isset($validated['fcm_token']) || trim($validated['fcm_token']) === '') {
            $validated['fcm_token'] = null;
        }

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
            Log::info('Creating user account', [
                'email' => $validated['email'],
                'community_id' => $validated['community_id'],
                'county_id' => $validated['county_id'],
                'region_id' => $validated['region_id'],
                'has_fcm_token' => !empty($validated['fcm_token']),
                'fcm_token_value' => $validated['fcm_token'] ?? 'null'
            ]);

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
                'fcm_token' => $validated['fcm_token'], // Store FCM token if provided
                'fcm_token_updated_at' => $validated['fcm_token'] ? now() : null,
            ]);

            Log::info('User account created successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'fcm_token_set' => !empty($user->fcm_token)
            ]);

            // Generate JWT token for the authenticated user
            $token = auth('api')->login($user);
            
            if (!$token) {
                Log::error('Failed to generate JWT token for user', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                throw new \Exception('Failed to generate authentication token');
            }

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

            Log::info('User registration completed successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'community_id' => $validated['community_id'],
                'county_id' => $validated['county_id'],
                'region_id' => $validated['region_id'],
                'fcm_token_registered' => !empty($validated['fcm_token']),
                'token_generated' => !empty($token)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration completed successfully',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'community_id' => $user->community_id,
                    'county_id' => $user->county_id,
                    'region_id' => $user->region_id,
                    'total_points' => $user->total_points,
                    'email_verified_at' => $user->email_verified_at,
                    'fcm_token' => $user->fcm_token,
                    'fcm_token_updated_at' => $user->fcm_token_updated_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'token' => $token,
                'fcm_registered' => !empty($validated['fcm_token']),
                'registration_complete' => true,
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

    /**
     * Update FCM token for a user (can be called after registration)
     */
    public function updateFcmToken(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string|max:255'
        ]);

        $user = auth('api')->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        try {
            $user->update([
                'fcm_token' => $validated['fcm_token'],
                'fcm_token_updated_at' => now()
            ]);

            Log::info('FCM token updated successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully',
                'fcm_registered' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update FCM token', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update FCM token'
            ], 500);
        }
    }
}
