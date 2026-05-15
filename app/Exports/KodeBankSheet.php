<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KodeBankSheet implements FromArray, WithTitle, WithEvents
{
    protected $bankList;

    /**
     * @param array $bankList each item: ['nama'=>'..','singkat'=>'..','bic'=>'..','kode'=>'..']
     */
    public function __construct(array $bankList = [])
    {
        $this->bankList = $bankList;
    }

    public function array(): array
    {
        $rows = [];

        // header
        $rows[] = [
            'NAMA PESERTA',
            'NAMA SINGKAT',
            'BIC PESERTA',
            'SANDI KLIRING KANTOR PUSAT'
        ];

        // data rows
        foreach ($this->bankList as $b) {
            $rows[] = [
                $b['nama'] ?? '',
                $b['singkat'] ?? '',
                $b['bic'] ?? '',
                $b['kode'] ?? ''
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Kode Bank Indonesia';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = (int) $sheet->getHighestRow();
                if ($lastRow < 1) {
                    $lastRow = 1;
                }
                $rangeAll = "A1:D{$lastRow}";

                // style header row (row 1): bold + yellow fill + center vertically
                $sheet->getStyle('A1:D1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFF00'],
                    ],
                ]);
                $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                                           ->setVertical(Alignment::VERTICAL_CENTER);

                // Align all data left & vertically center
                $sheet->getStyle($rangeAll)->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                      ->setVertical(Alignment::VERTICAL_CENTER);

                // Apply border to whole table (thin grid)
                $sheet->getStyle($rangeAll)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Auto size columns A..D
                foreach (range('A', 'D') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Optional: freeze header
                $sheet->freezePane('A2');
            }
        ];
    }
}
