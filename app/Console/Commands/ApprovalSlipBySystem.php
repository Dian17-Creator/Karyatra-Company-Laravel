<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PayrollBySystemService;

class ApprovalSlipBySystem extends Command
{
    protected $signature = 'app:approval-slip-by-system';
    protected $description = 'Auto approve slip by system & send email';

    public function handle()
    {
        app(PayrollBySystemService::class)->run();

        $this->info('Auto approval + kirim slip selesai');
    }
}
