<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PayrollService;

class PayrollRun extends Command
{
    protected $signature = 'payroll:run {year?} {month?}';
    protected $description = 'Trigger monthly payroll calculation';

    public function handle(PayrollService $service)
    {
        $target = now()->subMonth();

        $year  = $this->argument('year')  ?? $target->year;
        $month = $this->argument('month') ?? $target->month;

        $this->info("Running payroll {$year}-{$month}");

        $service->run($year, $month);

        $this->info('Payroll jobs dispatched successfully');
    }
}
