<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());

})->purpose('Display an inspiring quote')->hourly();

Schedule::command('telescope:prune --hours=3')->hourly();

Schedule::command('app:update-weather')->hourly();

Schedule::command('push:birthday')->dailyAt('00:00');
Schedule::command('push:kuts')->dailyAt('09:00');
