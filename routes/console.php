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
