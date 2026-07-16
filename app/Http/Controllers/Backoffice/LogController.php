<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\muser;
use App\Models\mscan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class LogController extends Controller
{
    public function viewLogs(int $userId)
    {
        $date = request('date');

        $startDate = request('start_date') ?? $date ?? now()->subMonth()->toDateString();
        $endDate   = request('end_date') ?? $date ?? now()->toDateString();
        $sort      = request('sort', 'desc');
        $source    = request('source');
        $status    = request('status');

        // =========================
        // Ambil data dari mscan
        // =========================
        $scanLogs = DB::table('mscan')
            ->join('muser', 'mscan.nuserId', '=', 'muser.nid')
            ->leftJoin('mtoken as t', 'mscan.ntokenId', '=', 't.nid')
            ->select(
                'mscan.nid',
                'mscan.nuserId',
                'mscan.dscanned',
                'mscan.nlat',
                'mscan.nlng',
                'mscan.ntokenId',
                't.nlat as token_lat',
                't.nlng as token_lng',
                'mscan.creason',
                'mscan.cphoto_path',
                'mscan.cstatus',
                'mscan.csuperstat',
                'mscan.chrdstat',
                'muser.cname',
                'muser.fadmin',
                'muser.fhrd',
                'muser.fsuper',
                DB::raw('COALESCE(mscan.fmanual, 0) as fmanual'),
                'muser.cname',
                DB::raw("CASE WHEN mscan.creason IS NOT NULL AND mscan.creason != '' THEN 'manual' ELSE 'scan' END as source"),
                DB::raw("'mscan' as source_origin"),
                'mscan.cplacename',
                DB::raw('NULL as ciswifi')
            )
            ->where('mscan.nuserId', $userId)
            ->whereBetween('mscan.dscanned', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        // =========================
        // Ambil data dari mscan_manual
        // =========================
        $manualLogs = DB::table('mscan_manual')
            ->join('muser', 'mscan_manual.nuserId', '=', 'muser.nid')
            ->select(
                'mscan_manual.nid',
                'mscan_manual.nuserId',
                'mscan_manual.dscanned',
                'mscan_manual.nlat',
                'mscan_manual.nlng',
                DB::raw('NULL as ntokenId'),
                DB::raw('NULL as token_lat'),
                DB::raw('NULL as token_lng'),
                'mscan_manual.creason',
                'mscan_manual.cphoto_path',
                'mscan_manual.cstatus',
                'mscan_manual.csuperstat',
                'mscan_manual.chrdstat',
                'mscan_manual.status',
                'muser.cname',
                'muser.cname',
                'muser.fadmin',
                'muser.fhrd',
                'muser.fsuper',
                DB::raw('0 as fmanual'),
                DB::raw("LOWER(mscan_manual.status) as source"),
                DB::raw("'mscan_manual' as source_origin"),
                'mscan_manual.cplacename',
                'mscan_manual.cdevstring',
                DB::raw('NULL as ciswifi')
            )
            ->where('mscan_manual.nuserId', $userId)
            ->whereBetween('mscan_manual.dscanned', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ])
            ->get();

        // =========================
        // Ambil data dari mface_scan (face logs)
        // =========================
        $faceLogs = DB::table('mface_scan')
            ->join('muser', 'mface_scan.nuserId', '=', 'muser.nid')
            ->select(
                'mface_scan.nid',
                'mface_scan.nuserId',
                'mface_scan.dscanned',
                'mface_scan.nlat',
                'mface_scan.nlng',
                DB::raw('NULL as ntokenId'),
                DB::raw('NULL as token_lat'),
                DB::raw('NULL as token_lng'),
                DB::raw('NULL as creason'),
                'mface_scan.cphoto_path',
                DB::raw('NULL as cstatus'),
                DB::raw('NULL as csuperstat'),
                DB::raw('NULL as chrdstat'),
                DB::raw('0 as fmanual'),
                'muser.cname',
                DB::raw("'face' as source"),
                DB::raw("'mface_scan' as source_origin"),
                'mface_scan.cplacename',
                'mface_scan.cdevstring',
                'muser.cname',
                'muser.fadmin',
                'muser.fhrd',
                'muser.fsuper',
                'mface_scan.ciswifi'
            )
            ->where('mface_scan.nuserId', $userId)
            ->whereBetween('mface_scan.dscanned', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        // Gabungkan semua hasil dan urutkan berdasarkan tanggal
        $logs = $scanLogs
            ->merge($manualLogs)
            ->merge($faceLogs)
            ->sortByDesc('dscanned')
            ->values();

        if ($source) {
            $logs = $logs->where('source', $source);
        }

        if ($status) {
            $logs = $logs->filter(function ($log) use ($status) {
                return ($log->cstatus ?? null) === $status ||
                    ($log->chrdstat ?? null) === $status ||
                    ($log->csuperstat ?? null) === $status;
            });
        }

        $logs = $logs->sortByDesc('dscanned')->values();

        // Ambil info user
        $user = muser::findOrFail($userId);

        // Pagination manual
        $page      = request('page', 1);
        $perPage   = 10;
        $offset    = ($page - 1) * $perPage;

        $paginatedLogs = new LengthAwarePaginator(
            $logs->slice($offset, $perPage),
            $logs->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('backoffice.logs', [
            'logs' => $paginatedLogs,
            'user' => $user,
            'sort' => $sort
        ]);
    }

    public function apiLogs(int $userId)
    {
        try {
            $startDate = request('start_date', now()->subMonth()->toDateString());
            $endDate   = request('end_date', now()->toDateString());

            // SCAN (mscan)
            $scanLogs = DB::table('mscan')
                ->select(
                    'nid',
                    'nuserId',
                    'dscanned',
                    'nlat',
                    'nlng',
                    'nadminid',
                    DB::raw("'scan' as type_absen")
                )
                ->where('nuserId', $userId)
                ->whereBetween('dscanned', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);

            // MANUAL (mscan_manual)
            $manualLogs = DB::table('mscan_manual')
                ->select(
                    'nid',
                    'nuserId',
                    'dscanned',
                    'nlat',
                    'nlng',
                    DB::raw('NULL as nadminid'),
                    DB::raw("'manual' as type_absen")
                )
                ->where('nuserId', $userId)
                ->whereBetween('dscanned', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);

            // FACE (mface_scan)
            $faceLogs = DB::table('mface_scan')
                ->select(
                    'nid',
                    'nuserId',
                    'dscanned',
                    'nlat',
                    'nlng',
                    DB::raw('NULL as nadminid'),
                    DB::raw("'face' as type_absen")
                )
                ->where('nuserId', $userId)
                ->whereBetween('dscanned', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);

            // GABUNGKAN SEMUA
            $logs = $scanLogs
                ->unionAll($manualLogs)
                ->unionAll($faceLogs)
                ->orderBy('dscanned', 'desc')
                ->get();

            return response()->json($logs);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteLogs(Request $request)
    {
        if (Auth::user()->fsuper != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus log absensi.');
        }

        $userId = $request->user_id;
        DB::transaction(function () use ($userId) {
            mscan::where('nuserId', $userId)->delete();
            DB::table('mscan_manual')->where('nuserId', $userId)->delete();
            DB::table('mface_scan')->where('nuserId', $userId)->delete();
        });

        return back()->with('success', 'Log absensi user berhasil dihapus.');
    }
}
