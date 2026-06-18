<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MscanReportByDeptExport;

class AttendanceController extends Controller
{
    public function index()
    {
        $authUser = Auth::user();
        $query = DB::table('mdepartment')->orderBy('cname');
        if ($authUser && $authUser->ccompany) {
            $query->where('ccompany', $authUser->ccompany);
        }
        $departments = $query->get();

        return view('attendance.index', compact('departments'));
    }

    public function getAttendanceReport(Request $request)
    {
        $startDate = $request->query('start') ?? now()->toDateString();
        $endDate   = $request->query('end') ?? $startDate;

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $filterDept = $request->query('dept');
        $deptId = $user->niddept;
        $isHRD  = $user->fhrd == 1;

        try {

            $sql = "
            SELECT
                scan.nuserid,
                scan.dscanned,
                sched.dstart,
                sched.dend,
                sched.dstart2,
                sched.dend2,
                sched.cschedname,
                u.cname,
                u.niddept
            FROM (
                SELECT nuserid, dscanned FROM mscan
                UNION ALL
                SELECT nuserid, dscanned FROM mscan_manual
                UNION ALL
                SELECT nuserId AS nuserid, dscanned FROM mface_scan
            ) scan
            LEFT JOIN tuserschedule sched
                ON sched.nuserid = scan.nuserid
                AND DATE(scan.dscanned) = DATE(sched.dwork)
            LEFT JOIN muser u
                ON u.nid = scan.nuserid
            WHERE DATE(scan.dscanned) BETWEEN ? AND ?
        ";

            $params = [$startDate, $endDate];

            if ($user && $user->ccompany) {
                $sql .= " AND u.ccompany = ? ";
                $params[] = $user->ccompany;
            }

            if (!empty($filterDept)) {
                $sql .= " AND u.niddept = ? ";
                $params[] = $filterDept;
            } elseif (!$isHRD && !empty($deptId)) {
                // user biasa hanya lihat dept sendiri
                $sql .= " AND u.niddept = ? ";
                $params[] = $deptId;
            }

            $sql .= " ORDER BY scan.nuserid, scan.dscanned";

            $rows = DB::select($sql, $params);

            $grouped = collect($rows)->groupBy(function ($row) {
                return $row->nuserid . '_' . date('Y-m-d', strtotime($row->dscanned));
            });

            $result = [];

            foreach ($grouped as $scans) {

                $first = $scans->first();

                if (!empty($first->dstart2)) {
                    $result[] = $this->calculateSplitShift($scans);
                } else {
                    $result[] = $this->calculateNormalShift($scans);
                }
            }

            $izinQuery = DB::table('mrequest')
                ->join('muser', 'muser.nid', '=', 'mrequest.nuserid')
                ->whereBetween('mrequest.drequest', [$startDate, $endDate]);

            if ($user && $user->ccompany) {
                $izinQuery->where('muser.ccompany', $user->ccompany);
            }

            if (!empty($filterDept)) {
                $izinQuery->where('muser.niddept', $filterDept);
            } elseif (!$isHRD && !empty($deptId)) {
                $izinQuery->where('muser.niddept', $deptId);
            }

            $izin = $izinQuery->select(
                'mrequest.nuserid as user_id',
                'muser.cname',
                'mrequest.drequest as date',
                'mrequest.creason as alasan',
                'muser.niddept'
            )->get();

            foreach ($izin as $i) {

                $result[] = [
                    'user_id' => $i->user_id,
                    'cname' => $i->cname,
                    'date' => $i->date,

                    'cschedname' => 'IZIN',

                    'dstart' => null,
                    'dend' => null,
                    'dstart2' => null,
                    'dend2' => null,

                    'in_time' => null,
                    'out_time' => null,
                    'in_time2' => null,
                    'out_time2' => null,

                    'alasan' => $i->alasan,
                    'type' => 'izin',

                    'niddept' => $i->niddept
                ];
            }

            usort($result, function ($a, $b) {
                return strtotime($a['date']) <=> strtotime($b['date']);
            });

            return response()->json([
                'success' => true,
                'data'    => $result,
                'total'   => count($result)
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateNormalShift($scans)
    {
        $first = $scans->first();

        $in = $scans->min('dscanned');
        $out = $scans->max('dscanned');

        return [
            'user_id' => $first->nuserid,
            'cname' => $first->cname,
            'date' => date('Y-m-d', strtotime($in)),
            'cschedname' => $first->cschedname,
            'dstart' => $first->dstart,
            'dend' => $first->dend,
            'in_time' => date('H:i:s', strtotime($in)),
            'out_time' => date('H:i:s', strtotime($out)),
            'in_time2' => null,
            'out_time2' => null,
            'niddept' => $first->niddept,
            'type' => 'attendance'
        ];
    }

    private function calculateSplitShift($scans)
    {
        $first = $scans->first();

        $shift1 = [];
        $shift2 = [];

        // titik tengah antara shift1 dan shift2
        $pivot = date('H:i:s', strtotime($first->dstart2 . ' -1 hour'));

        foreach ($scans as $scan) {

            $time = date('H:i:s', strtotime($scan->dscanned));

            if ($time < $pivot) {
                $shift1[] = $scan->dscanned;
            } else {
                $shift2[] = $scan->dscanned;
            }
        }

        $in1 = !empty($shift1) ? min($shift1) : null;
        $out1 = !empty($shift1) ? max($shift1) : null;

        $in2 = !empty($shift2) ? min($shift2) : null;
        $out2 = !empty($shift2) ? max($shift2) : null;

        return [
            'user_id' => $first->nuserid,
            'cname' => $first->cname,
            'date' => date('Y-m-d', strtotime($first->dscanned)),
            'cschedname' => $first->cschedname,

            'dstart' => $first->dstart,
            'dend' => $first->dend,
            'dstart2' => $first->dstart2,
            'dend2' => $first->dend2,

            'in_time' => $in1 ? date('H:i:s', strtotime($in1)) : null,
            'out_time' => $out1 ? date('H:i:s', strtotime($out1)) : null,

            'in_time2' => $in2 ? date('H:i:s', strtotime($in2)) : null,
            'out_time2' => $out2 ? date('H:i:s', strtotime($out2)) : null,

            'niddept' => $first->niddept,
            'type' => 'attendance'
        ];
    }

    public function getMissingAttendance(Request $request)
    {
        $startDate = $request->query('start') ?: now()->toDateString();
        $endDate   = $request->query('end') ?: $startDate;

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $filterDept = $request->query('dept');
        $deptId = $user->niddept;
        $isHRD  = $user->fhrd == 1;

        try {

            $sql = "
            SELECT
                u.nid AS user_id,
                u.cname,
                u.niddept,
                ? AS date
            FROM muser u
            WHERE NOT EXISTS (
                SELECT 1
                FROM (
                    SELECT nuserid, DATE(dscanned) ddate FROM mscan
                    UNION ALL
                    SELECT nuserid, DATE(dscanned) ddate FROM mscan_manual
                    UNION ALL
                    SELECT nuserId AS nuserid, DATE(dscanned) ddate FROM mface_scan
                ) s
                WHERE s.nuserid = u.nid
                AND s.ddate BETWEEN ? AND ?
            )
        ";

            $params = [$startDate, $startDate, $endDate];

            if ($user && $user->ccompany) {
                $sql .= " AND u.ccompany = ? ";
                $params[] = $user->ccompany;
            }

            if (!empty($filterDept)) {
                $sql .= " AND u.niddept = ? ";
                $params[] = $filterDept;
            } elseif (!$isHRD && !empty($deptId)) {
                $sql .= " AND u.niddept = ? ";
                $params[] = $deptId;
            }

            $sql .= " ORDER BY u.cname";

            $missing = DB::select($sql, $params);

            return response()->json([
                'success' => true,
                'data'    => $missing,
                'total'   => count($missing)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportByDepartment(Request $request)
    {
        $start = $request->query('start') ?? now()->toDateString();
        $end   = $request->query('end') ?? $start;
        $user  = Auth::user();

        return Excel::download(
            new MscanReportByDeptExport($start, $end, $user),
            'laporan_absensi_per_departemen.xlsx'
        );
    }
}
