<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AttendanceReportService;

class AttendanceReportRun extends Command
{
    protected $signature = 'attendance:send';
    protected $description = 'Send daily attendance report to HR';

    public function handle(AttendanceReportService $service)
    {
        $this->info('Generating attendance report...');

        $service->sendDaily();

        $this->info('Attendance report sent successfully');
    }
}
