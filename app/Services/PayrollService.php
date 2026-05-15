<?php

namespace App\Services;

use App\Jobs\CalculatePayrollJob;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function run(int $year, int $month): void
    {
        $userIds = DB::table('muser')
            ->where('factive', 1)
            //->where('nid', 32) // 👈 tambahin ini
            ->pluck('nid');

        foreach ($userIds as $uid) {
            CalculatePayrollJob::dispatchSync($uid, $year, $month);
        }
    }
}
