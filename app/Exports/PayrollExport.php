<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected $data;
    protected $month;
    protected $year;
    protected $departmentName; // optional

    public function title(): string
    {
        if (!$this->departmentName) {
            return 'Sheet';
        }

        $name = strtoupper($this->departmentName);

        // hapus "MATAHATI CAFE"
        $name = str_replace('MATAHATI CAFE', '', $name);

        // rapikan spasi
        $name = trim($name);

        // format proper case
        $name = ucwords(strtolower($name));

        // batas maksimal excel
        return substr($name, 0, 32);
    }

    public function __construct($data, $month, $year, $departmentName = null)
    {
        $this->data = $data;
        $this->month = (int)$month;
        $this->year = (int)$year;
        $this->departmentName = $departmentName ? trim($departmentName) : null;
    }

    private function getMonthName()
    {
        return strtoupper(date("M", mktime(0, 0, 0, $this->month ?: 1, 1)));
    }

    public function headings(): array
    {
        // Line 2: if departmentName provided show that, otherwise fallback to company name
        $line2 = $this->departmentName
            ? strtoupper($this->departmentName)
            : "CENTRAL KITCHEN MATAHATI CAFE";

        return [
            ["REPORT GAJI KARYAWAN"],
            [$line2],
            [$this->getMonthName() . "-" . substr((string)$this->year, -2)],
            [
                "No.", "Nama", "Jabatan", "Jumlah Masuk", "Gaji", "Gaji Pokok",
                "Tunjangan Makan", "Tunjangan Jabatan", "Tunjangan Transportasi",
                "Tunjangan Luar Kota", "Tunjangan Masa Kerja", "Tunjangan Backup",
                "Gaji Lembur", "Bonus Kehadiran", "Tabungan diambil",
                "Potongan Lain", "Potongan Tabungan",
                "Total Gaji", "Note", "Keterangan Absensi"
            ],
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;

        // totals accumulator
        $totals = [
            'jumlah_masuk' => 0,
            'gaji' => 0,
            'gaji_pokok' => 0,
            'tunjangan_makan' => 0,
            'tunjangan_jabatan' => 0,
            'tunjangan_transport' => 0,
            'tunjangan_luar_kota' => 0,
            'tunjangan_masa_kerja' => 0,
            'tunjangan_backup' => 0,
            'gaji_lembur' => 0,
            'bonus_kehadiran' => 0,
            'tabungan_diambil' => 0,
            'potongan_lain' => 0,
            'potongan_tabungan' => 0,
            'total_gaji' => 0,
        ];

        foreach ($this->data as $item) {
            // safe access user fields (support Eloquent model or array/stdClass)
            $user = $item->user ?? ($item['user'] ?? null);
            $nama = $user->cfullname ?? $user->cname ?? ($user['cfullname'] ?? ($user['cname'] ?? ''));
            $jabatan = $item->jabatan ?? ($user->jabatan ?? '');

            $jumlah_masuk = (float) ($item->jumlah_masuk ?? 0);
            // for 'Gaji' field keep original logic: if jenis harian maybe use gaji_harian, but keep as provided
            $gaji = (float) ($item->gaji_harian ?? $item->gaji ?? 0);
            $gaji_pokok = (float) ($item->gaji_pokok ?? 0);

            $t_makan = (float) ($item->tunjangan_makan ?? 0);
            $t_jabatan = (float) ($item->tunjangan_jabatan ?? 0);
            $t_transport = (float) ($item->tunjangan_transport ?? 0);
            $t_luarkota = (float) ($item->tunjangan_luar_kota ?? 0);
            $t_masakerja = (float) ($item->tunjangan_masa_kerja ?? 0);
            $t_backup = (float) ($item->tunjangan_backup ?? 0);

            $gaji_lembur = (float) ($item->gaji_lembur ?? 0);
            $bonus_kehadiran = (float) ($item->bonus_kehadiran ?? 0);

            $tabungan_diambil = (float) ($item->tabungan_diambil ?? 0);
            $potongan_lain = (float) ($item->potongan_lain ?? 0);
            $potongan_tabungan = (float) ($item->potongan_tabungan ?? 0);
            $total_gaji = (float) ($item->total_gaji ?? 0);

            $rows[] = [
                $no++,
                $nama,
                $jabatan,
                $jumlah_masuk,
                $gaji,
                $gaji_pokok,
                $t_makan,
                $t_jabatan,
                $t_transport,
                $t_luarkota,
                $t_masakerja,
                $t_backup,
                $gaji_lembur,
                $bonus_kehadiran,
                $tabungan_diambil,
                $potongan_lain,
                $potongan_tabungan,
                $total_gaji,
                $item->note ?? ($item['note'] ?? ''),
                $item->keterangan_absensi ?? ($item['keterangan_absensi'] ?? ''),

            ];

            // accumulate totals
            $totals['jumlah_masuk'] += $jumlah_masuk;
            $totals['gaji'] += $gaji;
            $totals['gaji_pokok'] += $gaji_pokok;
            $totals['tunjangan_makan'] += $t_makan;
            $totals['tunjangan_jabatan'] += $t_jabatan;
            $totals['tunjangan_transport'] += $t_transport;
            $totals['tunjangan_luar_kota'] += $t_luarkota;
            $totals['tunjangan_masa_kerja'] += $t_masakerja;
            $totals['tunjangan_backup'] += $t_backup;
            $totals['gaji_lembur'] += $gaji_lembur;
            $totals['bonus_kehadiran'] += $bonus_kehadiran;
            $totals['tabungan_diambil'] += $tabungan_diambil;
            $totals['potongan_lain'] += $potongan_lain;
            $totals['potongan_tabungan'] += $potongan_tabungan;
            $totals['total_gaji'] += $total_gaji;
        }

        // append totals row (placing total_gaji in the 'Total Gaji' column)
        $rows[] = [
            '', // No.
            'TOTAL', // Nama label
            '', // Jabatan
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
            $totals['total_gaji'],
            '',
            ''
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $dataCount = count($this->data);
        $lastRow = 4 + $dataCount + 1; // header row (4) + data rows + totals row

        // Merge title rows across A..R
        $sheet->mergeCells('A1:T1');
        $sheet->mergeCells('A2:T2');
        $sheet->mergeCells('A3:T3');

        $sheet->getStyle('A1:R3')->applyFromArray([
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'font'      => ['bold' => true, 'size' => 14]
        ]);

        // Header row styles (row 4)
        $sheet->getStyle('A4:R4')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'FFB6C1']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin']
            ]
        ]);

        $sheet->getStyle("S4")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'FFB6C1']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => 'thin']
            ]
        ]);

        $sheet->getStyle("P4:P{$lastRow}")
            ->getBorders()
            ->getLeft()
            ->setBorderStyle(Border::BORDER_NONE);

        // Align data area
        $sheet->getStyle("A4:T{$lastRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("A4:T{$lastRow}")->getAlignment()->setVertical('center');

        // Left align some columns
        $sheet->getStyle("B5:B{$lastRow}")->getAlignment()->setHorizontal('left');
        $sheet->getStyle("C5:C{$lastRow}")->getAlignment()->setHorizontal('left');
        $sheet->getStyle("Q5:Q{$lastRow}")->getAlignment()->setHorizontal('left');

        // Borders
        $sheet->getStyle("A4:T{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => 'thin']]
        ]);

        // Color columns
        $sheet->getStyle("N4:N{$lastRow}")->getFill()->setFillType('solid')->getStartColor()->setRGB('FFFACD');
        $sheet->getStyle("O4:O{$lastRow}")->getFill()->setFillType('solid')->getStartColor()->setRGB('FFFF66');
        $sheet->getStyle("P4:P{$lastRow}")->getFill()->setFillType('solid')->getStartColor()->setRGB('CCFFCC');
        $sheet->getStyle("P4:P{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("Q4:Q{$lastRow}")->getFill()->setFillType('solid')->getStartColor()->setRGB('ADD8E6');
        $sheet->getStyle("R4:R{$lastRow}")->getFill()->setFillType('solid')->getStartColor()->setRGB('FF7F7F');
        $sheet->getStyle("T4:T{$lastRow}")->getFill()->setFillType('solid')->getStartColor()->setRGB('FFFF33');

        // Day format
        $sheet->getStyle("D5:D{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0');

        $sheet->getStyle("Q5:Q{$lastRow}")
            ->getAlignment()
            ->setHorizontal('center');

        // Currency format
        $moneyCols = ['E','F','G','H','I','J','K','L','M','N','O','P','Q'];

        foreach ($moneyCols as $col) {
            $sheet->getStyle("{$col}5:{$col}{$lastRow}")
                  ->getNumberFormat()
                  ->setFormatCode('#,##0.00');
        }

        // Totals row styling and merge
        $totRow = $lastRow;
        $sheet->mergeCells("A{$totRow}:O{$totRow}");
        $sheet->getStyle("A{$totRow}:T{$totRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'FFFFE0']
            ],
            'borders' => ['allBorders' => ['borderStyle' => 'thin']]
        ]);
        $sheet->getStyle("A{$totRow}")->getAlignment()->setHorizontal('left');
        $sheet->getStyle("P{$totRow}")->getNumberFormat()->setFormatCode('#,##0.00');

        return [];
    }
}
