<?php

namespace App\Console\Commands;

use App\Http\Middleware\StorefrontMaintenanceMode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class StorefrontDownCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storefront:down {--message= : Custom maintenance notice displayed to customers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Put the Vanriti storefront into maintenance mode (keeps Admin Panel fully accessible)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $payload = [
            'status' => 'maintenance',
            'started_at' => now()->toIso8601String(),
            'message' => $this->option('message') ?: "We're currently enhancing our storefront to bring you a refreshed Ayurvedic wellness experience. We'll be back shortly!",
        ];

        $filePath = StorefrontMaintenanceMode::maintenanceFilePath();
        $directory = dirname($filePath);

        if (! is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Clear view cache so updated status renders immediately
        Artisan::call('view:clear');

        $this->components->info('🌿 Vanriti Storefront is now in MAINTENANCE MODE.');
        $this->line('  • <comment>Storefront:</comment> Visitors will see the branded Ayurvedic maintenance page (503).');
        $this->line('  • <comment>Admin Panel:</comment> Unaffected! Accessible at <info>/admin</info>');
        $this->line('  • <comment>Preview Mode:</comment> Visit <info>/?preview=vanriti</info> to test the storefront as an admin.');
        $this->line('  • <comment>To Bring Live:</comment> Run <info>php artisan storefront:up</info>');

        return Command::SUCCESS;
    }
}

