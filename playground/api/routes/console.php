<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Copia de seguridad diaria + limpieza por retención (doc 06, DC-16). La
// retención se gobierna con motor.backup.keep_days.
Schedule::command('backup:run --disable-notifications')->dailyAt('03:00');
Schedule::command('backup:clean --disable-notifications')->dailyAt('03:30');

// PDFs temporales caducados (doc 02).
Schedule::command('pdf:cleanup')->hourly();
