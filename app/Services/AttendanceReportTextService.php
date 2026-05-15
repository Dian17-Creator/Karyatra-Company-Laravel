<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Mail\AttendanceReportTextMail;
use Log;

class AttendanceReportTextService
{
    public function sendDaily(): void
    {
        $date = now()->toDateString();

        $data = DB::table(DB::raw("(
            SELECT nuserid, dscanned
            FROM mscan_manual
            WHERE dscanned >= '$date 00:00:00'
            AND dscanned <= '$date 23:59:59'

            UNION ALL

            SELECT nuserId AS nuserid, dscanned
            FROM mface_scan
            WHERE dscanned >= '$date 00:00:00'
            AND dscanned <= '$date 23:59:59'
        ) scan"))

        ->join('muser', 'muser.nid', '=', 'scan.nuserid')
        ->join('mdepartment', 'mdepartment.nid', '=', 'muser.niddept')
        ->select(
            'mdepartment.cname as department',
            'muser.cname as name',
            DB::raw('MIN(scan.dscanned) as checkin')
        )
        ->groupBy('department', 'name')
        ->orderBy('department')
        ->get();

        // ===============================
        // DATA IZIN
        // ===============================
        $izin = DB::table('mrequest')
            ->join('muser', 'muser.nid', '=', 'mrequest.nuserid')
            ->select(
                'muser.cname as name',
                'mrequest.creason as reason'
            )
            ->whereDate('mrequest.drequest', $date)
            ->get();

        // ===============================
        // BUILD REPORT TEXT
        // ===============================
        $report = "";

        if ($data->count() > 0) {

            $grouped = $data->groupBy('department');

            foreach ($grouped as $dept => $users) {

                $report .= "{$dept}\n";

                foreach ($users as $u) {

                    $jam = Carbon::parse($u->checkin)->format('H:i');

                    $report .= "-> {$u->name} ({$jam})\n";
                }

                $report .= "\n";
            }
        }

        // ===============================
        // SECTION IZIN
        // ===============================
        if ($izin->count() > 0) {

            $report .= "----------------------------------\n";
            $report .= "IZIN HARI INI\n";

            foreach ($izin as $i) {

                $report .= "-> {$i->name} ({$i->reason})\n";
            }

            $report .= "\n";
        }

        // ===============================
        // JIKA REPORT KOSONG
        // ===============================
        if (trim($report) === "") {
            $report = "Tidak ada data absensi hari ini.";
        }

        // ===============================
        // KIRIM EMAIL
        // ===============================
        Mail::to(config('mail.attendance_email_dian'))
            ->send(new AttendanceReportTextMail($report));

        Log::info("Attendance text report {$date} sent.");
    }
}
