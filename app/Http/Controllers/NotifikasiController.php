<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Mail\NotifikasiEmail;
use App\Jobs\SendEmailOnChange;

class NotifikasiController extends Controller
{
    // 🔹 1. TAMPILAN WEB — hanya tampilkan notifikasi, tidak kirim email
    public function index()
    {

        $user = Auth::user();
        $isHrd = $user->fhrd == 1;
        $userDept = $user->niddept;

        $today = \Carbon\Carbon::today();
        $next30Days = \Carbon\Carbon::today()->addDays(30);

        // Ambil izin pending
        $izinPending = DB::table('mrequest')
            ->join('muser', 'mrequest.nuserid', '=', 'muser.nid')
            ->where('mrequest.chrdstat', 'pending')
            ->where('mrequest.cstatus', 'pending')
            ->when(!$isHrd, fn($q) => $q->where('muser.niddept', $userDept))
            ->select('mrequest.nid', 'muser.cname as nama', 'mrequest.nuserid as nuserid', 'mrequest.dcreated as tanggal')
            ->get();

        // Ambil absen manual pending
        $absenPending = DB::table('mscan_manual')
            ->join('muser', 'mscan_manual.nuserId', '=', 'muser.nid')
            ->where('mscan_manual.chrdstat', 'pending')
            ->where('mscan_manual.cstatus', 'pending')
            // pastikan bukan entri "forgot" sehingga tidak muncul dua kali
            ->where('mscan_manual.status', '<>', 'forgot')
            ->when(!$isHrd, fn($q) => $q->where('muser.niddept', $userDept))
            ->select('mscan_manual.nid', 'muser.cname as nama', 'muser.nid as nuserid', 'mscan_manual.dscanned as tanggal')
            ->get();

        //Lupa Absen Pending
        $forgotPending = DB::table('mscan_manual')
            ->join('muser', 'mscan_manual.nuserId', '=', 'muser.nid')
            ->where('mscan_manual.status', 'forgot') // 🔥 INI PENTING
            ->where('mscan_manual.cstatus', 'pending')
            ->where('mscan_manual.chrdstat', 'pending')
            ->when(!$isHrd, fn($q) => $q->where('muser.niddept', $userDept))
            ->select(
                'mscan_manual.nid',
                'muser.cname as nama',
                'muser.nid as nuserid',
                'mscan_manual.dscanned as tanggal'
            )
            ->get();


        // Ambil kontrak habis 30 hari lagi
        // $contractsExpiring = \DB::table('tusercontract')
        //     ->join('muser', 'tusercontract.nuserid', '=', 'muser.nid')
        //     ->whereBetween('tusercontract.dend', [$today, $next30Days])
        //     ->where('tusercontract.cstatus', 'active')
        //     ->when(!$isHrd, fn ($q) => $q->where('muser.niddept', $userDept))
        //     ->select('tusercontract.nid', 'muser.cname as nama', 'tusercontract.nuserid as nuserid', 'tusercontract.dend as tanggal_akhir')
        //     ->get();

        // Ambil registrasi wajah yang belum aktif (belum ada di mface_scan)
        // $facePending = DB::table('tuserfaces')
        //     ->join('muser', 'tuserfaces.nuserid', '=', 'muser.nid')
        //     ->leftJoin('mface_scan', 'tuserfaces.nuserid', '=', 'mface_scan.nuserid')
        //     ->whereNull('mface_scan.nuserid') // BELUM APPROVED
        //     ->when(!$isHrd, fn ($q) => $q->where('muser.niddept', $userDept))
        //     ->select(
        //         'tuserfaces.nid',
        //         'muser.cname as nama',
        //         'muser.nid as nuserid',
        //         'tuserfaces.dcreated as tanggal'
        //     )
        //     ->get();

        // Gabungkan semua notifikasi
        $notifications = collect();

        foreach ($izinPending as $izin) {
            $notifications->push([
                'message' => '📝 Izin dari ' . $izin->nama . ' menunggu approval',
                'time' => $izin->tanggal,
                'type' => 'izin',
                'url' => url('/backoffice/requestcard/' . $izin->nuserid)
            ]);
        }

        foreach ($absenPending as $absen) {
            $notifications->push([
                'message' => '📋 Absen manual dari ' . $absen->nama . ' menunggu approval',
                'time' => $absen->tanggal,
                'type' => 'absen',
                'url' => url('/backoffice/logs/' . $absen->nuserid . '?source=manual&status=pending')
            ]);
        }

        foreach ($forgotPending as $forgot) {
            $notifications->push([
                'message' => '🕒 Lupa absen dari ' . $forgot->nama . ' menunggu approval HRD',
                'time'    => $forgot->tanggal,
                'type'    => 'forgot',
                'url' => url('/backoffice/logs/' . $forgot->nuserid . '?source=forgot&status=pending')
            ]);
        }

        // foreach ($contractsExpiring as $kontrak) {
        //     $remainingDays = \Carbon\Carbon::parse($kontrak->tanggal_akhir)->diffInDays($today);
        //     $notifications->push([
        //         'message' => '⏳ Kontrak kerja ' . $kontrak->nama . ' akan berakhir dalam ' . $remainingDays . ' hari (' .
        //             \Carbon\Carbon::parse($kontrak->tanggal_akhir)->format('d M Y') . ')',
        //         'time' => $kontrak->tanggal_akhir,
        //         'type' => 'contract',
        //         'url' => url('/schedule') // atau bisa ke halaman detail kontrak kalau kamu punya route-nya
        //     ]);
        // }

        // foreach ($facePending as $face) {
        //     $notifications->push([
        //         'message' => '📷 Registrasi wajah dari ' . $face->nama . ' menunggu approval',
        //         'time'    => $face->tanggal,
        //         'type'    => 'face',
        //         'url'     => url('/faces') // atau /faces/approval
        //     ]);
        // }

        $notifications = $notifications->sortByDesc('time')->values();

        return response()->json([
            'notifications' => $notifications,
            'count' => $notifications->count()
        ]);
    }

