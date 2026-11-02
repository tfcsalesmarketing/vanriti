<?php

namespace App\Console\Commands;

use App\Services\WebPushService;
use Illuminate\Console\Command;

class SendAbandonedCartPushReminders extends Command
{
    protected $signature = 'push:send-abandoned-cart-reminders';

    protected $description = 'Dispatch web push reminders to anonymous & registered subscribers who left items in their cart.';

    public function handle(WebPushService $webPushService): int
    {
        $this->info('Scanning for abandoned carts with active push subscribers...');

        $sentCount = $webPushService->sendAbandonedCartPushReminders();

        $this->info("Completed! Dispatched {$sentCount} abandoned cart push alerts.");

        return Command::SUCCESS;
    }
}
