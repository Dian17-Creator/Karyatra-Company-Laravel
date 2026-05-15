<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailOnChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        $data = $this->data;

        // 🔥 Tentukan subject berdasarkan tipe
        $subject = match ($data->tipe) {
            'izin'   => 'Notifikasi Pengajuan Izin',
            'manual' => 'Notifikasi Absen Manual',
            default  => 'Notifikasi Absensi'
        };

        Mail::send('emails.notif', ['data' => $data], function ($msg) use ($subject) {
            $msg->to([
                config('mail.attendance_email')
            ])
                ->subject($subject);
        });
    }
}
