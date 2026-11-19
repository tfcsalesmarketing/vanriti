<?php

namespace App\Console\Commands;

use App\Http\Middleware\StorefrontMaintenanceMode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class StorefrontUpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storefront:up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bring the Vanriti storefront out of maintenance mode (live for all customers)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = StorefrontMaintenanceMode::maintenanceFilePath();

        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Clear view cache so storefront loads live immediately
        Artisan::call('view:clear');

        $this->components->info('🌿 Vanriti Storefront is now LIVE!');
        $this->line('  • Customers can browse products and checkout normally.');
        $this->line('  • To put back into maintenance anytime: <info>php artisan storefront:down</info>');

        return Command::SUCCESS;
    }
}
