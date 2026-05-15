<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KehadiranMultiSheetExport implements WithMultipleSheets
{
    protected $sheets;
    protected $month;
    protected $year;

    public function __construct($sheets, $month, $year)
    {
        $this->sheets = $sheets;
        $this->month = $month;
        $this->year = $year;
    }

    public function sheets(): array
    {
        $result = [];

        foreach ($this->sheets as $sheetName => $data) {

            $result[] = new KehadiranPerSheetExport(
                $data['rows'],
                $this->month,
                $this->year,
                $data['header'],
                $sheetName
            );
        }

        return $result;
    }
}
