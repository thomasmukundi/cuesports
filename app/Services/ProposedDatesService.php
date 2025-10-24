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
            \Log::warning("Tournament not found for proposed dates", ['tournament_id' => $tournamentId]);
            return [];
        }
        
        // Start date: today or tournament start date, whichever is later
        $today = Carbon::today();
        $tournamentStart = $tournament->start_date ? Carbon::parse($tournament->start_date) : $today;
        $startDate = $today->gt($tournamentStart) ? $today : $tournamentStart;
        
        // End date: tournament end date or 30 days from start, whichever is earlier
        $tournamentEnd = $tournament->end_date ? Carbon::parse($tournament->end_date) : null;
        $maxEndDate = $tournamentEnd ? $tournamentEnd : $startDate->copy()->addDays(30);
        
        \Log::info("Generating proposed dates", [
            'tournament_id' => $tournamentId,
            'tournament_start' => $tournament->start_date,
            'tournament_end' => $tournament->end_date,
            'calculated_start' => $startDate->format('Y-m-d'),
            'calculated_end' => $maxEndDate->format('Y-m-d'),
            'days_requested' => $daysCount
        ]);
        
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
        // only extend beyond tournament end date if absolutely necessary
        if ($generatedCount < $daysCount) {
            if ($tournamentEnd) {
                \Log::info("Tournament period too short for proposed dates, extending beyond end date", [
                    'tournament_id' => $tournamentId,
                    'tournament_end' => $tournamentEnd->format('Y-m-d'),
                    'dates_needed' => $daysCount,
                    'dates_generated' => $generatedCount
                ]);
            }
            
            // Only extend up to a reasonable limit (max 14 days beyond end date or 30 days total)
            $maxExtensionDate = $tournamentEnd ? 
                $tournamentEnd->copy()->addDays(14) : 
                $startDate->copy()->addDays(30);
            
            while ($generatedCount < $daysCount && $currentDate->lte($maxExtensionDate)) {
                $proposedDates[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day_name' => $currentDate->format('l'),
                    'available' => true
                ];
                $generatedCount++;
                $currentDate->addDay();
            }
        }
        
        \Log::info("Proposed dates generated successfully", [
            'tournament_id' => $tournamentId,
            'dates_generated' => count($proposedDates),
            'dates_requested' => $daysCount,
            'first_date' => $proposedDates[0]['date'] ?? 'none',
            'last_date' => end($proposedDates)['date'] ?? 'none'
        ]);
        
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
