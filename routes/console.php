<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly sweep: cancel Razorpay checkouts that were abandoned before payment
// and restore the reserved stock. Requires a scheduler process
// (schedule:run every minute) or 'schedule:work' on the server.
Schedule::command('orders:expire-pending-payments')->dailyAt('02:00');
