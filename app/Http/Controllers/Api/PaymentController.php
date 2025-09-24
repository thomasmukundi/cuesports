<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Process tournament payment
     * Currently returns success for all requests - will be implemented later
     */
    public function processTournamentPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required|integer',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:mpesa,card,bank_transfer',
            'phone_number' => 'required_if:payment_method,mpesa|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Implement actual payment processing logic
        // For now, always return success
        
        $paymentId = 'PAY_' . time() . '_' . rand(1000, 9999);
        $user = auth()->user();
        
        // Use cache to prevent duplicate notifications for the same payment
        $cacheKey = "payment_notification_{$user->id}_{$request->tournament_id}_{$request->amount}";
        
        // Check if we've already sent a notification for this payment
        if (!Cache::has($cacheKey)) {
            // Mark this payment as processed to prevent duplicates
            Cache::put($cacheKey, true, now()->addMinutes(10));
            
            // Send single notification for payment success
            try {
                Notification::create([
                    'player_id' => $user->id,
                    'type' => 'payment_success',
                    'message' => "Payment of KES " . number_format($request->amount, 2) . " processed successfully for tournament registration",
                    'data' => [
                        'payment_id' => $paymentId,
                        'tournament_id' => $request->tournament_id,
                        'amount' => $request->amount,
                        'payment_method' => $request->payment_method,
                        'transaction_reference' => 'TXN_' . time(),
                    ]
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create payment notification', [
                    'user_id' => $user->id,
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => [
                'payment_id' => $paymentId,
                'tournament_id' => $request->tournament_id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'status' => 'completed',
                'transaction_reference' => 'TXN_' . time(),
                'processed_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request, $paymentId): JsonResponse
    {
        // TODO: Implement actual payment status checking
        // For now, always return completed status
        
        $user = auth()->user();
        
        // Check if this is a status check after payment completion
        // Use a different cache key for status checks to avoid duplicate notifications
        $statusCacheKey = "payment_status_notified_{$paymentId}";
        
        $paymentData = [
            'payment_id' => $paymentId,
            'status' => 'completed',
            'amount' => 1000.00, // Mock amount
            'payment_method' => 'mpesa',
            'processed_at' => now()->toISOString(),
        ];
        
        // Only send notification once when status changes to completed
        if ($paymentData['status'] === 'completed' && !Cache::has($statusCacheKey)) {
            // Mark this payment status as notified to prevent duplicates
            Cache::put($statusCacheKey, true, now()->addHours(1));
            
            try {
                // Check if we haven't already sent a notification for this payment
                $existingNotification = Notification::where('player_id', $user->id)
                    ->where('type', 'payment_success')
                    ->where('data->payment_id', $paymentId)
                    ->first();
                
                if (!$existingNotification) {
                    Notification::create([
                        'player_id' => $user->id,
                        'type' => 'payment_success',
                        'message' => "Payment of KES " . number_format($paymentData['amount'], 2) . " confirmed successfully",
                        'data' => $paymentData
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to create payment status notification', [
                    'user_id' => $user->id,
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $paymentData
        ]);
    }

    /**
     * Get user's payment history
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // TODO: Implement actual payment history retrieval
        // For now, return mock data
        
        $mockPayments = [
            [
                'payment_id' => 'PAY_' . (time() - 86400) . '_1234',
                'tournament_name' => 'Kenya Open Championship',
                'amount' => 1500.00,
                'payment_method' => 'mpesa',
                'status' => 'completed',
                'processed_at' => now()->subDay()->toISOString(),
            ],
            [
                'payment_id' => 'PAY_' . (time() - 172800) . '_5678',
                'tournament_name' => 'Nairobi Pool Masters',
                'amount' => 1000.00,
                'payment_method' => 'card',
                'status' => 'completed',
                'processed_at' => now()->subDays(2)->toISOString(),
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $mockPayments
        ]);
    }
}
