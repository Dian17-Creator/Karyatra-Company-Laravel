<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\MscanExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\muser; // model muser
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MscanController extends Controller
{
    public function exportExcel(Request $request)
    {
        $userId = $request->query('user_id');
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        // Ambil nama user dari model (berdasarkan ID)
        $userName = 'semua';
        if ($userId) {
            $user = muser::find($userId);
            $userName = $user ? $user->cname : $userName;
        }

        // Bersihkan nama agar aman untuk nama file
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', strtolower($userName));

        // Tambahkan tanggal ke nama file
        $fileName = 'log_absensi_' . $safeName . '_' . date('Ymd') . '.xlsx';

        // Kembalikan file Excel
        return Excel::download(new MscanExport($userId, $start, $end), $fileName);
    }
}
