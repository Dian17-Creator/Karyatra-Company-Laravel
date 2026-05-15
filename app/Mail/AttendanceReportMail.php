<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AttendanceReportMail extends Mailable
{
    public $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function build()
    {
        return $this->subject('Laporan Absensi Harian')
            ->view('emails.attendance')
            ->attach($this->file);
    }
}
