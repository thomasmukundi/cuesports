<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\EmailService;

class AdminPasswordController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Show change password form
     */
    public function showChangePassword()
    {
        return view('admin.change-password');
    }

    /**
     * Send verification code to admin email
     */
    public function sendVerificationCode(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
        ]);

        $admin = Auth::user();

        // Verify current password
        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ], 400);
        }

        try {
            // Generate 6-digit verification code
            $verificationCode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store code in cache for 15 minutes
            $cacheKey = 'admin_password_change_' . $admin->id;
            Cache::put($cacheKey, $verificationCode, 900); // 15 minutes

            // Send verification code to hardcoded admin email
            $adminEmail = 'mukundithomas8@gmail.com';
            $adminName = 'Admin';

            $data = [
                'name' => $adminName,
                'app_name' => config('app.name', 'CueSports Kenya'),
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'environment' => config('app.env'),
                'verification_code' => $verificationCode,
                'app_url' => config('app.url'),
            ];

            Mail::send('emails.admin-password-verification', $data, function ($message) use ($adminEmail, $adminName) {
                $message->to($adminEmail, $adminName)
                        ->subject('Admin Password Change Verification - ' . config('app.name'));
            });

            Log::info('Admin password change verification code sent', [
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'verification_email' => $adminEmail
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent to admin email: ' . $adminEmail
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send admin password verification code', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.'
            ], 500);
        }
    }

    /**
     * Verify code and change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|string|size:6',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $admin = Auth::user();
        $cacheKey = 'admin_password_change_' . $admin->id;
        
        // Get stored verification code
        $storedCode = Cache::get($cacheKey);
        
        if (!$storedCode) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired. Please request a new one.'
            ], 400);
        }

        if ($storedCode !== $request->verification_code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.'
            ], 400);
        }

        try {
            // Update admin password
            $admin->password = Hash::make($request->new_password);
            $admin->save();

            // Clear the verification code
            Cache::forget($cacheKey);

            Log::info('Admin password changed successfully', [
                'admin_id' => $admin->id,
                'admin_email' => $admin->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to change admin password', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change password. Please try again.'
            ], 500);
        }
    }
}
