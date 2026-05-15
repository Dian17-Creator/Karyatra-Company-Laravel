<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MscanReportExport;

class MscanReportExportController extends Controller
{
    public function export(Request $request)
    {
        // =========================
        // 🔹 Ambil parameter filter
        // =========================
        $start = $request->input('start_date');
        $end   = $request->input('end_date');
        $dept  = $request->input('dept');

        // Default tanggal = hari ini
        if (empty($start) && empty($end)) {
            $start = now()->toDateString();
            $end   = now()->toDateString();
        }

        // =========================
        // 🔹 Auth
        // =========================
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // =========================
        // 🔹 Nama file
        // =========================
        $date = now()->format('Ymd');
        $fileName = "laporan_absensi_{$date}.xlsx";

        // =========================
        // 🔹 Export Excel (TANPA named arguments)
        // =========================
        return Excel::download(
            new MscanReportExport(
                $start,
                $end,
                $dept,
                $user
            ),
            $fileName
        );
    }
}
