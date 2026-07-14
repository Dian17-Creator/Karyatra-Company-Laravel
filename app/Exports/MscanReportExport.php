<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * =====================================================
 * EXPORT INDUK → MULTI SHEET PER DEPARTEMEN
 * =====================================================
 */
class MscanReportExport implements WithMultipleSheets
{
    protected string $start;
    protected string $end;
    protected $user;

    public function __construct(string $start, string $end, $user)
    {
        $this->start = $start;
        $this->end   = $end;
        $this->user  = $user;
    }

    public function sheets(): array
    {
        $sheets = [];

        $departments = DB::table('mdepartment')
            ->orderBy('cname')
            ->get();

        foreach ($departments as $dept) {
            $sheets[] = new MscanReportSheet(
                $this->start,
                $this->end,
                (int) $dept->nid,
                $this->user
            );
        }

        return $sheets;
    }
}

/**
 * =====================================================
 * SHEET PER DEPARTEMEN
 * =====================================================
 */
class MscanReportSheet implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    WithEvents,
    WithCustomStartCell
{
    protected string $start;
    protected string $end;
    protected int $dept;
    protected $user;

    public function __construct(string $start, string $end, int $dept, $user)
    {
        $this->start = $start;
        $this->end   = $end;
        $this->dept  = $dept;
        $this->user  = $user;
    }

    /* =========================
     * POSISI HEADER TABEL
     * ========================= */
    public function startCell(): string
    {
        return 'A4';
    }

    /* =========================
     * NAMA SHEET
     * ========================= */
    public function title(): string
    {
        return DB::table('mdepartment')
            ->where('nid', $this->dept)
            ->value('cname') ?? 'Departemen';
    }

    /* =========================
     * DATA
     * ========================= */
    public function collection()
    {
        $sql = "
        SELECT
            scan.nuserid,
            scan.dscanned,
            scan.source,
            sched.dstart,
            sched.dend,
            sched.dstart2,
            sched.dend2,
            sched.cschedname,
            u.cname,
            u.niddept
        FROM (
            SELECT nuserid, dscanned, (CASE WHEN creason IS NOT NULL AND creason != '' THEN 'manual' ELSE 'scan' END) AS source FROM mscan
            UNION ALL
            SELECT nuserid, dscanned, LOWER(status) AS source FROM mscan_manual
            UNION ALL
            SELECT nuserId AS nuserid, dscanned, 'face' AS source FROM mface_scan
        ) scan
        LEFT JOIN tuserschedule sched
            ON sched.nuserid = scan.nuserid
            AND DATE(scan.dscanned) = DATE(sched.dwork)
        LEFT JOIN muser u
            ON u.nid = scan.nuserid
        WHERE DATE(scan.dscanned) BETWEEN ? AND ?
        AND u.niddept = ?
        ORDER BY scan.nuserid, scan.dscanned
    ";

        $rows = DB::select($sql, [
            $this->start,
            $this->end,
            $this->dept
        ]);

        $grouped = collect($rows)->groupBy(function ($row) {
            return $row->nuserid . '_' . date('Y-m-d', strtotime($row->dscanned));
        });

        $result = [];

        foreach ($grouped as $scans) {

            $first = $scans->first();

            /* =========================
             * NORMAL SHIFT
             * ========================= */
            if (empty($first->dstart2)) {

                $in = $scans->min('dscanned');
                $out = $scans->max('dscanned');

                $late = 0;
                $overtime = 0;

                if (!empty($first->dstart) && !empty($in)) {
                    $scheduleStart = date('Y-m-d', strtotime($in)) . ' ' . $first->dstart;

                    $late = max(
                        0,
                        floor((strtotime($in) - strtotime($scheduleStart)) / 60)
                    );
                }

                if (!empty($first->dend) && !empty($out)) {
                    $scheduleEnd = date('Y-m-d', strtotime($out)) . ' ' . $first->dend;

                    $overtime = max(
                        0,
                        floor((strtotime($out) - strtotime($scheduleEnd)) / 60)
                    );
                }

                $inScan = $scans->firstWhere('dscanned', $in);
                $outScan = $scans->firstWhere('dscanned', $out);

                $result[] = [
                    'cname' => $first->cname,
                    'date' => date('Y-m-d', strtotime($in)),
                    'cschedname' => $first->cschedname,

                    'dstart' => $first->dstart,
                    'in_time' => date('H:i:s', strtotime($in)),
                    'in_source' => $inScan ? $inScan->source : null,
                    'dend' => $first->dend,
                    'out_time' => date('H:i:s', strtotime($out)),
                    'out_source' => $outScan ? $outScan->source : null,

                    'dstart2' => null,
                    'in_time2' => null,
                    'in_source2' => null,
                    'dend2' => null,
                    'out_time2' => null,
                    'out_source2' => null,

                    'late_minutes' => $late,
                    'overtime_minutes' => $overtime,

                    'alasan' => null,
                ];
            } else {

                /* =========================
                 * SPLIT SHIFT
                 * ========================= */

                $shift1 = [];
                $shift2 = [];

                $pivot = strtotime(date('Y-m-d', strtotime($first->dscanned)) . ' ' . $first->dstart2) - 3600;

                foreach ($scans as $scan) {

                    $scanTime = strtotime($scan->dscanned);

                    if ($scanTime < $pivot) {
                        $shift1[] = $scan;
                    } else {
                        $shift2[] = $scan;
                    }
                }

                $in1Scan = null;
                $out1Scan = null;
                if (!empty($shift1)) {
                    $in1Time = null;
                    $out1Time = null;
                    foreach ($shift1 as $s) {
                        $sTime = strtotime($s->dscanned);
                        if ($in1Time === null || $sTime < $in1Time) {
                            $in1Time = $sTime;
                            $in1Scan = $s;
                        }
                        if ($out1Time === null || $sTime > $out1Time) {
                            $out1Time = $sTime;
                            $out1Scan = $s;
                        }
                    }
                }

                $in2Scan = null;
                $out2Scan = null;
                if (!empty($shift2)) {
                    $in2Time = null;
                    $out2Time = null;
                    foreach ($shift2 as $s) {
                        $sTime = strtotime($s->dscanned);
                        if ($in2Time === null || $sTime < $in2Time) {
                            $in2Time = $sTime;
                            $in2Scan = $s;
                        }
                        if ($out2Time === null || $sTime > $out2Time) {
                            $out2Time = $sTime;
                            $out2Scan = $s;
                        }
                    }
                }

                $late = 0;
                $overtime = 0;

                /* =====================
                   LATE SHIFT 1
                =====================*/
                if ($first->dstart && $in1Scan) {

                    $scheduleStart = date('Y-m-d', strtotime($in1Scan->dscanned)) . ' ' . $first->dstart;

                    $late = max(
                        0,
                        floor((strtotime($in1Scan->dscanned) - strtotime($scheduleStart)) / 60)
                    );
                }

                /* =====================
                   OVERTIME SHIFT 1
                =====================*/
                if ($first->dend && $out1Scan) {

                    $scheduleEnd1 = date('Y-m-d', strtotime($out1Scan->dscanned)) . ' ' . $first->dend;

                    $overtime += max(
                        0,
                        floor((strtotime($out1Scan->dscanned) - strtotime($scheduleEnd1)) / 60)
                    );
                }

                /* =====================
                   OVERTIME SHIFT 2
                =====================*/
                if ($first->dend2 && $out2Scan) {

                    $scheduleEnd2 = date('Y-m-d', strtotime($out2Scan->dscanned)) . ' ' . $first->dend2;

                    $overtime += max(
                        0,
                        floor((strtotime($out2Scan->dscanned) - strtotime($scheduleEnd2)) / 60)
                    );
                }

                $result[] = [
                    'cname' => $first->cname,
                    'date' => date('Y-m-d', strtotime($first->dscanned)),
                    'cschedname' => $first->cschedname,

                    'dstart' => $first->dstart,
                    'in_time' => $in1Scan ? date('H:i:s', strtotime($in1Scan->dscanned)) : null,
                    'in_source' => $in1Scan ? $in1Scan->source : null,
                    'dend' => $first->dend,
                    'out_time' => $out1Scan ? date('H:i:s', strtotime($out1Scan->dscanned)) : null,
                    'out_source' => $out1Scan ? $out1Scan->source : null,

                    'dstart2' => $first->dstart2,
                    'in_time2' => $in2Scan ? date('H:i:s', strtotime($in2Scan->dscanned)) : null,
                    'in_source2' => $in2Scan ? $in2Scan->source : null,
                    'dend2' => $first->dend2,
                    'out_time2' => $out2Scan ? date('H:i:s', strtotime($out2Scan->dscanned)) : null,
                    'out_source2' => $out2Scan ? $out2Scan->source : null,

                    'late_minutes' => $late,
                    'overtime_minutes' => $overtime,
                    'alasan' => null,
                ];
            }
        }

        /* =========================
 * DATA IZIN
 * ========================= */

        $izin = DB::table('mrequest')
            ->join('muser', 'muser.nid', '=', 'mrequest.nuserid')
            ->whereBetween('mrequest.drequest', [$this->start, $this->end])
            ->where('muser.niddept', $this->dept)
            ->select(
                'muser.cname',
                'mrequest.drequest as date',
                'mrequest.creason as alasan'
            )
            ->get();

        foreach ($izin as $i) {

            $result[] = [
                'cname' => $i->cname,
                'date' => $i->date,
                'cschedname' => 'IZIN',

                'dstart' => null,
                'in_time' => null,
                'in_source' => null,
                'dend' => null,
                'out_time' => null,
                'out_source' => null,

                'dstart2' => null,
                'in_time2' => null,
                'in_source2' => null,
                'dend2' => null,
                'out_time2' => null,
                'out_source2' => null,

                'late_minutes' => 0,
                'overtime_minutes' => 0,
                'alasan' => $i->alasan
            ];
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Tanggal',
            'Shift',

            'Jam Masuk',
            'Jam Checkin',
            'Jam Keluar',
            'Jam Checkout',

            'Jam Masuk (Split)',
            'Jam Checkin (Split)',
            'Jam Keluar (Split)',
            'Jam Checkout (Split)',

            'Keterlambatan (Menit)',
            'Lembur (Menit)',
            'Alasan',
        ];
    }

    private function formatTimeWithSource($time, $source)
    {
        if (empty($time) || $time === '-') {
            return '-';
        }
        if (empty($source)) {
            return $time;
        }

        $letter = '';
        switch ($source) {
            case 'face':
                $letter = 'F';
                break;
            case 'manual':
                $letter = 'M';
                break;
            case 'forgot':
                $letter = 'L';
                break;
            case 'scan':
                $letter = 'S';
                break;
            default:
                $letter = strtoupper(substr($source, 0, 1));
                break;
        }

        return $time . ' (' . $letter . ')';
    }

    public function map($row): array
    {
        return [
            $row['cname'],
            $row['date'],
            $row['cschedname'] ?? '-',

            $row['dstart'] ?? '-',
            $this->formatTimeWithSource($row['in_time'] ?? null, $row['in_source'] ?? null),
            $row['dend'] ?? '-',
            $this->formatTimeWithSource($row['out_time'] ?? null, $row['out_source'] ?? null),

            $row['dstart2'] ?? '-',
            $this->formatTimeWithSource($row['in_time2'] ?? null, $row['in_source2'] ?? null),
            $row['dend2'] ?? '-',
            $this->formatTimeWithSource($row['out_time2'] ?? null, $row['out_source2'] ?? null),

            $row['late_minutes'] > 0 ? $row['late_minutes'] . ' menit' : '-',
            $row['overtime_minutes'] > 0 ? $row['overtime_minutes'] . ' menit' : '-',

            $row['alasan'] ?? '-',
        ];
    }

    /* =========================
     * TITLE + BORDER TABLE
     * ========================= */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $deptRaw = DB::table('mdepartment')
                    ->where('nid', $this->dept)
                    ->value('cname');

                $deptName = strtoupper($deptRaw) === 'CK'
                    ? 'CENTRAL KITCHEN'
                    : strtoupper($deptRaw);

                if ($this->start === $this->end) {
                    $tanggal = Carbon::parse($this->start)
                        ->translatedFormat('l, d F Y');
                } else {
                    $tanggalStart = Carbon::parse($this->start)->translatedFormat('d F Y');
                    $tanggalEnd = Carbon::parse($this->end)->translatedFormat('d F Y');
                    $tanggal = $tanggalStart . ' - ' . $tanggalEnd;
                }

                // TITLE
                $sheet->mergeCells('A1:N1');
                $sheet->setCellValue('A1', 'REPORT ABSENSI KARYAWAN');

                $sheet->mergeCells('A2:N2');
                $sheet->setCellValue('A2', $deptName . ' MATAHATI CAFE');

                $sheet->mergeCells('A3:N3');
                $sheet->setCellValue('A3', $tanggal);

                $sheet->getStyle('A1:N3')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);

                $lastRow = $sheet->getHighestRow();

                // BORDER
                $sheet->getStyle("A4:N{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // FORCE EXCEL RENDER COLUMN
                foreach (range('A', 'N') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            4 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            'A4:N4' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            'B:N' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 13,
            'B' => 11,
            'C' => 20,
            'D' => 13,
            'E' => 13,
            'F' => 13,
            'G' => 13,

            'H' => 17,
            'I' => 20,
            'J' => 17,
            'K' => 20,

            'L' => 21,
            'M' => 15,
            'N' => 25,
        ];
    }
}
