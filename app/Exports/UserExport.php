<?php

namespace App\Exports;

use App\Models\muser;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize
{
    protected $users;

    public function __construct()
    {
        $this->users = muser::with([
            'department',
            'rekening'
        ])->orderBy('cname')->get();
    }

    /**
     * =====================
     * DATA
     * =====================
     */
    public function collection()
    {
        return $this->users->map(function ($user) {

            // Nomor rekening
            $rekening = $user->caccnumber
                ? preg_replace('/\s+/', '', $user->caccnumber)
                : ($user->rekening->nomor_rekening ?? '-');

            // Bank
            $bankUser = trim((string) ($user->bank ?? ''));
            $bankRel  = trim((string) ($user->rekening->bank ?? ''));
            $bankName = $bankUser !== ''
                ? $bankUser
                : ($bankRel !== '' ? $bankRel : '-');

            // Role
            $role =
                $user->fhrd ? 'HRD' :
                ($user->fsuper ? 'Supervisor' :
                ($user->fadmin ? 'Captain' :
                ($user->fsenior ? 'Senior Crew' : 'Crew')));

            return [
                'Username'       => $user->cemail,
                'Gmail'          => $user->cmailaddress ?? '-',
                'No Telepon'     => $user->cphone ?? '-',
                'Nomor KTP'      => $user->cktp ?? '-',
                'Nomor Rekening' => $rekening ?: '-',
                'Bank'           => $bankName ?: '-',
                'Nama'           => $user->cname,
                'Nama Lengkap'   => $user->cfullname ?? '-',
                'Finger ID'      => $user->finger_id ?? '-',
                'Tanggal Masuk'  => $user->dtanggalmasuk
                    ? Carbon::parse($user->dtanggalmasuk)->format('d M Y')
                    : '-',
                'Cabang'         => $user->department->cname ?? '-',
                'Role'           => $role,
                'Status'         => $user->factive ? 'Aktif' : 'Nonaktif',
            ];
        });
    }

    /**
     * =====================
     * HEADER
     * =====================
     */
    public function headings(): array
    {
        return [
            'Username',
            'Gmail',
            'No Telepon',
            'Nomor KTP',
            'Nomor Rekening',
            'Bank',
            'Nama',
            'Nama Lengkap',
            'Finger ID',
            'Tanggal Masuk',
            'Cabang',
            'Role',
            'Status',
        ];
    }

    /**
     * =====================
     * STYLE
     * =====================
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        // ===== HEADER STYLE =====
        $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E7D32'], // Hijau
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical'   => 'center',
            ],
        ]);

        // tinggi header
        $sheet->getRowDimension(1)->setRowHeight(26);

        // ===== BODY HEIGHT =====
        foreach (range(2, $highestRow) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(20);
        }

        // ===== ANGKA RATA TENGAH =====
        $sheet->getStyle("C2:C{$highestRow}")->getAlignment()->setHorizontal('center'); // No Telp
        $sheet->getStyle("D2:D{$highestRow}")->getAlignment()->setHorizontal('center'); // KTP
        $sheet->getStyle("E2:E{$highestRow}")->getAlignment()->setHorizontal('center'); // Rekening
        $sheet->getStyle("I2:I{$highestRow}")->getAlignment()->setHorizontal('center'); // Finger ID

        return [];
    }
}
