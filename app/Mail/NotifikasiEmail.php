<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class NotifikasiEmail extends Mailable
{
    public $notifications;

    public function __construct($notifications)
    {
        $this->notifications = $notifications;
    }

    public function build()
    {
        return $this->subject('Notifikasi Baru dari Sistem Absensi')
            ->view('emails.notifikasi')
            ->with('notifications', $this->notifications);
    }
}
