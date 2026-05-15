<?php

namespace App\Observers;

use App\Jobs\CalculatePayrollJob;
use App\Models\Tusercontract;
use Carbon\Carbon;

class TusercontractObserver
{
    public function created(Tusercontract $contract)
    {
        // Ambil periode sekarang berdasarkan tanggal start kontrak
        $dt = $contract->dstart ? Carbon::parse($contract->dstart) : Carbon::now();
        $year = $dt->year;
        $month = $dt->month;

        // dispatch job per user untuk periode kontrak
        CalculatePayrollJob::dispatch((int)$contract->nuserid, $year, $month);
    }

    public function updated(Tusercontract $contract)
    {
        // recalc untuk periode kontrak (start month)
        $dt = $contract->dstart ? Carbon::parse($contract->dstart) : Carbon::now();
        $year = $dt->year;
        $month = $dt->month;

        CalculatePayrollJob::dispatch((int)$contract->nuserid, $year, $month);
    }

    public function deleted(Tusercontract $contract)
    {
        // opsional: dispatch recalc atau hapus csalary untuk periode terkait
        $dt = $contract->dstart ? Carbon::parse($contract->dstart) : Carbon::now();
        CalculatePayrollJob::dispatch((int)$contract->nuserid, $dt->year, $dt->month);
    }
}
