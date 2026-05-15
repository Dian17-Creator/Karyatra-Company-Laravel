<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AttendanceReportTextService;
use Illuminate\Support\Facades\Mail;

class AttendanceReportTextRun extends Command
{
    protected $signature = 'attendance:send-text';
    protected $description = 'Send attendance report as text to HR';

    public function handle(AttendanceReportTextService $service)
    {
        $this->info('Generating attendance text report...');

        $service->sendDaily();

        $this->info('Attendance text report sent successfully');
    }
}