    public function trigger(Request $request)
    {
        // 🔐 validasi token
        if ($request->token !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $id = $request->id;

        // =========================
        // 🔍 CEK IZIN DULU
        // =========================
        $data = DB::table('mrequest')
            ->join('muser', 'mrequest.nuserid', '=', 'muser.nid')
            ->where('mrequest.nid', $id)
            ->select(
                'mrequest.nid',
                'mrequest.nuserid',
                'mrequest.creason',
                'mrequest.cphoto_path',
                'mrequest.dcreated',
                'muser.cname as nama',
                DB::raw("'izin' as tipe")
            )
            ->first();

        // =========================
        // ✅ JIKA IZIN → AMBIL RANGE
        // =========================
        if ($data) {

            $range = DB::table('mrequest')
                ->where('nuserid', $data->nuserid)
                ->where('creason', $data->creason)
                ->where('cphoto_path', $data->cphoto_path)
                ->whereDate('dcreated', \Carbon\Carbon::parse($data->dcreated)->toDateString())
                ->selectRaw('MIN(drequest) as start_date, MAX(drequest) as end_date')
                ->first();

            $data->start_date = $range->start_date;
            $data->end_date   = $range->end_date;
        } else {

            // =========================
            // 🔄 KALAU BUKAN IZIN → CEK MANUAL
            // =========================
            $data = DB::table('mscan_manual')
                ->join('muser', 'mscan_manual.nuserId', '=', 'muser.nid')
                ->where('mscan_manual.nid', $id)
                ->select(
                    'mscan_manual.nid',
                    'muser.cname as nama',
                    'mscan_manual.creason',
                    'mscan_manual.dscanned as tanggal',
                    DB::raw("'manual' as tipe")
                )
                ->first();
        }

        // =========================
        // ❌ DATA TIDAK ADA
        // =========================
        if (!$data) {
            return response()->json([
                'error' => 'Data tidak ditemukan'
            ], 404);
        }

        // =========================
        // 🚀 KIRIM KE QUEUE
        // =========================
        SendEmailOnChange::dispatch($data)->onQueue('hrd');

        return response()->json([
            'status' => 'ok',
            'type' => $data->tipe
        ]);
    }
}
