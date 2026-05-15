<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\PayrollCalculationController;
use App\Http\Controllers\MagendaController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\NotifikasiController;

Route::get('/logs/{userId}', [BackofficeController::class, 'apiLogs']);
Route::get('/schedule/{userId}', [ScheduleController::class, 'apiUserSchedule']);
Route::get('/schedule/today/{userId}', [ScheduleController::class, 'apiTodayShift']);
Route::prefix('user/gaji')->group(function () {
    Route::get('{userId}', [PayrollCalculationController::class, 'getUserSalary']);
    Route::post('status', [PayrollCalculationController::class, 'updateSalaryStatus']);
});

// Route::get('/agenda/{userId}/{month}', [MagendaController::class, 'mobile']);
Route::get('/agenda/{month}', [MagendaController::class, 'mobile']);

// Notifikasi FCM Routes
Route::post('/save-token', [DeviceTokenController::class, 'store']);
Route::post('/send-notif', [DeviceTokenController::class, 'sendNotif']);

// Notifikasi ke email
Route::get('/trigger-email', [NotifikasiController::class, 'trigger']);
