<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

class PayrollExportBca implements WithMultipleSheets
{
    protected $data;
    protected $month;
    protected $year;
    protected $payrollDate;
    protected $departmentId;
    public function __construct($data, $month, $year, $payrollDate = null, $departmentId = null)
    {
        $this->data = $data;
        $this->month = (int) $month;
        $this->year = (int) $year;
        $this->departmentId = $departmentId !== null ? (string)$departmentId : null;

        if ($payrollDate instanceof Carbon) {
            $this->payrollDate = $payrollDate->copy();
        } elseif ($payrollDate) {
            try {
                $this->payrollDate = Carbon::parse($payrollDate);
            } catch (\Exception $e) {
                $this->payrollDate = Carbon::create($this->year, $this->month, 5);
            }
        } else {
            // default to day 5 of the period to keep consistency with period month/year
            $this->payrollDate = Carbon::create($this->year, $this->month, 5);
        }
    }

    public function sheets(): array
    {
        // return numeric array; sheet title handled inside sheet classes (WithTitle)
        return [
            new PayrollBCASheet($this->data, $this->month, $this->year, $this->payrollDate, $this->departmentId),
            new PayrollLegendSheet(),
        ];
    }
}
