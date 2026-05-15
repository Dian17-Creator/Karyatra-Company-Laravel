<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PayrollExportMandiriExcel implements FromArray, WithEvents, WithTitle
{
    protected $data;
    protected $companyAccount;
    protected $companyAlias;
    protected $reference;
    protected $dateYmd;   // final string 'YYYYMMDD'
    protected $routeUrl;
    protected $payrollDay;
    protected $totalAmount = 0;
    protected $countRows = 0;

    public function title(): string
    {
        return 'Converter'; // nama sheet 1
    }

    public function __construct(
        $data,
        $companyAccount = '1710007401451',
        $companyAlias = 'BANK MANDIRI',
        $reference = '15786538',
        $dateYmd = null,
        $routeUrl = null,
        $payrollDay = 17,
        $periodYear = null,    // NEW
        $periodMonth = null    // NEW
    ) {
        $this->data = $data;
        $this->companyAccount = $companyAccount;
        $this->companyAlias = $companyAlias;
        $this->reference = $reference;
        $this->routeUrl = $routeUrl;
        $this->payrollDay = (int)$payrollDay;

        // If caller passed a real YYYYMMDD string, accept it.
        // Else try to parse/normalize; if still not possible, use provided periodYear/periodMonth + payrollDay; fallback to now.
        if ($dateYmd) {
            if ($dateYmd instanceof Carbon) {
                $dt = $dateYmd;
            } else {
                $s = trim((string)$dateYmd);
                if (preg_match('/^\d{8}$/', $s)) {
                    // string already YYYYMMDD -> parse
                    $dt = Carbon::createFromFormat('Ymd', $s);
                } else {
                    // try parse
                    try {
                        $dt = Carbon::parse($s);
                    } catch (\Exception $e) {
                        $dt = null;
                    }
                }
            }

            if ($dt) {
                $this->dateYmd = $dt->format('Ymd');
            } else {
                $this->dateYmd = null;
            }
        } else {
            $this->dateYmd = null;
        }

        // If still null, try to build from provided periodYear & periodMonth
        if (empty($this->dateYmd)) {
            if ($periodYear && $periodMonth) {
                $lastDay = Carbon::create($periodYear, $periodMonth, 1)->endOfMonth()->day;
                $day = min($this->payrollDay, $lastDay);
                $dt = Carbon::create($periodYear, $periodMonth, $day);
                $this->dateYmd = $dt->format('Ymd');
            } else {
                $this->dateYmd = Carbon::now()->format('Ymd');
            }
        }
    }

    public function array(): array
    {
        $rowsCollection = $this->data instanceof \Illuminate\Support\Collection
            ? $this->data
            : collect($this->data);

        $filtered = $rowsCollection->map(function ($row) {

            $user = $row->user;

            $acc = $user?->caccnumber;
            $name = $user?->cfullname ?? $user?->cname;

            // cari captain payroll dept
            $captain = \App\Models\muser::where('niddeptpayroll', $user->niddeptpayroll)
                        ->where('fadmin', 1)
                        ->first();

            if ($captain && $acc == $captain->caccnumber) {
                $name = $captain->cfullname ?? $captain->cname;
            }

            if (!$acc && $captain) {
                $acc = $captain->caccnumber;
                $name = $captain->cfullname ?? $captain->cname;
            }

            if (!$acc) {
                return null;
            }

            return (object)[
                'user_id' => $user->id,
                'caccnumber' => $acc,
                'cfullname' => $name,
                'amount' => (int) $row->total_gaji
            ];

        })->filter()->values();

        $unique = $filtered->values();

        $this->countRows = $unique->count();
        $this->totalAmount = $unique->sum('amount');

        $rows = [];
        $rows[] = ['Batch Upload MCM 2.0'];         // A1
        $rows[] = ['Harus / Mandatory'];           // A2
        $rows[] = ['Pilihan / Optional'];          // A3
        $rows[] = [''];                            // A4
        $rows[] = [$this->companyAlias];           // A5

        // B6 is dateYmd string, C6 companyAccount, D6 count, E6 total, F6 reference
        $rows[] = [
            'P',
            $this->dateYmd,
            $this->companyAccount,
            $this->countRows,
            $this->totalAmount,
        ];

        foreach ($unique as $row) {

            // buat 44 kolom kosong (A sampai AR)
            $line = array_fill(0, 44, '');

            $line[0]  = $row->caccnumber;   // A
            $line[1]  = $row->cfullname;    // B
            $line[5]  = 'IDR';              // F
            $line[6]  = $row->amount;       // G
            $line[7]  = 'Gaji';             // H
            $line[9]  = 'IBU';              // J
            $line[11] = 'MANDIRI';     // L
            $line[12] = 'Tulungagung';      // M

            $line[16] = 'N';                // Q
            $line[38] = 'OUR';              // AM
            $line[39] = '1';                // AN
            $line[40] = 'E';                // AO

            $rows[] = $line;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'underline' => true],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']],
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'FFFF00']],
                ]);

                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '0000FF']],
                ]);

                // write date and company account as strings to avoid excel auto-formatting
                $sheet->setCellValueExplicit('B6', $this->dateYmd, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('C6', $this->companyAccount, DataType::TYPE_STRING);

                $sheet->setCellValue('D6', (int)($this->countRows ?? 0));
                $sheet->setCellValue('E6', (int)($this->totalAmount ?? 0));
                $sheet->getStyle('E6')->getNumberFormat()->setFormatCode('#,##0');
                $sheet->setCellValueExplicit('F6', $this->reference, DataType::TYPE_STRING);

                $sheet->getStyle('A6:F6')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->getStyle('B6:C6')->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'FFFF00']],
                ]);

                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A7:A{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                for ($row = 7; $row <= $lastRow; $row++) {
                    $sheet->setCellValueExplicit(
                        "A{$row}",
                        $sheet->getCell("A{$row}")->getValue(),
                        DataType::TYPE_STRING
                    );
                }

                $sheet->getStyle("J7:J{$lastRow}")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'FFFF00']],
                ]);

                $sheet->getStyle("F7:I{$lastRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'color' => ['rgb' => 'FFFF00']
                    ],
                ]);

                $sheet->setCellValue("AR{$lastRow}", '');

                // Kolom Q (N)
                // $sheet->getStyle("Q7:Q{$lastRow}")->applyFromArray([
                //     'fill' => [
                //         'fillType' => 'solid',
                //         'color' => ['rgb' => 'FFFF00']
                //     ],
                // ]);

                // Kolom AM sampai AO (OUR 1 E)
                $sheet->getStyle("AM7:AN{$lastRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'color' => ['rgb' => 'FFFF00']
                    ],
                ]);

                $highestColumnIndex = Coordinate::columnIndexFromString('AR');

                $sheet->getColumnDimension('A')->setAutoSize(true);
                $sheet->getColumnDimension('B')->setAutoSize(true);

                for ($col = 3; $col <= $highestColumnIndex; $col++) {
                    $columnLetter = Coordinate::stringFromColumnIndex($col);
                    $currentWidth = $sheet->getColumnDimension($columnLetter)->getWidth();

                    if ($currentWidth < 15) {
                        $sheet->getColumnDimension($columnLetter)->setWidth(15);
                    }
                }

                for ($row = 7; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(20);
                }

                if ($this->routeUrl) {
                    $sheet->setCellValue('B2', '=HYPERLINK("'.$this->routeUrl.'", "Convert CSV")');

                    $sheet->getStyle('B2')->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'C0C0C0']],
                        'alignment' => ['horizontal' => 'center'],
                    ]);
                }

                $sheet->getStyle("G7:G{$lastRow}")
                      ->getNumberFormat()
                      ->setFormatCode('#,##0');
            }
        ];
    }

    public function toMandiriCsvArray(): array
    {
        $rowsCollection = $this->data instanceof \Illuminate\Support\Collection
            ? $this->data
            : collect($this->data);

        $filtered = $rowsCollection->map(function ($row) {

            $user = $row->user;

            $acc = $user?->caccnumber;
            $name = $user?->cfullname ?? $user?->cname;

            // cari captain dept
            $captain = \App\Models\muser::where('niddeptpayroll', $user->niddeptpayroll)
                        ->where('fadmin', 1)
                        ->first();

            // jika rekening sama captain → pakai nama captain
            if ($captain && $acc == $captain->caccnumber) {
                $name = $captain->cfullname ?? $captain->cname;
            }

            // jika user tidak punya rekening
            if (!$acc && $captain) {
                $acc = $captain->caccnumber;
                $name = $captain->cfullname ?? $captain->cname;
            }

            if (!$acc) {
                return null;
            }

            return [
                'acc' => $acc,
                'name' => $name,
                'amount' => (int) ($row->total_gaji ?? 0),
            ];

        })->filter()->values();

        $count = $filtered->count();
        $total = $filtered->sum('amount');

        $rows = [];

        // P row (MANDIRI format only)
        $rows[] = array_pad([
            'P',
            $this->dateYmd,
            $this->companyAccount,
            $count,
            $total,
        ], 44, '');

        foreach ($filtered as $row) {
            $rows[] = array_pad([
                $row['acc'],
                $row['name'],
                '', '', '',
                'IDR',
                $row['amount'],
                'Gaji',
                '',
                'IBU',
                '',
                'MANDIRI',
                'Tulungagung',
                '',
                '',
                '',
                'N',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'OUR',
                '1',
                'E'
            ], 44, '');
        }

        return $rows;
    }
}
