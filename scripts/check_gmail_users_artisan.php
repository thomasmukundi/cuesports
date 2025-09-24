<?php

/**
 * Laravel Artisan Command to Check Gmail Users
 * Run with: php artisan tinker --execute="require 'scripts/check_gmail_users_artisan.php';"
 */

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== CueSports Kenya - Gmail Users Report ===\n";
echo "Generated on: " . now()->format('Y-m-d H:i:s') . "\n\n";

try {
    // Query users with @gmail.com emails
    $gmailUsers = User::where('email', 'like', '%@gmail.com')
        ->orderBy('created_at', 'desc')
        ->get();
    
    if ($gmailUsers->isEmpty()) {
        echo "No users found with @gmail.com email addresses.\n";
        return;
    }
    
    echo "Found " . $gmailUsers->count() . " users with @gmail.com email addresses:\n";
    echo str_repeat("=", 80) . "\n";
    
    foreach ($gmailUsers as $user) {
        echo "ID: {$user->id}\n";
        echo "Name: {$user->name}\n";
        echo "Username: {$user->username}\n";
        echo "Email: {$user->email}\n";
        echo "Admin: " . ($user->is_admin ? 'Yes' : 'No') . "\n";
        echo "Created: {$user->created_at}\n";
        
        // Note: Passwords are hashed, so we can't show the actual password
        echo "Password Hash: " . substr($user->password, 0, 20) . "...\n";
        
        // For development/testing purposes, check if it's a common test password
        $commonPasswords = ['password', 'password123', '123456', 'admin', 'test', 'user'];
        $passwordMatched = false;
        
        foreach ($commonPasswords as $testPassword) {
            if (Hash::check($testPassword, $user->password)) {
                echo "⚠️  WEAK PASSWORD DETECTED: '{$testPassword}'\n";
                $passwordMatched = true;
                break;
            }
        }
        
        if (!$passwordMatched) {
            echo "Password: [Secure - not a common weak password]\n";
        }
        
        echo str_repeat("-", 40) . "\n";
    }
    
    // Summary statistics
    echo "\n=== SUMMARY ===\n";
    echo "Total Gmail users: " . $gmailUsers->count() . "\n";
    echo "Admin users: " . $gmailUsers->where('is_admin', true)->count() . "\n";
    echo "Regular users: " . $gmailUsers->where('is_admin', false)->count() . "\n";
    
    // Show test credentials for development
    echo "\n=== TEST CREDENTIALS (Development Only) ===\n";
    $testUser = $gmailUsers->where('email', 'mukundithomas8@gmail.com')->first();
    if ($testUser) {
        echo "Main Test User: mukundithomas8@gmail.com\n";
        echo "- Likely password: password123 (if weak password detected above)\n";
    }
    
    $adminUser = $gmailUsers->where('email', 'admin@cuesports.com')->first();
    if ($adminUser) {
        echo "Admin User: admin@cuesports.com\n";
        echo "- Likely password: password (if weak password detected above)\n";
    }
    
    // Security recommendations
    echo "\n=== SECURITY RECOMMENDATIONS ===\n";
    echo "1. Ensure all users have strong passwords\n";
    echo "2. Consider implementing 2FA for admin accounts\n";
    echo "3. Regularly audit user accounts\n";
    echo "4. Monitor for suspicious login activities\n";
    echo "5. In production, disable weak password checking\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
