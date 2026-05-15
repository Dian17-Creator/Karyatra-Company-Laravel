<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
| Reminder absensi otomatis
*/

//Schedule::command('absensi:notifikasi-reminder')->everyMinute();

// Text Report Absensi HR
Schedule::command('attendance:send-text')
    ->dailyAt('11:00');

Schedule::command('attendance:send-text')
    ->dailyAt('17:00');

// Report Excel Mail HRD
Schedule::command('attendance:send')
    ->dailyAt('08:00');

Schedule::command('payroll:run')
    ->monthlyOn(30, '12:00');

// Auto approval slip gaji by system
Schedule::command('app:approval-slip-by-system')
    ->monthlyOn(4, '22:00');

//Notifikasi Reminder Masuk
// Schedule::command('absensi:notifikasi-reminder')
//     ->everyMinute();

//Notifikasi Reminder Pulang
// Schedule::command('absensi:notifikasi-reminder')
//     ->dailyAt('16:30');