<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KehadiranExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $users;
    protected $month;
    protected $year;
    protected $departmentName;

    public function __construct($users, $month, $year, $departmentName = null)
    {
        $this->users = $users;
        $this->month = (int)$month;
        $this->year = (int)$year;
        $this->departmentName = $departmentName;
    }

    private function getMonthName()
    {
        return strtoupper(date("M", mktime(0, 0, 0, $this->month, 1)));
    }

    public function headings(): array
    {
        return [
            ["REPORT KEHADIRAN"],
            [$this->departmentName ?? "MATAHATI CAFE"],
            [$this->getMonthName() . "-" . substr((string)$this->year, -2)],
            [
                "No",
                "Nama",
                "Jabatan",
                "Jumlah Sakit",
                "Jumlah Izin",
                "Jumlah Alfa",
                "Jumlah Masuk"
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;

        foreach ($this->users as $user) {

            $att = $this->getAttendanceSummary(
                $user->nid,
                $this->year,
                $this->month
            );

            // 🔥 ambil dari salary (SAMA seperti payroll)
            $salary = $user->salary->first();
            $jabatan = $salary->jabatan ?? '-';

            $rows[] = [
                $no++,
                $user->cfullname ?? $user->cname ?? '-',
                $jabatan,
                $att['S'] > 0 ? $att['S'] : '-',
                $att['I'] > 0 ? $att['I'] : '-',
                $att['A'] > 0 ? $att['A'] : '-',
                $att['H'] > 0 ? $att['H'] : '-',
            ];
        }

        return $rows;
    }

    // 🔥 LOGIC ABSENSI (AMBIL DARI JOB)
    private function getAttendanceSummary($userId, $year, $month)
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // HADIR
        $scanDates = DB::select("
            SELECT DISTINCT DATE(dscanned) AS dt FROM (
                SELECT dscanned FROM mscan WHERE nuserid = ?
                UNION
                SELECT dscanned FROM mscan_manual WHERE nuserid = ?
                UNION
                SELECT dscanned FROM mface_scan WHERE nuserid = ?
            ) x
            WHERE DATE(dscanned) BETWEEN ? AND ?
        ", [$userId, $userId, $userId, $periodStart, $periodEnd]);

        $hadirMap = [];
        foreach ($scanDates as $row) {
            $hadirMap[$row->dt] = true;
        }

        // IZIN / SAKIT
        $izinRows = DB::table('mrequest')
            ->select(DB::raw('DATE(drequest) as izin_date'), 'category')
            ->where('nuserid', $userId)
            ->whereIn('category', ['izin', 'sakit'])
            ->where(function ($q) {
                $q->where('cstatus', 'approved')
                  ->orWhere('chrdstat', 'approved')
                  ->orWhere('csuperstat', 'approved');
            })
            ->whereBetween('drequest', [$periodStart, $periodEnd])
            ->get();

        $izinMap = [];
        foreach ($izinRows as $r) {
            $izinMap[$r->izin_date] =
                strtolower($r->category) === 'sakit' ? 'S' : 'I';
        }

        // WORKDAYS
        $workdays = DB::table('tuserschedule')
            ->where('nuserid', $userId)
            ->whereBetween('dwork', [$periodStart, $periodEnd])
            ->pluck('dwork')
            ->toArray();

        $A = $I = $S = $H = 0;

        foreach ($workdays as $day) {
            if (isset($hadirMap[$day])) {
                $H++;
            } elseif (isset($izinMap[$day])) {
                if ($izinMap[$day] === 'I') {
                    $I++;
                }
                if ($izinMap[$day] === 'S') {
                    $S++;
                }
            } else {
                $A++;
            }
        }

        return [
            'A' => $A,
            'I' => $I,
            'S' => $S,
            'H' => $H,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->users) + 4;

        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');

        $sheet->getStyle('A1:G3')->applyFromArray([
            'alignment' => ['horizontal' => 'center'],
            'font' => ['bold' => true, 'size' => 14]
        ]);

        $sheet->getStyle('A4:G4')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => 'center'],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'FFB6C1']
            ]
        ]);

        $sheet->getStyle("A4:G{$lastRow}")
            ->getAlignment()
            ->setHorizontal('center');

        return [];
    }
}
