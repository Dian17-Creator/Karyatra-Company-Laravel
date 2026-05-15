<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

class MultiPayrollMandiriExport implements WithMultipleSheets
{
    protected $converterData;
    protected $bankList;
    protected $companyAccount;
    protected $companyAlias;
    protected $reference;
    protected $dateYmd;    // string 'YYYYMMDD' or null
    protected $routeUrl;
    protected $payrollDay;
    protected $periodYear;
    protected $periodMonth;

    /**
     * $dateYmd optional: string 'YYYYMMDD' or null
     * $periodYear, $periodMonth used when $dateYmd is null to compute proper month-year
     */
    public function __construct(
        $converterData = [],
        $bankList = [],
        $companyAccount = null,
        $companyAlias = null,
        $reference = null,
        $dateYmd = null,
        $routeUrl = null,
        $payrollDay = 5,
        $periodYear = null,
        $periodMonth = null
    ) {
        $this->converterData  = $converterData;
        $this->bankList       = $bankList;
        $this->companyAccount = $companyAccount;
        $this->companyAlias   = $companyAlias;
        $this->reference      = $reference;
        $this->dateYmd        = $dateYmd;
        $this->routeUrl       = $routeUrl;
        $this->payrollDay     = (int)$payrollDay;
        $this->periodYear     = $periodYear ? (int)$periodYear : null;
        $this->periodMonth    = $periodMonth ? (int)$periodMonth : null;
    }

    public function sheets(): array
    {
        // pass period year/month and let PayrollExportMandiriExcel decide final dateYmd if null
        $sheet1 = new PayrollExportMandiriExcel(
            $this->converterData,
            $this->companyAccount ?? '1710007401451',
            $this->companyAlias ?? 'BANK MANDIRI',
            $this->reference ?? '',
            $this->dateYmd,   // may be null or 'YYYYMMDD' or parsable
            $this->routeUrl,
            $this->payrollDay,
            $this->periodYear,
            $this->periodMonth
        );

        $sheet2 = new KodeBankSheet($this->bankList);

        return [
            $sheet1,
            $sheet2,
        ];
    }
}
