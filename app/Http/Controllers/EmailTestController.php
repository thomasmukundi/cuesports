<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailTestController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Display email test page
     */
    public function index()
    {
        $config = [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'environment' => config('app.env'),
        ];

        return view('email-test', compact('config'));
    }

    /**
     * Send test email via AJAX
     */
    public function sendTest(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'type' => 'required|in:test,verification,password-reset,welcome'
        ]);

        $email = $request->email;
        $name = $request->name;
        $type = $request->type;

        try {
            $result = false;
            $code = null;

            switch ($type) {
                case 'test':
                    $result = $this->emailService->sendTestEmail($email, $name);
                    break;
                
                case 'verification':
                    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                    $result = $this->emailService->sendVerificationCode($email, $code, $name);
                    break;
                
                case 'password-reset':
                    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                    $result = $this->emailService->sendPasswordResetCode($email, $code, $name);
                    break;
                
                case 'welcome':
                    $result = $this->emailService->sendWelcomeEmail($email, $name);
                    break;
            }

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => ucfirst($type) . ' email sent successfully!',
                    'code' => $code,
                    'email' => $email,
                    'type' => $type
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send ' . $type . ' email'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test all email types at once
     */
    public function testAll(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string|max:255'
        ]);

        $email = $request->email;
        $name = $request->name;
        $results = [];

        // Test 1: Test email
        try {
            $result = $this->emailService->sendTestEmail($email, $name);
            $results['test'] = [
                'success' => $result,
                'message' => $result ? 'Test email sent' : 'Test email failed'
            ];
        } catch (\Exception $e) {
            $results['test'] = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }

        // Test 2: Verification email
        try {
            $verificationCode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $result = $this->emailService->sendVerificationCode($email, $verificationCode, $name);
            $results['verification'] = [
                'success' => $result,
                'message' => $result ? 'Verification email sent' : 'Verification email failed',
                'code' => $verificationCode
            ];
        } catch (\Exception $e) {
            $results['verification'] = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }

        // Test 3: Password reset email
        try {
            $resetCode = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $result = $this->emailService->sendPasswordResetCode($email, $resetCode, $name);
            $results['password-reset'] = [
                'success' => $result,
                'message' => $result ? 'Password reset email sent' : 'Password reset email failed',
                'code' => $resetCode
            ];
        } catch (\Exception $e) {
            $results['password-reset'] = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }

        // Test 4: Welcome email
        try {
            $result = $this->emailService->sendWelcomeEmail($email, $name);
            $results['welcome'] = [
                'success' => $result,
                'message' => $result ? 'Welcome email sent' : 'Welcome email failed'
            ];
        } catch (\Exception $e) {
            $results['welcome'] = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $totalCount = count($results);

        return response()->json([
            'success' => $successCount > 0,
            'message' => "$successCount out of $totalCount emails sent successfully",
            'results' => $results,
            'summary' => [
                'total' => $totalCount,
                'successful' => $successCount,
                'failed' => $totalCount - $successCount
            ]
        ]);
    }
}
