<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        api: __DIR__ . "/../routes/api.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )

    // ->withSchedule(function (Schedule $schedule) {
    //     $schedule
    //         ->call(function () {
    //             Log::info("✅ Cron Laravel 11 jalan pada " . now());

    //             //$url = 'https://absensi.matahati.my.id/laravel/public/notifikasi/send-emails';
    //             $url = url("notifikasi/send-emails");
    //             $token = env("CRON_TOKEN");

    //             try {
    //                 $response = Http::timeout(15)->get($url, [
    //                     "token" => $token,
    //                 ]);

    //                 Log::info("📨 Scheduler: Notifikasi dipanggil", [
    //                     "status" => $response->status(),
    //                     "body" => $response->body(),
    //                 ]);
    //             } catch (\Exception $e) {
    //                 Log::error(
    //                     "🚫 Gagal memanggil endpoint notifikasi: " .
    //                         $e->getMessage(),
    //                 );
    //             }
    //         })
    //         ->weeklyOn(1, "08:00"); // hanya SENIN, jam 08:00
    // })

    ->withMiddleware(function (Middleware $middleware): void {
        //
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
