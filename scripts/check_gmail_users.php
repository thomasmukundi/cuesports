<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Hash;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Setup database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_DATABASE'] ?? 'cuesports',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    echo "=== CueSports Kenya - Gmail Users Report ===\n";
    echo "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Query users with @gmail.com emails
    $gmailUsers = Capsule::table('users')
        ->where('email', 'like', '%@gmail.com')
        ->select('id', 'name', 'first_name', 'last_name', 'email', 'username', 'password', 'is_admin', 'created_at')
        ->orderBy('created_at', 'desc')
        ->get();
    
    if ($gmailUsers->isEmpty()) {
        echo "No users found with @gmail.com email addresses.\n";
        exit(0);
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
        $commonPasswords = ['password', 'password123', '123456', 'admin'];
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
    
    // Security recommendations
    echo "\n=== SECURITY RECOMMENDATIONS ===\n";
    echo "1. Ensure all users have strong passwords\n";
    echo "2. Consider implementing 2FA for admin accounts\n";
    echo "3. Regularly audit user accounts\n";
    echo "4. Monitor for suspicious login activities\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure you're running this from the Laravel project root.\n";
    exit(1);
}
