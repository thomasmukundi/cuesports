<?php

namespace App\Console\Commands;

use App\Jobs\CheckTournamentCompletion;
use App\Models\Tournament;
use Illuminate\Console\Command;

class CheckTournaments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tournaments:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all active tournaments for completion and progression';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking active tournaments...');
        
        $tournaments = Tournament::where('status', 'ongoing')->get();
        
        if ($tournaments->isEmpty()) {
            $this->info('No active tournaments found.');
            return 0;
        }
        
        foreach ($tournaments as $tournament) {
            $this->info("Dispatching check for: {$tournament->name}");
            CheckTournamentCompletion::dispatch($tournament->id);
        }
        
        $this->info("Dispatched checks for {$tournaments->count()} tournament(s).");
        
        return 0;
    }
}
