<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Tournament;

class ProposedDatesService
{
    /**
     * Generate proposed dates for a match
     * 
     * @param int $tournamentId
     * @param int $daysCount Number of dates to generate (default: 7)
     * @return array
     */
    public static function generateProposedDates(int $tournamentId, int $daysCount = 7): array
    {
        $tournament = Tournament::find($tournamentId);
        
        if (!$tournament) {
            return [];
        }
        
        // Start date: tomorrow or tournament start date, whichever is later
        $tomorrow = Carbon::tomorrow();
        $tournamentStart = $tournament->start_date ? Carbon::parse($tournament->start_date) : $tomorrow;
        $startDate = $tomorrow->gt($tournamentStart) ? $tomorrow : $tournamentStart;
        
        // End date: tournament end date or 30 days from start, whichever is earlier
        $tournamentEnd = $tournament->end_date ? Carbon::parse($tournament->end_date) : null;
        $maxEndDate = $tournamentEnd ? $tournamentEnd : $startDate->copy()->addDays(30);
        
        // Ensure we don't go beyond tournament end date if it exists
        if ($tournamentEnd && $maxEndDate->gt($tournamentEnd)) {
            $maxEndDate = $tournamentEnd;
        }
        
        $proposedDates = [];
        $currentDate = $startDate->copy();
        $generatedCount = 0;
        
        // Generate dates within tournament period
        while ($generatedCount < $daysCount && $currentDate->lte($maxEndDate)) {
            // Skip past dates (should not happen with our logic, but safety check)
            if ($currentDate->gte(Carbon::today())) {
                $proposedDates[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day_name' => $currentDate->format('l'),
                    'available' => true
                ];
                $generatedCount++;
            }
            $currentDate->addDay();
        }
        
        // If tournament period is too short and we need more dates, 
        // extend beyond tournament end date for flexibility (but warn in logs)
        if ($generatedCount < $daysCount && $tournamentEnd) {
            \Log::info("Tournament period too short for proposed dates, extending beyond end date", [
                'tournament_id' => $tournamentId,
                'tournament_end' => $tournamentEnd->format('Y-m-d'),
                'dates_needed' => $daysCount,
                'dates_generated' => $generatedCount
            ]);
            
            while ($generatedCount < $daysCount) {
                $proposedDates[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day_name' => $currentDate->format('l'),
                    'available' => true
                ];
                $generatedCount++;
                $currentDate->addDay();
            }
        }
        
        return $proposedDates;
    }
    
    /**
     * Generate proposed dates as JSON string
     * 
     * @param int $tournamentId
     * @param int $daysCount
     * @return string
     */
    public static function generateProposedDatesJson(int $tournamentId, int $daysCount = 7): string
    {
        $dates = self::generateProposedDates($tournamentId, $daysCount);
        return json_encode($dates);
    }
}
