<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollBCASheet implements FromCollection, WithHeadings, WithEvents
{
    protected $data;
    protected $month;
    protected $year;
    protected $payrollDate; // Carbon instance
    protected $departmentId; // optional prefix (string)

    /**
     * $payrollDate can be: null | Carbon | string (parsable)
     * $departmentId can be: null | numeric|string (nid)
     */
    public function __construct($data, $month, $year, $payrollDate = null, $departmentId = null)
    {
        $this->data  = $data;
        $this->month = (int)$month;
        $this->year  = (int)$year;
        $this->departmentId = $departmentId !== null ? (string)$departmentId : null;

        // default payroll date -> use the chosen period (day 5) instead of now()
        if ($payrollDate instanceof Carbon) {
            $this->payrollDate = $payrollDate->copy();
        } elseif ($payrollDate) {
            try {
                $this->payrollDate = Carbon::parse($payrollDate);
            } catch (\Exception $e) {
                $this->payrollDate = Carbon::create($this->year, $this->month, 5);
            }
        } else {
            $this->payrollDate = Carbon::create($this->year, $this->month, 5);
        }
    }

    public function collection()
    {
        $rows = $this->data instanceof \Illuminate\Support\Collection
            ? $this->data->values()
            : collect($this->data)->values();

        // Use payrollDate to build Transaction ID prefix.
        // Format dmY ensures chosen payroll date is used.
        $dateForTxn = $this->payrollDate->format('dmY');

        // We'll maintain per-row index for sequence
        $mapped = $rows->map(function ($row, $index) use ($dateForTxn) {
            $no = $index + 1;

            // determine department to use for this row:
            // priority: explicit export-level departmentId (if provided at export time),
            // otherwise per-row user->niddept (if available), otherwise none.
            $rowDept = null;
            if (!empty($this->departmentId)) {
                // export forced to a department (user chose single dept in modal)
                $rowDept = $this->departmentId;
            } else {
                // try per-row user niddept if present
                $rowDept = $row->user->niddept ?? ($row['user']['niddept'] ?? null);
            }

            if (!empty($rowDept) || $rowDept === '0' || $rowDept === 0) {
                // dept present -> use 2-digit sequence (01,02)
                $seq = str_pad($no, 2, '0', STR_PAD_LEFT);
                $transactionId = "{$dateForTxn}-{$rowDept}{$seq}";
            } else {
                // no dept -> keep old 3-digit sequence (001,002)
                $seq = str_pad($no, 3, '0', STR_PAD_LEFT);
                $transactionId = "{$dateForTxn}-{$seq}";
            }

            // Credited account: try user->rekening->nomor_rekening -> fallback user.caccnumber
            $creditedAccount = $row->user->rekening->nomor_rekening ?? ($row->user->caccnumber ?? ($row['user']['caccnumber'] ?? ''));

            // Receiver name: uppercase fullname or name
            $receiverName = strtoupper($row->user->cfullname ?? ($row->user->cname ?? ($row['user']['cfullname'] ?? ($row['user']['cname'] ?? ''))));

            $amount = (int) round($row->total_gaji ?? 0);

            return [
                'No'                        => $no,
                'Transaction ID'            => $transactionId,
                'Transfer Type'             => 'BCA',
                'Beneficiary ID'            => '',
                'Credited Account'          => $creditedAccount,
                'Receiver Name'             => $receiverName,
                'Amount'                    => $amount,
                'NIP'                       => $row->user->nip ?? ($row['user']['nip'] ?? ''),
                'Remark'                    => 'Gaji',
                'Beneficiary email address' => '',
                'Receiver Swift Code'       => '',
                'Receiver Cust Type'        => '',
                'Receiver Cust Residence'   => '',
            ];
        });

        // ensure returns a Collection (FromCollection expects a collection)
        return collect($mapped->values());
    }

    public function headings(): array
    {
        return [
            'No',
            'Transaction ID',
            'Transfer Type',
            'Beneficiary ID',
            'Credited Account',
            'Receiver Name',
            'Amount',
            'NIP',
            'Remark',
            'Beneficiary email address',
            'Receiver Swift Code',
            'Receiver Cust Type',
            'Receiver Cust Residence',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $header = 'A1:M1';

                // Header style
                $event->sheet->getStyle($header)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => 'FFFFFF'],
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'color' => ['rgb' => '000000'],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical'   => 'center',
                    ],
                ]);

                // Get last row
                $last = $event->sheet->getDelegate()->getHighestRow();

                // Default body center
                $event->sheet->getStyle("A2:M{$last}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical'   => 'center',
                    ]
                ]);

                // OVERRIDE: some left-aligned cols
                $event->sheet->getStyle("C2:C{$last}")->getAlignment()->setHorizontal('left'); // Transfer Type
                $event->sheet->getStyle("F2:F{$last}")->getAlignment()->setHorizontal('left'); // Receiver Name
                $event->sheet->getStyle("J2:J{$last}")->getAlignment()->setHorizontal('left'); // beneficiary email

                // Auto size
                foreach (range('A', 'M') as $col) {
                    $event->sheet->getDelegate()->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }
}
