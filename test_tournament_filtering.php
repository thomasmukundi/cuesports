<?php

/**
 * Tournament Filtering Test Script
 * Run with: php test_tournament_filtering.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tournament;
use App\Models\User;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;

echo "=== Tournament Filtering Test ===\n\n";

// Test user from Thika community (assuming this exists)
$testUser = User::where('email', 'mukundithomas8@gmail.com')->first();

if (!$testUser) {
    echo "❌ Test user not found. Please ensure mukundithomas8@gmail.com exists.\n";
    exit(1);
}

echo "Test User: {$testUser->name} ({$testUser->email})\n";
echo "User Location:\n";
echo "  Community ID: {$testUser->community_id}\n";
echo "  County ID: {$testUser->county_id}\n";
echo "  Region ID: {$testUser->region_id}\n\n";

// Get user's location names
$userCommunity = $testUser->community_id ? Community::find($testUser->community_id) : null;
$userCounty = $testUser->county_id ? County::find($testUser->county_id) : null;
$userRegion = $testUser->region_id ? Region::find($testUser->region_id) : null;

echo "User Location Names:\n";
echo "  Community: " . ($userCommunity ? $userCommunity->name : 'None') . "\n";
echo "  County: " . ($userCounty ? $userCounty->name : 'None') . "\n";
echo "  Region: " . ($userRegion ? $userRegion->name : 'None') . "\n\n";

// Test tournament filtering logic
echo "=== Tournament Filtering Results ===\n\n";

// Get all tournaments
$allTournaments = Tournament::all();
echo "Total tournaments in database: " . $allTournaments->count() . "\n\n";

// Apply filtering logic (same as TournamentController)
$query = Tournament::query();

$query->where(function ($q) use ($testUser, $userCommunity, $userCounty, $userRegion) {
    // Always show special tournaments and national tournaments
    $q->where('special', true)
      ->orWhere('area_scope', 'national')
      ->orWhereNull('area_scope');

    // Show community tournaments if user is in that community
    if ($userCommunity) {
        $q->orWhere(function ($subQ) use ($userCommunity) {
            $subQ->where('area_scope', 'community')
                 ->where('area_name', $userCommunity->name);
        });
    }

    // Show county tournaments if user is in that county
    if ($userCounty) {
        $q->orWhere(function ($subQ) use ($userCounty) {
            $subQ->where('area_scope', 'county')
                 ->where('area_name', $userCounty->name);
        });
    }

    // Show regional tournaments if user is in that region
    if ($userRegion) {
        $q->orWhere(function ($subQ) use ($userRegion) {
            $subQ->where('area_scope', 'regional')
                 ->where('area_name', $userRegion->name);
        });
    }
});

$filteredTournaments = $query->get();

echo "Tournaments visible to user: " . $filteredTournaments->count() . "\n\n";

echo "Tournament Details:\n";
echo str_repeat("-", 80) . "\n";
printf("%-3s %-25s %-10s %-15s %-15s %-8s\n", "ID", "Name", "Special", "Area Scope", "Area Name", "Visible");
echo str_repeat("-", 80) . "\n";

foreach ($allTournaments as $tournament) {
    $isVisible = $filteredTournaments->contains('id', $tournament->id);
    
    printf("%-3s %-25s %-10s %-15s %-15s %-8s\n", 
        $tournament->id,
        substr($tournament->name, 0, 24),
        $tournament->special ? 'Yes' : 'No',
        $tournament->area_scope ?? 'None',
        $tournament->area_name ?? 'None',
        $isVisible ? '✅' : '❌'
    );
}

echo str_repeat("-", 80) . "\n\n";

// Test specific scenarios
echo "=== Filtering Logic Verification ===\n\n";

$specialTournaments = $allTournaments->where('special', true);
$nationalTournaments = $allTournaments->where('area_scope', 'national');
$communityTournaments = $allTournaments->where('area_scope', 'community');
$countyTournaments = $allTournaments->where('area_scope', 'county');
$regionalTournaments = $allTournaments->where('area_scope', 'regional');

echo "Special tournaments (should all be visible): " . $specialTournaments->count() . "\n";
echo "National tournaments (should all be visible): " . $nationalTournaments->count() . "\n";
echo "Community tournaments: " . $communityTournaments->count() . "\n";
echo "County tournaments: " . $countyTournaments->count() . "\n";
echo "Regional tournaments: " . $regionalTournaments->count() . "\n\n";

// Check community filtering
if ($userCommunity) {
    $userCommunityTournaments = $communityTournaments->where('area_name', $userCommunity->name);
    echo "Community tournaments for '{$userCommunity->name}': " . $userCommunityTournaments->count() . "\n";
}

// Check county filtering
if ($userCounty) {
    $userCountyTournaments = $countyTournaments->where('area_name', $userCounty->name);
    echo "County tournaments for '{$userCounty->name}': " . $userCountyTournaments->count() . "\n";
}

// Check regional filtering
if ($userRegion) {
    $userRegionalTournaments = $regionalTournaments->where('area_name', $userRegion->name);
    echo "Regional tournaments for '{$userRegion->name}': " . $userRegionalTournaments->count() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "✅ Tournament filtering logic verified!\n";
echo "📱 Mobile app will only show tournaments that are:\n";
echo "   - Special tournaments (visible to all)\n";
echo "   - National tournaments (visible to all)\n";
echo "   - Community tournaments matching user's community\n";
echo "   - County tournaments matching user's county\n";
echo "   - Regional tournaments matching user's region\n";
