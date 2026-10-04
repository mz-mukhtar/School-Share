<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RecalculateStorage extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'schoolshare:recalculate-storage';

    /**
     * The console command description.
     */
    protected $description = 'Recalculates and updates the storage_used_bytes for all users based on physical files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting storage recalculation for all users...');

        $users = User::with('projects.files')->get();
        $bar = $this->output->createProgressBar(count($users));
        
        foreach ($users as $user) {
            $totalBytes = 0;
            
            foreach ($user->projects as $project) {
                foreach ($project->files as $file) {
                    // Check if file exists physically
                    if (Storage::disk('public')->exists($file->path)) {
                        $totalBytes += Storage::disk('public')->size($file->path);
                    }
                }
            }
            
            // Update the user's storage limit
            $user->update(['storage_used_bytes' => $totalBytes]);
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Storage recalculation complete!');
    }
}
