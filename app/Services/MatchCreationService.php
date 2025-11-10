<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\PoolMatch;
use App\Models\User;
use App\Models\Winner;
use App\Events\MatchPairingCreated;
use App\Services\TournamentUtilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MatchCreationService
{
    /**
     * Create matches for a group of players
     */
    public static function createMatchesForGroup(Tournament $tournament, Collection $players, string $level, $groupId): array
    {
        // Extract actual players if we have winner objects with previous group info
        $actualPlayers = $players->map(function ($item) {
            return is_object($item) && isset($item->player) ? $item->player : $item;
        });
        
        $playerCount = $actualPlayers->count();
        $levelName = TournamentUtilityService::getLevelName($level, $groupId) ?? 'Special Tournament';
        
        // Debug: Log the level name being generated
        \Log::info("Creating matches for group {$groupId}: level_name = '{$levelName}'");
        
        if ($playerCount === 1) {
            // Single player automatically wins
            $player = $actualPlayers->first();
            Winner::create([
                'player_id' => $player->id,
                'position' => 1,
                'level' => $level,
                'level_id' => $groupId,
                'tournament_id' => $tournament->id,
            ]);
            
            \Log::info("Single player auto-win created for group {$groupId}");
            return [];
        }
        
        if ($playerCount <= 4) {
            return self::handleSpecialCases($tournament, $actualPlayers, $level, $groupId, $levelName);
        } else {
            return self::createStandardMatches($tournament, $actualPlayers, $level, $groupId, 'round_1', $levelName);
        }
    }

    /**
     * Create smart pairing for county/regional/national levels avoiding same previous group
     */
    public static function createSmartPairingForLevel(Tournament $tournament, Collection $players, string $level, int $groupId): array
    {
        $levelName = TournamentUtilityService::getLevelName($level, $groupId) ?? 'Special Tournament';
        $playerCount = $players->count();
        
        \Log::info("=== SMART PAIRING DECISION POINT ===", [
            'level' => $level,
            'player_count' => $playerCount,
            'group_id' => $groupId,
            'tournament_id' => $tournament->id,
            'will_use_special_cases' => ($playerCount <= 4),
            'will_use_standard_progression' => ($playerCount > 4),
            'players' => $players->map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'previous_group' => TournamentUtilityService::getPreviousGroupIdFromPlayer($p, $level)
                ];
            })->toArray()
        ]);
        
        // For 5+ players (including 6,7,8,9,10+), use standard round-based progression
        // This creates round_1 matches and lets normal progression handle subsequent rounds
        return self::createStandardRound1WithSmartPairing($tournament, $players, $level, $groupId, $levelName);
    }

    /**
     * Handle special cases for 2, 3, and 4 player groups
     */
    public static function handleSpecialCases(Tournament $tournament, Collection $players, string $level, $groupId, string $levelName, ?string $roundName = null): array
    {
        $playerCount = $players->count();
        $matches = [];
        
        if ($playerCount === 2) {
            // Create direct final with smart pairing
            $pairedPlayers = self::smartPairPlayers($players, $level);
            $match = self::createMatch($tournament, $pairedPlayers[0], $pairedPlayers[1], '2_final', $level, $groupId, $levelName);
            $matches[] = $match;
        }
        
        return $matches;
    }

    /**
     * Smart pair players avoiding same previous group when possible
     */
    public static function smartPairPlayers(Collection $players, string $level): array
    {
        $playerArray = $players->all();
        
        if (count($playerArray) <= 4) {
            // For small groups, use position-based pairing to avoid same previous group
            return self::pairAvoidingSamePreviousGroup($playerArray, $level);
        } else {
            // For larger groups, use simple shuffling
            return collect($playerArray)->shuffle()->all();
        }
    }

    /**
     * Pair players avoiding same previous group with position-based matching
     */
    public static function pairAvoidingSamePreviousGroup(array $players, string $level): array
    {
        $paired = [];
        $used = [];
        
        // Group players by previous group and position
        $groupedByPreviousAndPosition = [];
        foreach ($players as $player) {
            $previousGroupId = TournamentUtilityService::getPreviousGroupIdFromPlayer($player, $level);
            $position = $player->position ?? 1; // Default to position 1 if not set
            
            if (!isset($groupedByPreviousAndPosition[$previousGroupId])) {
                $groupedByPreviousAndPosition[$previousGroupId] = [];
            }
            if (!isset($groupedByPreviousAndPosition[$previousGroupId][$position])) {
                $groupedByPreviousAndPosition[$previousGroupId][$position] = [];
            }
            $groupedByPreviousAndPosition[$previousGroupId][$position][] = $player;
        }
        
        $previousGroups = array_keys($groupedByPreviousAndPosition);
        
        if (count($previousGroups) >= 2) {
            // Priority order: Position 1 -> Position 2 -> Position 3
            for ($position = 1; $position <= 3; $position++) {
                self::pairPlayersAtPosition($groupedByPreviousAndPosition, $previousGroups, $position, $paired, $used, $level);
            }
            
            // Handle any remaining players from different groups (fallback pairing)
            self::pairRemainingCrossGroup($groupedByPreviousAndPosition, $previousGroups, $paired, $used, $level);
        }
        
        // Add any remaining unpaired players (same group pairing as last resort)
        foreach ($players as $player) {
            if (!in_array($player->id, $used)) {
                $paired[] = $player;
            }
        }
        
        return $paired;
    }

    /**
     * Pair players at specific position from different previous groups
     */
    private static function pairPlayersAtPosition($groupedByPreviousAndPosition, $previousGroups, $position, &$paired, &$used, $level): void
    {
        $playersAtPosition = [];
        
        // Collect all players at this position from all previous groups
        foreach ($previousGroups as $prevGroupId) {
            if (isset($groupedByPreviousAndPosition[$prevGroupId][$position])) {
                foreach ($groupedByPreviousAndPosition[$prevGroupId][$position] as $player) {
                    if (!in_array($player->id, $used)) {
                        $playersAtPosition[] = ['player' => $player, 'prev_group' => $prevGroupId];
                    }
                }
            }
        }
        
        // Pair players from different previous groups
        $usedGroups = [];
        for ($i = 0; $i < count($playersAtPosition) - 1; $i++) {
            if (in_array($playersAtPosition[$i]['player']->id, $used)) continue;
            
            for ($j = $i + 1; $j < count($playersAtPosition); $j++) {
                if (in_array($playersAtPosition[$j]['player']->id, $used)) continue;
                
                // Pair if from different previous groups
                if ($playersAtPosition[$i]['prev_group'] !== $playersAtPosition[$j]['prev_group']) {
                    $paired[] = $playersAtPosition[$i]['player'];
                    $paired[] = $playersAtPosition[$j]['player'];
                    $used[] = $playersAtPosition[$i]['player']->id;
                    $used[] = $playersAtPosition[$j]['player']->id;
                    $usedGroups[] = $playersAtPosition[$i]['prev_group'];
                    $usedGroups[] = $playersAtPosition[$j]['prev_group'];
                    break;
                }
            }
        }
        
        // Handle odd number of players at this position
        if (count($playersAtPosition) % 2 === 1) {
            self::handleOddPositionPairing($groupedByPreviousAndPosition, $previousGroups, $position, $paired, $used, $level);
        }
    }

    /**
     * Handle odd position pairing (e.g., 3 position 1s -> pair with position 2)
     */
    private static function handleOddPositionPairing($groupedByPreviousAndPosition, $previousGroups, $currentPosition, &$paired, &$used, $level): void
    {
        $nextPosition = $currentPosition + 1;
        
        // Find unpaired player at current position
        $unpairedAtCurrent = null;
        foreach ($previousGroups as $prevGroupId) {
            if (isset($groupedByPreviousAndPosition[$prevGroupId][$currentPosition])) {
                foreach ($groupedByPreviousAndPosition[$prevGroupId][$currentPosition] as $player) {
                    if (!in_array($player->id, $used)) {
                        $unpairedAtCurrent = ['player' => $player, 'prev_group' => $prevGroupId];
                        break 2;
                    }
                }
            }
        }
        
        if ($unpairedAtCurrent && $nextPosition <= 3) {
            // Try to pair with someone from next position in different group
            foreach ($previousGroups as $prevGroupId) {
                if ($prevGroupId === $unpairedAtCurrent['prev_group']) continue;
                
                if (isset($groupedByPreviousAndPosition[$prevGroupId][$nextPosition])) {
                    foreach ($groupedByPreviousAndPosition[$prevGroupId][$nextPosition] as $player) {
                        if (!in_array($player->id, $used)) {
                            $paired[] = $unpairedAtCurrent['player'];
                            $paired[] = $player;
                            $used[] = $unpairedAtCurrent['player']->id;
                            $used[] = $player->id;
                            return;
                        }
                    }
                }
            }
        }
    }

    /**
     * Pair any remaining players from different groups (fallback)
     */
    private static function pairRemainingCrossGroup($groupedByPreviousAndPosition, $previousGroups, &$paired, &$used, $level): void
    {
        $remainingPlayers = [];
        
        // Collect all remaining unpaired players
        foreach ($previousGroups as $prevGroupId) {
            foreach ($groupedByPreviousAndPosition[$prevGroupId] as $position => $positionPlayers) {
                foreach ($positionPlayers as $player) {
                    if (!in_array($player->id, $used)) {
                        $remainingPlayers[] = ['player' => $player, 'prev_group' => $prevGroupId];
                    }
                }
            }
        }
        
        // Pair remaining players from different groups
        for ($i = 0; $i < count($remainingPlayers) - 1; $i++) {
            if (in_array($remainingPlayers[$i]['player']->id, $used)) continue;
            
            for ($j = $i + 1; $j < count($remainingPlayers); $j++) {
                if (in_array($remainingPlayers[$j]['player']->id, $used)) continue;
                
                if ($remainingPlayers[$i]['prev_group'] !== $remainingPlayers[$j]['prev_group']) {
                    $paired[] = $remainingPlayers[$i]['player'];
                    $paired[] = $remainingPlayers[$j]['player'];
                    $used[] = $remainingPlayers[$i]['player']->id;
                    $used[] = $remainingPlayers[$j]['player']->id;
                    break;
                }
            }
        }
    }

    /**
     * Create a single match with proper structure
     */
    public static function createMatch(Tournament $tournament, $player1, $player2, string $roundName, string $level, ?int $groupId, string $levelName, ?int $byePlayerId = null, ?string $matchName = null): PoolMatch
    {
        $matchName = $matchName ?? "{$roundName}_match";
        
        return PoolMatch::create([
            'match_name' => $matchName,
            'player_1_id' => $player1->id,
            'player_2_id' => $player2->id,
            'bye_player_id' => $byePlayerId,
            'level' => $level,
            'level_name' => $levelName,
            'round_name' => $roundName,
            'tournament_id' => $tournament->id,
            'group_id' => $groupId,
            'status' => 'pending',
            'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
        ]);
    }

    /**
     * Create standard round 1 matches for 5+ players with smart pairing
     */
    public static function createStandardRound1WithSmartPairing(Tournament $tournament, Collection $players, string $level, int $groupId, string $levelName): array
    {
        $matches = [];
        $pairedPlayers = self::smartPairPlayers($players, $level);
        $playerCount = count($pairedPlayers);
        $matchNumber = 1;
        
        // Create matches for paired players
        for ($i = 0; $i < $playerCount - 1; $i += 2) {
            if ($i + 1 < $playerCount) {
                $matchName = "round_1__match{$matchNumber}";
                $match = self::createMatch(
                    $tournament, 
                    $pairedPlayers[$i], 
                    $pairedPlayers[$i + 1], 
                    'round_1', 
                    $level, 
                    $groupId, 
                    $levelName, 
                    null, 
                    $matchName
                );
                $matches[] = $match;
                $matchNumber++;
            }
        }
        
        // Handle odd player (if any) - they get a bye to next round
        if ($playerCount % 2 === 1) {
            $oddPlayer = $pairedPlayers[$playerCount - 1];
            
            // Pair odd player with first player (who plays twice)
            if (count($pairedPlayers) >= 3) {
                $matchName = "round_1__match{$matchNumber}";
                $match = self::createMatch(
                    $tournament, 
                    $oddPlayer, 
                    $pairedPlayers[0], 
                    'round_1', 
                    $level, 
                    $groupId, 
                    $levelName, 
                    null, 
                    $matchName
                );
                $matches[] = $match;
            }
        }
        
        \Log::info("Created " . count($matches) . " round_1 matches for {$playerCount} players at {$level} level");
        
        return $matches;
    }

    /**
     * Create standard matches with proper structure
     */
    public static function createStandardMatches(Tournament $tournament, Collection $players, string $level, $groupId, string $roundName, string $levelName): array
    {
        $matches = [];
        $playerArray = $players->values()->all();
        $playerCount = count($playerArray);
        $matchNumber = 1;
        
        \Log::info("Creating standard matches", [
            'player_count' => $playerCount,
            'level' => $level,
            'round_name' => $roundName,
            'group_id' => $groupId
        ]);
        
        // Create matches for pairs of players
        for ($i = 0; $i < $playerCount; $i += 2) {
            if (isset($playerArray[$i + 1])) {
                $match = PoolMatch::create([
                    'match_name' => "{$roundName}_M{$matchNumber}",
                    'player_1_id' => $playerArray[$i]->id,
                    'player_2_id' => $playerArray[$i + 1]->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => $roundName,
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
                ]);
                $matches[] = $match;
                $matchNumber++;
            }
        }
        
        // Handle odd player if any - one player must play twice
        if ($playerCount % 2 === 1) {
            $oddPlayer = $playerArray[$playerCount - 1];
            \Log::info("Handling odd player - creating additional matches", [
                'odd_player_id' => $oddPlayer->id,
                'odd_player_name' => $oddPlayer->name,
                'total_players' => $playerCount
            ]);
            
            // Pick a random player to play twice (avoid the odd player)
            $doublePlayerIndex = rand(0, $playerCount - 2); // Exclude the odd player
            $doublePlayer = $playerArray[$doublePlayerIndex];
            
            \Log::info("Player will play twice", [
                'double_player_id' => $doublePlayer->id,
                'double_player_name' => $doublePlayer->name
            ]);
            
            // Create additional match: double player vs odd player
            $match = PoolMatch::create([
                'match_name' => "{$roundName}_M{$matchNumber}",
                'player_1_id' => $doublePlayer->id,
                'player_2_id' => $oddPlayer->id,
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => $roundName,
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
            $matches[] = $match;
            $matchNumber++;
            
            \Log::info("Created additional match for odd player", [
                'match_id' => $match->id,
                'match_name' => $match->match_name,
                'double_player' => $doublePlayer->name,
                'odd_player' => $oddPlayer->name
            ]);
        }
        
        \Log::info("Created " . count($matches) . " standard matches for {$playerCount} players");
        
        return $matches;
    }

    /**
     * Create matches for special tournament next round
     */
    public static function createSpecialTournamentMatches(Tournament $tournament, Collection $players, string $level, ?int $groupId, string $roundName): void
    {
        $levelName = TournamentUtilityService::getLevelName($level, $groupId);
        $playerCount = $players->count();
        
        \Log::info("Creating special tournament matches", [
            'tournament_id' => $tournament->id,
            'level' => $level,
            'round_name' => $roundName,
            'player_count' => $playerCount
        ]);
        
        if ($playerCount <= 4) {
            self::handleSpecialCases($tournament, $players, $level, $groupId, $levelName, $roundName);
        } else {
            // Standard pairing for >4 players
            self::createStandardMatchesWithAvoidance($tournament, $players, $level, $groupId, $roundName, $levelName);
        }
    }

    /**
     * Create standard matches with community avoidance and proper odd number handling
     */
    public static function createStandardMatchesWithAvoidance(Tournament $tournament, Collection $players, string $level, $groupId, string $roundName, string $levelName): void
    {
        // Convert Collection to simple array with proper indexing
        $playerArray = $players->values()->all();
        $playerCount = count($playerArray);
        $matchNumber = 1;
        
        \Log::info("=== CREATE STANDARD MATCHES WITH AVOIDANCE START ===", [
            'player_count' => $playerCount,
            'level' => $level,
            'round_name' => $roundName,
            'group_id' => $groupId,
            'tournament_id' => $tournament->id
        ]);
        
        if ($playerCount % 2 === 1) {
            // Odd number: One player plays twice
            $doublePlayer = $playerArray[0]; // First player plays twice
            $remainingPlayers = array_slice($playerArray, 1);
            
            \Log::info("Handling odd player count - double player strategy", [
                'double_player_id' => $doublePlayer->id,
                'double_player_name' => $doublePlayer->name,
                'remaining_players' => count($remainingPlayers)
            ]);
            
            // Find best opponent for double player (avoid same community if possible)
            $firstOpponentIndex = 0;
            for ($i = 0; $i < count($remainingPlayers); $i++) {
                if ($level !== 'community' && $doublePlayer->community_id !== $remainingPlayers[$i]->community_id) {
                    $firstOpponentIndex = $i;
                    break;
                }
            }
            $firstOpponent = $remainingPlayers[$firstOpponentIndex];
            
            $match1 = PoolMatch::create([
                'match_name' => "{$roundName}_M{$matchNumber}",
                'player_1_id' => $doublePlayer->id,
                'player_2_id' => $firstOpponent->id,
                'level' => $level,
                'level_name' => $levelName,
                'round_name' => $roundName,
                'tournament_id' => $tournament->id,
                'group_id' => $groupId,
                'status' => 'pending',
                'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
            ]);
            $matchNumber++;
            
            // Remove the first opponent from remaining players
            unset($remainingPlayers[$firstOpponentIndex]);
            $remainingPlayers = array_values($remainingPlayers); // Re-index
            
            \Log::info("Created first match for double player", [
                'match_id' => $match1->id,
                'opponent_id' => $firstOpponent->id,
                'remaining_count' => count($remainingPlayers)
            ]);
            
            // Pair remaining players normally
            for ($i = 0; $i < count($remainingPlayers); $i += 2) {
                if (isset($remainingPlayers[$i + 1])) {
                    $match = PoolMatch::create([
                        'match_name' => "{$roundName}_M{$matchNumber}",
                        'player_1_id' => $remainingPlayers[$i]->id,
                        'player_2_id' => $remainingPlayers[$i + 1]->id,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => $roundName,
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
                    ]);
                    $matchNumber++;
                }
            }
            
            // Create second match for double player with last remaining player
            if (count($remainingPlayers) % 2 === 1) {
                $lastPlayer = $remainingPlayers[count($remainingPlayers) - 1];
                
                \Log::info("Creating second match for double player", [
                    'double_player_id' => $doublePlayer->id,
                    'last_player_id' => $lastPlayer->id
                ]);
                
                $finalMatch = PoolMatch::create([
                    'match_name' => "{$roundName}_M{$matchNumber}",
                    'player_1_id' => $doublePlayer->id,
                    'player_2_id' => $lastPlayer->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => $roundName,
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
                ]);
            }
        } else {
            // Even number: Standard pairing
            \Log::info("Even player count - standard pairing");
            
            for ($i = 0; $i < $playerCount; $i += 2) {
                if (isset($playerArray[$i + 1])) {
                    $match = PoolMatch::create([
                        'match_name' => "{$roundName}_M{$matchNumber}",
                        'player_1_id' => $playerArray[$i]->id,
                        'player_2_id' => $playerArray[$i + 1]->id,
                        'level' => $level,
                        'level_name' => $levelName,
                        'round_name' => $roundName,
                        'tournament_id' => $tournament->id,
                        'group_id' => $groupId,
                        'status' => 'pending',
                        'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
                    ]);
                    $matchNumber++;
                }
            }
        }
        
        \Log::info("=== CREATE STANDARD MATCHES WITH AVOIDANCE END ===", [
            'matches_created' => $matchNumber - 1,
            'tournament_id' => $tournament->id
        ]);
    }

    /**
     * Propose available days for a match
     */
    public static function proposeMatchDays(PoolMatch $match): void
    {
        // Propose next 7 days (excluding today) as available options
        $proposedDates = [];
        for ($i = 1; $i <= 7; $i++) {
            $date = now()->addDays($i);
            $proposedDates[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'available' => true
            ];
        }
        
        $match->update([
            'proposed_dates' => json_encode($proposedDates)
        ]);
        
        \Log::info("Proposed match days updated", [
            'match_id' => $match->id,
            'proposed_dates_count' => count($proposedDates)
        ]);
    }

    /**
     * Create matches with position-based grouping and community avoidance
     */
    public static function createPositionBasedMatches(Tournament $tournament, Collection $players, string $level, string $position): void
    {
        // Group by geographic area to avoid same-origin matches
        $groupedPlayers = self::groupPlayersByGeography($players, $level);
        
        foreach ($groupedPlayers as $groupId => $groupPlayers) {
            $levelName = TournamentUtilityService::getLevelName($level, $groupId);
            
            // Create matches with community/county avoidance
            self::createCommunityAwareMatches($groupPlayers, $tournament, $level, $groupId, 'round_1', $position, $levelName);
        }
    }

    /**
     * Group players by geography based on level
     */
    private static function groupPlayersByGeography(Collection $players, string $level): Collection
    {
        switch ($level) {
            case 'county':
                return $players->groupBy('county_id');
            case 'regional':
                return $players->groupBy('region_id');
            case 'national':
                return collect([1 => $players]); // Single national group
            default:
                return $players->groupBy('community_id');
        }
    }

    /**
     * Create matches with community/county avoidance
     */
    public static function createCommunityAwareMatches(Collection $players, Tournament $tournament, string $level, $groupId, string $roundName, string $suffix = '', string $levelName = ''): void
    {
        $playerCount = $players->count();
        
        \Log::info("Creating community-aware matches", [
            'player_count' => $playerCount,
            'level' => $level,
            'group_id' => $groupId,
            'round_name' => $roundName,
            'suffix' => $suffix
        ]);
        
        if ($playerCount < 2) {
            \Log::info("Not enough players for matches in group {$groupId}");
            return;
        }
        
        // Use smart pairing to avoid same-origin matches
        $pairedPlayers = self::smartPairPlayers($players, $level);
        $matchNumber = 1;
        
        // Create matches from paired players
        for ($i = 0; $i < count($pairedPlayers); $i += 2) {
            if (isset($pairedPlayers[$i + 1])) {
                $matchName = $suffix ? "{$roundName}_{$suffix}_M{$matchNumber}" : "{$roundName}_M{$matchNumber}";
                
                PoolMatch::create([
                    'match_name' => $matchName,
                    'player_1_id' => $pairedPlayers[$i]->id,
                    'player_2_id' => $pairedPlayers[$i + 1]->id,
                    'level' => $level,
                    'level_name' => $levelName,
                    'round_name' => $roundName,
                    'tournament_id' => $tournament->id,
                    'group_id' => $groupId,
                    'status' => 'pending',
                    'proposed_dates' => \App\Services\ProposedDatesService::generateProposedDates($tournament->id),
                ]);
                
                $matchNumber++;
            }
        }
        
        \Log::info("Created {$matchNumber} community-aware matches for group {$groupId}");
    }
}
