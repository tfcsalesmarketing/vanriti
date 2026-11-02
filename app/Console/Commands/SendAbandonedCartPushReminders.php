<?php

namespace App\Console\Commands;

use App\Services\WebPushService;
use Illuminate\Console\Command;

class SendAbandonedCartPushReminders extends Command
{
    protected $signature = 'push:send-abandoned-cart-reminders
                            {--force : Ignore delay cutoffs and send to all carts with active push subscribers immediately}
                            {--delay= : Minimum minutes of inactivity before dispatching reminder (defaults to settings or 45)}';

    protected $description = 'Dispatch web push reminders to anonymous & registered subscribers who left items in their cart.';

    public function handle(WebPushService $webPushService): int
    {
        $force = (bool) $this->option('force');
        $delay = $this->option('delay') !== null ? (int) $this->option('delay') : 45;

        $this->info("Scanning for abandoned carts with active push subscribers (delay: {$delay}m, force: " . ($force ? 'yes' : 'no') . ')...');

        $sentCount = $webPushService->sendAbandonedCartPushReminders($delay, $force);

        $this->info("Completed! Dispatched {$sentCount} abandoned cart push alert(s).");

        return Command::SUCCESS;
    }
}
