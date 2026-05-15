<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithTitle;

class KehadiranPerSheetExport extends KehadiranExport implements WithTitle
{
    protected $title;

    public function __construct($users, $month, $year, $departmentName, $title)
    {
        parent::__construct($users, $month, $year, $departmentName);
        $this->title = $title;
    }

    public function title(): string
    {
        return substr($this->title, 0, 31); // batas excel
    }
}
