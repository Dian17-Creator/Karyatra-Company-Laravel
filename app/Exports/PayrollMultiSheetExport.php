<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PayrollMultiSheetExport implements WithMultipleSheets
{
    protected $groupedData;
    protected $month;
    protected $year;

    public function __construct($groupedData, $month, $year)
    {
        $this->groupedData = $groupedData;
        $this->month = $month;
        $this->year = $year;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->groupedData as $sheetName => $data) {

            $rows = $data['rows'];
            $header = $data['header'];

            $sheets[] = new PayrollExport(
                $rows,
                $this->month,
                $this->year,
                $header
            );
        }

        return $sheets;
    }
}
