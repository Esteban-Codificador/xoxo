<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Link status of stored resources, shown in the CMS (needs `schedule:run` in cron).
Schedule::command('content:verify-resources')->dailyAt('03:30')->withoutOverlapping();
