<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollLegendSheet implements FromArray, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Legend';
    }
    public function array(): array
    {
        return [
            ['Column', 'Keterangan', 'Length (max karakter)'],
            ['No', 'Nomor baris', '-'],
            ['Transaction ID', 'ID Transaksi harus unik, tidak boleh ada yang sama dalam waktu 3 bulan', '18'],
            ['Transfer Type', 'Jenis layanan transfer', '3'],
            ['Beneficiary ID', 'Khusus fitur Designated Account, wajib diisi', '70'],
            ['Credited Acc.', 'Nomor rekening tujuan transaksi', '34'],
            ['Receiver Name', 'Nama penerima tujuan transaksi', '70'],
            ['Amount', 'Nominal transaksi dengan format 2 angka decimal', '13 + 2 decimal'],
            ['NIP', 'Nomor Induk Karyawan / Pegawai', '18'],
            ['Remark', 'Keterangan mutasi', '18'],
            ['Beneficiary Email', 'Email notifikasi, gunakan koma (,)', '300'],
            ['Receiver Bank Cd', 'SWIFT Code bank tujuan', '11'],
            ['Receiver Cust Type', 'Jenis tipe nasabah', '1'],
            ['Receiver Cust Residen', 'Jenis residen nasabah', '1'],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:C1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '000000']],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                for ($i = 2; $i <= 14; $i++) {
                    $sheet->getStyle("A{$i}:C{$i}")->applyFromArray([
                        'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'F2F2F2']],
                    ]);
                }

                $sheet->getStyle('A1:C14')->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                ]);

                foreach (['A', 'B', 'C'] as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->mergeCells('E1:F1');
                $sheet->setCellValue('E1', 'Transfer Type');
                $sheet->getStyle('E1:F1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '000000']],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                $sheet->fromArray([
                    ['BCA', 'Transfer sesama BCA'],
                    ['LLG', 'Transfer Bank lain dalam negeri dengan LLG'],
                    ['RTG', 'Transfer Bank lain dalam negeri dengan RTGS'],
                ], null, 'E2');

                $sheet->getStyle('E2:F4')->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'F2F2F2']],
                    'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                ]);

                $sheet->mergeCells('E6:F6');
                $sheet->setCellValue('E6', 'Receiver Cust Type');
                $sheet->getStyle('E6:F6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '000000']],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                $sheet->fromArray([
                    ['1', 'Perorangan'],
                    ['2', 'Perusahaan'],
                    ['3', 'Pemerintah'],
                ], null, 'E7');

                $sheet->getStyle('E7:F9')->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'F2F2F2']],
                    'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                ]);

                $sheet->mergeCells('E11:F11');
                $sheet->setCellValue('E11', 'Receiver Cust Residence');
                $sheet->getStyle('E11:F11')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '000000']],
                    'alignment' => ['horizontal' => 'center'],
                ]);

                $sheet->fromArray([
                    ['1', 'Residence / Penduduk'],
                    ['2', 'Non Residence / Bukan Penduduk'],
                ], null, 'E12');

                $sheet->getStyle('E12:F13')->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'F2F2F2']],
                    'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                ]);

                foreach (['E', 'F'] as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }
}
