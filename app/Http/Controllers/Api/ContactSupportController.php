<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ContactSupport;

class ContactSupportController extends Controller
{
    /**
     * Submit a contact support request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            
            $contactSupport = ContactSupport::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your support request has been submitted successfully. We will get back to you soon.',
                'data' => [
                    'id' => $contactSupport->id,
                    'subject' => $contactSupport->subject,
                    'submitted_at' => $contactSupport->created_at->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit support request. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's support requests
     */
    public function index()
    {
        try {
            $user = auth()->user();
            
            $supportRequests = ContactSupport::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->select('id', 'subject', 'message', 'created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $supportRequests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch support requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
