<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TinyPesaService
{
    private const INITIALIZE_URL = 'https://seroxideentertainment.co.ke/tickets/spotbot/tinypesa.php';
    private const CHECK_STATUS_URL = 'https://seroxideentertainment.co.ke/tickets/spotbot/check_status.php';

    /**
     * Initialize TinyPesa transaction
     */
    public function initializeTransaction(int $userId, int $tournamentId, float $amount, string $phoneNumber): array
    {
        try {
            Log::info('TinyPesa initialization request', [
                'user_id' => $userId,
                'tournament_id' => $tournamentId,
                'amount' => $amount,
                'phone_number' => $phoneNumber,
                'url' => self::INITIALIZE_URL
            ]);

            $response = Http::timeout(30)
                ->withOptions(['verify' => false]) // Disable SSL verification for development
                ->post(self::INITIALIZE_URL, [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'phone_number' => $phoneNumber,
                    'service_id' => $tournamentId,
                ]);

            Log::info('TinyPesa HTTP response', [
                'status_code' => $response->status(),
                'body' => $response->body(),
                'successful' => $response->successful()
            ]);

            if (!$response->successful()) {
                Log::error('TinyPesa API HTTP error', [
                    'status_code' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Payment service temporarily unavailable',
                ];
            }

            $data = $response->json();

            Log::info('TinyPesa initialization response', [
                'user_id' => $userId,
                'tournament_id' => $tournamentId,
                'response' => $data
            ]);

            if (isset($data['status']) && $data['status'] === 'success') {
                // Create local transaction record
                $transaction = Transaction::create([
                    'user_id' => $userId,
                    'service_id' => $tournamentId,
                    'amount' => $amount,
                    'phone_number' => $phoneNumber,
                    'request_id' => $data['request_id'] ?? uniqid('txn_'),
                    'status' => 'pending',
                ]);

                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'Payment initialized successfully',
                    'request_id' => $data['request_id'] ?? $transaction->id,
                    'transaction_id' => $transaction->id,
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Transaction initialization failed',
            ];

        } catch (\Exception $e) {
            Log::error('TinyPesa initialization error', [
                'user_id' => $userId,
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Payment service unavailable. Please try again later.',
            ];
        }
    }

    /**
     * Check transaction status
     */
    public function checkTransactionStatus(int $userId, int $tournamentId): array
    {
        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => false]) // Disable SSL verification for development
                ->post(self::CHECK_STATUS_URL, [
                'user_id' => $userId,
                'service_id' => $tournamentId,
            ]);

            $data = $response->json();

            Log::info('TinyPesa status check response', [
                'user_id' => $userId,
                'tournament_id' => $tournamentId,
                'response' => $data
            ]);

            if ($data['status'] === 'success' && $data['transaction_exists']) {
                // Update local transaction record
                $this->updateLocalTransaction($data['transaction']);

                return [
                    'success' => true,
                    'is_complete' => $data['is_complete'],
                    'is_successful' => $data['is_successful'],
                    'transaction' => $data['transaction'],
                    'message' => $data['message'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Transaction not found',
            ];

        } catch (\Exception $e) {
            Log::error('TinyPesa status check error', [
                'user_id' => $userId,
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Unable to check payment status. Please try again.',
            ];
        }
    }

    /**
     * Update local transaction with TinyPesa data
     */
    private function updateLocalTransaction(array $transactionData): void
    {
        try {
            Log::info('Updating local transaction', [
                'request_id' => $transactionData['request_id'] ?? 'N/A',
                'status' => $transactionData['status'] ?? 'N/A',
                'tiny_pesa_id' => $transactionData['tiny_pesa_id'] ?? 'N/A'
            ]);

            $transaction = Transaction::where('request_id', $transactionData['request_id'])->first();

            if ($transaction) {
                $updateData = [
                    'status' => $transactionData['status'] ?? $transaction->status,
                    'merchant_request_id' => $transactionData['merchant_request_id'] ?? $transaction->merchant_request_id,
                    'checkout_request_id' => $transactionData['checkout_request_id'] ?? $transaction->checkout_request_id,
                    'mpesa_receipt_number' => $transactionData['mpesa_receipt_number'] ?? $transaction->mpesa_receipt_number,
                    'transaction_date' => $transactionData['transaction_date'] ?? $transaction->transaction_date,
                    'tiny_pesa_id' => $transactionData['tiny_pesa_id'] ?? $transaction->tiny_pesa_id,
                    'account_no' => $transactionData['account_no'] ?? $transaction->account_no,
                ];

                $updated = $transaction->update($updateData);
                
                if ($updated) {
                    Log::info('Transaction updated successfully', [
                        'transaction_id' => $transaction->id,
                        'request_id' => $transactionData['request_id'],
                        'new_status' => $transactionData['status']
                    ]);
                } else {
                    Log::error('Failed to update transaction', [
                        'transaction_id' => $transaction->id,
                        'request_id' => $transactionData['request_id']
                    ]);
                }
            } else {
                Log::warning('Transaction not found for update', [
                    'request_id' => $transactionData['request_id']
                ]);
                
                // Try to create a new transaction if it doesn't exist
                $this->createMissingTransaction($transactionData);
            }
        } catch (\Exception $e) {
            Log::error('Error updating local transaction', [
                'request_id' => $transactionData['request_id'] ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create missing transaction record
     */
    private function createMissingTransaction(array $transactionData): void
    {
        try {
            $transaction = Transaction::create([
                'user_id' => $transactionData['user_id'] ?? null,
                'service_id' => $transactionData['service_id'] ?? null,
                'amount' => $transactionData['amount'] ?? 0,
                'phone_number' => $transactionData['phone_number'] ?? null,
                'request_id' => $transactionData['request_id'],
                'status' => $transactionData['status'],
                'merchant_request_id' => $transactionData['merchant_request_id'] ?? null,
                'checkout_request_id' => $transactionData['checkout_request_id'] ?? null,
                'mpesa_receipt_number' => $transactionData['mpesa_receipt_number'] ?? null,
                'transaction_date' => $transactionData['transaction_date'] ?? null,
                'tiny_pesa_id' => $transactionData['tiny_pesa_id'] ?? null,
                'account_no' => $transactionData['account_no'] ?? null,
            ]);

            Log::info('Missing transaction created', [
                'transaction_id' => $transaction->id,
                'request_id' => $transactionData['request_id']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create missing transaction', [
                'request_id' => $transactionData['request_id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get transaction by request ID
     */
    public function getTransactionByRequestId(string $requestId): ?Transaction
    {
        return Transaction::where('request_id', $requestId)->first();
    }
}
