<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Unpaid Razorpay checkouts reserve stock. Sweep them every 15 minutes so a
// unit is not held for the rest of the day. Requires schedule:run each minute.
Schedule::command('orders:expire-pending-payments')->everyFifteenMinutes();

// Send WhatsApp recovery message to customers who initiated checkout in the last 24h
// but did not complete payment.
Schedule::command('checkout:send-recovery-reminders --hours=1')->hourly();

// Send Web Push notification reminders to anonymous & registered subscribers who abandoned their cart
Schedule::command('push:send-abandoned-cart-reminders')->everyThirtyMinutes();

// Sync active ShipMojo shipments (AWB, courier, tracking URL, in-transit & delivery scans)
Schedule::command('shipmojo:sync-tracking')->everyThirtyMinutes();
