<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class NotifikasiReminder extends Command
{
    protected $signature = "absensi:notifikasi-reminder";
    protected $description = "Kirim reminder absensi 15 menit sebelum shift";

    public function handle()
    {
        $now = Carbon::now();

        // target waktu shift (30 menit dari sekarang)
        $target = $now->copy()->addMinutes(30);

        // toleransi 30 detik sebelum dan sesudah
        $start = $target->copy()->subSeconds(30);
        $end = $target->copy()->addSeconds(30);

        $this->info(
            "Cek shift antara {$start->format("H:i:s")} - {$end->format(
                "H:i:s",
            )}",
        );

        $users = DB::table("tuserschedule")
            ->join("muser", "muser.nid", "=", "tuserschedule.nuserid")
            ->whereDate("dwork", $now->toDateString())
            ->where(function ($query) use ($start, $end) {
                $query
                    ->whereBetween("dstart", [
                        $start->format("H:i:s"),
                        $end->format("H:i:s"),
                    ])
                    ->orWhereBetween("dend", [
                        $start->format("H:i:s"),
                        $end->format("H:i:s"),
                    ])
                    ->orWhereBetween("dstart2", [
                        $start->format("H:i:s"),
                        $end->format("H:i:s"),
                    ])
                    ->orWhereBetween("dend2", [
                        $start->format("H:i:s"),
                        $end->format("H:i:s"),
                    ]);
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info("Tidak ada shift yang dimulai 30 menit lagi.");
            return;
        }

        foreach ($users as $user) {
            try {
                $body = "Jangan lupa absen hari ini 😊";

                // cek apakah waktu masuk
                if (
                    ($user->dstart >= $start->format("H:i:s") &&
                        $user->dstart <= $end->format("H:i:s")) ||
                    ($user->dstart2 >= $start->format("H:i:s") &&
                        $user->dstart2 <= $end->format("H:i:s"))
                ) {
                    $body = "Jangan lupa absen masuk hari ini 😊";
                }

                // cek apakah waktu pulang
                elseif (
                    ($user->dend >= $start->format("H:i:s") &&
                        $user->dend <= $end->format("H:i:s")) ||
                    ($user->dend2 >= $start->format("H:i:s") &&
                        $user->dend2 <= $end->format("H:i:s"))
                ) {
                    $body = "Jangan lupa absen pulang hari ini 😊";
                }

                //                Http::post('https://absensi.matahati.my.id/laravel/public/api/send-notif', [
                //                    "user_id" => $user->nuserid,
                //                    "title"   => "Absensi Matahati",
                //                    "body"    => $body
                //                ]);

                $response = Http::timeout(10)->post(
                    config('app.url') . '/api/send-notif',
                    [
                        "user_id" => $user->nuserid,
                        "title"   => "Absensi Matahati",
                        "body"    => $body,
                    ]
                );

                $this->info("Status: " . $response->status());

                $this->info("Notif dikirim ke user ID: {$user->nuserid}");
            } catch (\Exception $e) {
                $this->error("Gagal kirim notif ke user {$user->nuserid}");
                $this->error($e->getMessage());
            }
        }

        $this->info("Reminder selesai.");
    }
}
