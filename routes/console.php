<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-notification-reminders')->dailyAt('07:00');

// Parte 8 del plan de mejoras: mensualidades, recordatorios y mora.
Schedule::command('app:generate-monthly-fees')->dailyAt('06:00')->onOneServer();

// Sección 46 del plan: copias de seguridad. Solo la base de datos a diario
// (los archivos de la app cambian poco y ya están en control de versiones);
// el respaldo completo (código + BD) corre una vez por semana.
Schedule::command('backup:run --only-db')->dailyAt('02:00')->onOneServer();
Schedule::command('backup:run')->weeklyOn(1, '02:30')->onOneServer();
Schedule::command('backup:clean')->dailyAt('03:00')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('04:00')->onOneServer();
