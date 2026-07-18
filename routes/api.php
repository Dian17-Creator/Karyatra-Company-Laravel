<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\Backoffice\LogController;
use App\Http\Controllers\Backoffice\UserController;
use App\Http\Controllers\Backoffice\DepartmentController;
use App\Http\Controllers\Backoffice\BankController;
use App\Http\Controllers\PayrollCalculationController;
use App\Http\Controllers\MagendaController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\FaceApprovalController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Backoffice\CompanyController;

Route::get('/logs/{userId}', [LogController::class, 'apiLogs']);
Route::get('/schedule/{userId}', [ScheduleController::class, 'apiUserSchedule']);
Route::get('/schedule/today/{userId}', [ScheduleController::class, 'apiTodayShift']);
Route::prefix('user/gaji')->group(function () {
    Route::get('list', [GajiController::class, 'apiList']);
    Route::get('{userId}', [PayrollCalculationController::class, 'getUserSalary']);
    Route::post('status', [PayrollCalculationController::class, 'updateSalaryStatus']);
    Route::get('{id}/detail', [GajiController::class, 'show']);
    Route::post('{id}/update', [GajiController::class, 'update']);
});

// Route::get('/agenda/{userId}/{month}', [MagendaController::class, 'mobile']);
Route::get('/agenda/{month}', [MagendaController::class, 'mobile']);

// Notifikasi FCM Routes
Route::post('/save-token', [DeviceTokenController::class, 'store']);
Route::post('/send-notif', [DeviceTokenController::class, 'sendNotif']);

// Notifikasi ke email
Route::get('/trigger-email', [NotifikasiController::class, 'trigger']);

// Face Approval API
Route::get('/face-approval/pending', [FaceApprovalController::class, 'apiPendingList']);
Route::post('/face-approval/{id}/approve', [FaceApprovalController::class, 'apiApprove']);
Route::post('/face-approval/{id}/reject', [FaceApprovalController::class, 'apiReject']);

// Store User Api
Route::post('/user/store', [UserController::class, 'apiStoreUser']);
Route::get('/department/list', [DepartmentController::class, 'apiDepartmentList']);
Route::get('/bank/list', [BankController::class, 'apiBankList']);
Route::get('/mandiri/rekening', [BankController::class, 'apiMandiriRekening']);

Route::get('/register/check-company', [LoginController::class, 'apiCheckCompany']);
Route::post('/register', [LoginController::class, 'apiRegister']);

Route::get('/company/check', [CompanyController::class, 'apiCheckCompany']);
Route::post('/company/update', [CompanyController::class, 'apiUpdateCompany']);
