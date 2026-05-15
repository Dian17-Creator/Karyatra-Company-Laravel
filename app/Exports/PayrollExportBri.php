<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class PayrollExportBri extends DefaultValueBinder implements
    FromCollection,
    WithMapping,
    WithHeadings,
    ShouldAutoSize,
    WithColumnFormatting,
    WithCustomValueBinder
{
    protected $data;
    private $fileName;

    public function __construct($data, $fileName = null)
    {
        $this->data     = $data;
        $this->fileName = $fileName ?? 'payroll_bri.xlsx';
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'REKENING',
            'NOMINAL',
            'EMAIL',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }

    /**
     * 🔥 KUNCI UTAMA: Paksa kolom A jadi STRING
     */
    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() === 'A') {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function map($row): array
    {
        $rekening = data_get($row, 'user.caccnumber')
            ?? data_get($row, 'caccnumber')
            ?? data_get($row, 'nomor_rekening')
            ?? data_get($row, 'rekening')
            ?? '';

        // 🔥 bersihin non digit (anti sisa Excel)
        $rekening = preg_replace('/\D+/', '', (string) $rekening);

        $email = data_get($row, 'user.cmailaddress')
            ?? data_get($row, 'user.email')
            ?? data_get($row, 'cmailaddress')
            ?? data_get($row, 'email')
            ?? '';

        $nominalRaw = data_get($row, 'total_gaji')
            ?? data_get($row, 'gaji_pokok')
            ?? data_get($row, 'gaji')
            ?? 0;

        $nominal = (string) intval(round(floatval($nominalRaw)));

        // if (is_string($nominalRaw)) {
        //     $digits  = preg_replace('/[^0-9\-]/', '', $nominalRaw);
        //     $nominal = $digits === '' ? '0' : $digits;
        // } else {
        //     $nominal = (string) intval(round(floatval($nominalRaw)));
        // }

        return [
            trim($rekening),
            trim($nominal),
            trim((string) $email),
        ];
    }
}
