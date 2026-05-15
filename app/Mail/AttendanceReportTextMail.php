<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AttendanceReportTextMail extends Mailable
{
    public $report;

    public function __construct($report)
    {
        $this->report = $report;
    }

    public function build()
    {
        return $this->subject('Laporan Absensi Harian')
            ->view('emails.attendancetext');
    }
}
