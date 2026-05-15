<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\MrequestExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class MrequestExportController extends Controller
{
    public function export(Request $request)
    {
        $user_id = $request->input('user_id');
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        // Ambil nama user (cname) berdasarkan user_id (nid)
        $user = DB::table('muser')->where('nid', $user_id)->first();
        $user_name = $user ? $user->cname : 'user';

        // Bersihkan nama agar aman untuk nama file (hapus spasi, karakter spesial)
        $safe_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $user_name);

        // Nama file akhir
        $filename = 'log_request_absensi_' . $safe_name . '_' . date('Ymd') . '.xlsx';

        return Excel::download(
            new MrequestExport($user_id, $start, $end),
            $filename
        );
    }
}
