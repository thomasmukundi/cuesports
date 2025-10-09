<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Register a new user (two-stage registration)
     */
    public function register(Request $request)
    {
        // Check for existing email first
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A user with this email already exists'
            ], 422);
        }

        // Check for existing username first
        if (User::where('username', $request->username)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A user with this username already exists'
            ], 422);
        }

        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
                // Location fields are now optional for initial registration
                'community_id' => 'nullable|exists:communities,id',
                'county_id' => 'nullable|exists:counties,id',
                'region_id' => 'nullable|exists:regions,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Check for specific validation errors and provide better messages
            $errors = $e->errors();
            $message = 'Registration failed, please check the information provided';
            
            if (isset($errors['email']) && in_array('The email has already been taken.', $errors['email'])) {
                $message = 'A user with this email address already exists';
            } elseif (isset($errors['username']) && in_array('The username has already been taken.', $errors['username'])) {
                $message = 'A user with this username already exists';
            } elseif (isset($errors['password'])) {
                $message = 'Password validation failed. Please ensure your password meets the requirements';
            } elseif (isset($errors['email']) && in_array('The email field must be a valid email address.', $errors['email'])) {
                $message = 'Please provide a valid email address';
            }
            
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration failed with exception', [
                'email' => $request->email,
                'username' => $request->username,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed due to a server error. Please try again later.'
            ], 500);
        }

        // Check if this is a complete registration (with location) or initial registration
        $hasLocationData = $request->filled(['community_id', 'county_id', 'region_id']);

        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'community_id' => $request->community_id,
            'county_id' => $request->county_id,
            'region_id' => $request->region_id,
            'total_points' => 0,
            // Mark email as unverified for mobile app flow
            'email_verified_at' => $hasLocationData ? now() : null,
        ]);

        $token = auth()->login($user);

        // If no location data provided, this is mobile app initial registration
        if (!$hasLocationData) {
            // Don't authenticate user yet - they need to complete verification and location selection first
            // Delete the user we just created and store data temporarily
            $user->delete();
            
            // Send verification code for the email
            try {
                $verification = \App\Models\Verification::createOrUpdate(
                    $request->email,
                    \App\Models\Verification::TYPE_SIGN_UP,
                    null, // No user_id yet
                    [
                        'registration_data' => [
                            'first_name' => $request->first_name,
                            'last_name' => $request->last_name,
                            'username' => $request->username,
                            'email' => $request->email,
                            'password' => Hash::make($request->password),
                            'phone' => $request->phone,
                        ]
                    ]
                );
                
                \Illuminate\Support\Facades\Log::info("Verification code for {$request->email}: {$verification->code}");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create verification code', [
                    'email' => $request->email,
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Registration failed. Please try again.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration data saved. Please verify your email.',
                'requires_verification' => true,
                'requires_location' => true,
                'email' => $request->email,
            ], 201);
        }

        // Complete registration with location data (legacy flow)
        // Send welcome email
        try {
            $emailService = new EmailService();
            $emailService->sendWelcomeEmail($user->email, $user->name);
        } catch (\Exception $e) {
            \Log::warning('Failed to send welcome email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
            'requires_verification' => false,
            'requires_location' => false,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'fcm_token' => 'nullable|string|max:255', // Optional FCM token for push notifications
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $message = 'Please check your login information';
            
            if (isset($errors['email'])) {
                $message = 'Please provide a valid email address';
            } elseif (isset($errors['password'])) {
                $message = 'Password must be at least 8 characters long';
            }
            
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        // Check if user exists
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            Log::warning('Login attempt with non-existent email', [
                'email' => $credentials['email']
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address. Please check your email or register for a new account.'
            ], 401);
        }

        // Check if password is correct
        if (!Hash::check($credentials['password'], $user->password)) {
            Log::warning('Login attempt with incorrect password', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password. Please check your password and try again.'
            ], 401);
        }

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to login, try again later'
                ], 401);
            }
        } catch (JWTException $e) {
            \Log::error('JWT Token Creation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to login, try again later'
            ], 500);
        }

        $user = auth()->user();
        
        // Update last login time
        $updateData = ['last_login' => now()];
        
        // Update FCM token if provided
        $fcmTokenUpdated = false;
        if ($request->filled('fcm_token')) {
            $updateData['fcm_token'] = $request->fcm_token;
            $updateData['fcm_token_updated_at'] = now();
            $fcmTokenUpdated = true;
        }
        
        $user->update($updateData);
        
        Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'fcm_token_provided' => $request->filled('fcm_token'),
            'fcm_token_updated' => $fcmTokenUpdated
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image_url,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at,
                'fcm_token' => $user->fcm_token,
                'fcm_token_updated_at' => $user->fcm_token_updated_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'community_id' => $user->community_id,
                'county_id' => $user->county_id,
                'region_id' => $user->region_id,
                'total_points' => $user->total_points,
                'last_login' => $user->last_login,
                'is_admin' => $user->is_admin,
            ],
            'token' => $token,
            'fcm_registered' => $fcmTokenUpdated,
            'expires_at' => null
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image_url,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at,
                'fcm_token' => $user->fcm_token,
                'fcm_token_updated_at' => $user->fcm_token_updated_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'community_id' => $user->community_id,
                'county_id' => $user->county_id,
                'region_id' => $user->region_id,
                'total_points' => $user->total_points,
                'last_login' => $user->last_login,
                'is_admin' => $user->is_admin,
            ]
        ]);
    }

    /**
     * Logout user
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout'
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|nullable|string|max:20',
                'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
                'community_id' => 'sometimes|exists:communities,id',
                'county_id' => 'sometimes|exists:counties,id',
                'region_id' => 'sometimes|exists:regions,id',
            ]);
            
            if (isset($validated['first_name']) || isset($validated['last_name'])) {
                $validated['name'] = ($validated['first_name'] ?? $user->first_name) . ' ' . 
                                    ($validated['last_name'] ?? $user->last_name);
            }
            
            $user->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user->fresh()
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }
    }

    /**
     * Refresh token
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'token' => $token
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token cannot be refreshed'
            ], 401);
        }
    }

    /**
     * Update FCM token for push notifications
     */
    public function updateFcmToken(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $request->validate([
                'fcm_token' => 'required|string|max:255'
            ]);
            
            $user->update([
                'fcm_token' => $request->fcm_token,
                'fcm_token_updated_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update FCM token'
            ], 500);
        }
    }

    /**
     * Update FCM token after registration/login (for delayed permission grants)
     */
    public function updateFcmTokenDelayed(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $validated = $request->validate([
                'fcm_token' => 'required|string|max:255'
            ]);
            
            $user->update([
                'fcm_token' => $validated['fcm_token'],
                'fcm_token_updated_at' => now()
            ]);
            
            Log::info('FCM token updated after delayed permission grant', [
                'user_id' => $user->id,
                'email' => $user->email,
                'updated_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully',
                'fcm_registered' => true
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        } catch (\Exception $e) {
            Log::error('Failed to update FCM token after delayed permission', [
                'error' => $e->getMessage(),
                'user_id' => auth('api')->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update FCM token'
            ], 500);
        }
    }

    /**
     * Remove FCM token (for logout or token invalidation)
     */
    public function removeFcmToken(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $user->update([
                'fcm_token' => null,
                'fcm_token_updated_at' => now()
            ]);
            
            Log::info('FCM token removed', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'FCM token removed successfully'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove FCM token'
            ], 500);
        }
    }
}
