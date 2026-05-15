<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UserScheduleExport implements FromArray, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return array_merge(
            [['Tanggal', 'Shift']],
            array_map(fn ($d) => [$d['tanggal'], $d['shift']], $this->data)
        );
    }

    public function styles(Worksheet $sheet)
    {
        // rata tengah semua
        $sheet->getStyle('A1:B' . (count($this->data) + 1))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // header bold
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        return [];
    }
}
