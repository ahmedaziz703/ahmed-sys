<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh demo data by running migrate:refresh and seed commands';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if demo mode is active
        if (!config('app.app_demo_mode')) {
            $this->error('Demo mode is not active. This command can only be run in demo mode.');
            return 1;
        }

        $this->info('Starting demo data refresh...');
        
        // Enable maintenance mode with a message
        $this->info('Enabling maintenance mode...');
        Artisan::call('down', [
            '--render' => 'maintenance',
            '--refresh' => '30',
        ]);
        
        try {
            // Run the migration refresh and seeding
            $this->info('Running migrate:refresh...');
            Artisan::call('migrate:refresh', [
                '--force' => true,
            ]);
            
            $this->info('Running db:seed...');
            Artisan::call('db:seed', [
                '--force' => true,
            ]);
            
            $this->info('Demo data has been refreshed successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred while refreshing demo data: ' . $e->getMessage());
            
            // Disable maintenance mode even if there was an error
            $this->info('Disabling maintenance mode...');
            Artisan::call('up');
            
            return 1;
        }
        
        // Disable maintenance mode
        $this->info('Disabling maintenance mode...');
        Artisan::call('up');
        
        $this->info('Demo data refresh completed successfully!');
        
        return 0;
    }
} 