<?php

namespace App\Services;

use App\Exports\MscanReportExport;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\AttendanceReportMail;
use Illuminate\Support\Facades\File;

class AttendanceReportService
{
    public function sendDaily(): void
    {
        $date = now()->subDay()->toDateString();

        // 🔥 nama fix → selalu overwrite
        $fileName = "laporan_absensi.xlsx";

        $folder = public_path("uploads/attendance");

        File::ensureDirectoryExists($folder);

        $path = $folder . "/" . $fileName;

        $excelBinary = Excel::raw(
            new MscanReportExport($date, $date, null, null),
            \Maatwebsite\Excel\Excel::XLSX,
        );

        File::put($path, $excelBinary); // otomatis overwrite

        Mail::to(env("ATTENDANCE_REPORT_EMAIL_DIAN"))->send(
            new AttendanceReportMail($path),
        );
    }
}
