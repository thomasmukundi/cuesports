<?php

namespace App\Services;

use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use App\Models\Tournament;
use Illuminate\Support\Facades\Log;

class TournamentUtilityService
{
    /**
     * Get level name from level and group ID
     */
    public static function getLevelName(string $level, $groupId): ?string
    {
        switch ($level) {
            case 'community':
                $community = Community::find($groupId);
                return $community ? $community->name : null;
            case 'county':
                $county = County::find($groupId);
                return $county ? $county->name : null;
            case 'regional':
                $region = Region::find($groupId);
                return $region ? $region->name : null;
            default:
                return null;
        }
    }

    /**
     * Get group ID from level and level name
     */
    public static function getGroupIdFromLevelName(string $level, ?string $levelName): ?int
    {
        if (!$levelName) {
            return null;
        }
        
        switch ($level) {
            case 'community':
                $community = Community::where('name', $levelName)->first();
                return $community ? $community->id : null;
            case 'county':
                $county = County::where('name', $levelName)->first();
                return $county ? $county->id : null;
            case 'regional':
                $region = Region::where('name', $levelName)->first();
                return $region ? $region->id : null;
            default:
                return null;
        }
    }

    /**
     * Get the column name for filtering players by level
     */
    public static function getLevelColumn(string $level): string
    {
        switch ($level) {
            case 'county':
                return 'county_id';
            case 'regional':
                return 'region_id';
            case 'national':
                return 'region_id'; // National uses region for grouping
            default:
                return 'community_id';
        }
    }

    /**
     * Check if we're at the tournament's target level (where final winners are determined)
     */
    public static function isAtTournamentTargetLevel(Tournament $tournament, string $level): bool
    {
        // Special tournaments don't have multi-level progression
        if ($tournament->special) {
            return true;
        }
        
        // If no area_scope is specified, default to national level
        if (!$tournament->area_scope || $tournament->area_scope === 'national') {
            return $level === 'national';
        }
        
        // Check if current level matches tournament's area_scope
        return $level === $tournament->area_scope;
    }

    /**
     * Get previous group ID from player based on current level
     */
    public static function getPreviousGroupIdFromPlayer($player, string $level): ?int
    {
        switch ($level) {
            case 'county':
                return $player->community_id;
            case 'regional':
                return $player->county_id;
            case 'national':
                return $player->region_id;
            default:
                return null;
        }
    }

    /**
     * Calculate number of matches created for a given number of players
     */
    public static function calculateMatchesCreated(int $playerCount): int
    {
        if ($playerCount <= 1) return 0;
        if ($playerCount == 2) return 1;
        if ($playerCount == 3) return 2; // SF + Final
        if ($playerCount == 4) return 3; // 2 SF + Final
        
        // For larger counts, use tournament bracket logic
        return intval(floor($playerCount / 2));
    }

    /**
     * Get tournament level hierarchy
     */
    public static function getTournamentLevelHierarchy(): array
    {
        return ['community', 'county', 'regional', 'national'];
    }

    /**
     * Get next level in tournament progression
     */
    public static function getNextLevel(string $currentLevel): ?string
    {
        $hierarchy = self::getTournamentLevelHierarchy();
        $currentIndex = array_search($currentLevel, $hierarchy);
        
        if ($currentIndex === false || $currentIndex === count($hierarchy) - 1) {
            return null; // No next level
        }
        
        return $hierarchy[$currentIndex + 1];
    }

    /**
     * Get previous level in tournament progression
     */
    public static function getPreviousLevel(string $currentLevel): ?string
    {
        $hierarchy = self::getTournamentLevelHierarchy();
        $currentIndex = array_search($currentLevel, $hierarchy);
        
        if ($currentIndex === false || $currentIndex === 0) {
            return null; // No previous level
        }
        
        return $hierarchy[$currentIndex - 1];
    }

    /**
     * Validate tournament level
     */
    public static function isValidLevel(string $level): bool
    {
        return in_array($level, self::getTournamentLevelHierarchy()) || $level === 'special';
    }

    /**
     * Get level display name
     */
    public static function getLevelDisplayName(string $level): string
    {
        switch ($level) {
            case 'community':
                return 'Community Level';
            case 'county':
                return 'County Level';
            case 'regional':
                return 'Regional Level';
            case 'national':
                return 'National Level';
            case 'special':
                return 'Special Tournament';
            default:
                return ucfirst($level) . ' Level';
        }
    }

    /**
     * Get area scope hierarchy
     */
    public static function getAreaScopeHierarchy(): array
    {
        return ['community', 'county', 'regional', 'national'];
    }

    /**
     * Check if area scope is valid
     */
    public static function isValidAreaScope(?string $areaScope): bool
    {
        if (!$areaScope) {
            return true; // null/empty is valid (defaults to national)
        }
        
        return in_array($areaScope, self::getAreaScopeHierarchy());
    }

    /**
     * Get tournament progression path based on area scope
     */
    public static function getTournamentProgressionPath(Tournament $tournament): array
    {
        if ($tournament->special) {
            return ['special'];
        }

        $areaScope = $tournament->area_scope ?? 'national';
        $hierarchy = self::getTournamentLevelHierarchy();
        
        // Find the target level index
        $targetIndex = array_search($areaScope, $hierarchy);
        if ($targetIndex === false) {
            $targetIndex = count($hierarchy) - 1; // Default to national
        }
        
        // Return progression path up to target level
        return array_slice($hierarchy, 0, $targetIndex + 1);
    }

    /**
     * Log utility method usage for debugging
     */
    public static function logUtilityUsage(string $method, array $parameters = []): void
    {
        Log::debug("TournamentUtilityService::{$method} called", $parameters);
    }
}
