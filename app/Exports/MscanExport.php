<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MscanExport implements FromCollection, WithHeadings, WithMapping
{
    protected $userId;
    protected $start;
    protected $end;

    public function __construct($userId = null, $start = null, $end = null)
    {
        $this->userId = $userId;
        $this->start = $start;
        $this->end = $end;
    }

    public function collection()
    {
        // ===============================
        // 1️⃣ SCAN NORMAL (mscan)
        // ===============================
        $scanLogs = DB::table('mscan')
            ->join('muser', 'mscan.nuserId', '=', 'muser.nid')
            ->leftJoin('mtoken as t', 'mscan.ntokenId', '=', 't.nid')
            ->select(
                'mscan.nid',
                'mscan.nuserId',
                'mscan.dscanned',
                'mscan.nlat',
                'mscan.nlng',
                'mscan.cplacename as location_name', // ✅ FIX UTAMA
                'mscan.creason',
                'mscan.cstatus',
                'mscan.chrdstat',
                DB::raw('COALESCE(mscan.fmanual, 0) as fmanual'),
                'muser.cname',
                DB::raw("
                    CASE
                        WHEN mscan.fmanual = 1
                            OR (mscan.creason IS NOT NULL AND mscan.creason != '')
                        THEN 'Manual'
                        ELSE 'Scan'
                    END as tipe_absen
                "),
                DB::raw("'mscan' as source_origin")
            )

            ->where('mscan.nuserId', $this->userId)
            ->whereBetween('mscan.dscanned', [
                $this->start . ' 00:00:00',
                $this->end . ' 23:59:59',
            ])
            ->get();

        // ===============================
        // 2️⃣ MANUAL (mscan_manual)
        // ===============================
        $manualLogs = DB::table('mscan_manual')
            ->join('muser', 'mscan_manual.nuserId', '=', 'muser.nid')
            ->select(
                'mscan_manual.nid',
                'mscan_manual.nuserId',
                'mscan_manual.dscanned',
                'mscan_manual.nlat',
                'mscan_manual.nlng',
                'mscan_manual.cplacename as location_name', // ✅ REVISI (1 lokasi saja)
                'mscan_manual.creason',
                'mscan_manual.cstatus',
                'mscan_manual.chrdstat',
                DB::raw('1 as fmanual'),
                'muser.cname',
                DB::raw("'Manual' as tipe_absen"), // ✅ REVISI
                DB::raw("'mscan_manual' as source_origin")
            )
            ->where('mscan_manual.nuserId', $this->userId)
            ->whereBetween('mscan_manual.dscanned', [
                $this->start . ' 00:00:00',
                $this->end . ' 23:59:59',
            ])
            ->get();

        // ===============================
        // 3️⃣ FACE SCAN (mface_scan)
        // ===============================
        $faceLogs = DB::table('mface_scan')
            ->join('muser', 'mface_scan.nuserId', '=', 'muser.nid')
            ->select(
                'mface_scan.nid',
                'mface_scan.nuserId',
                'mface_scan.dscanned',
                'mface_scan.nlat',
                'mface_scan.nlng',
                'mface_scan.cplacename as location_name', // ✅ REVISI (1 lokasi saja)
                DB::raw('NULL as token_lat'),
                DB::raw('NULL as token_lng'),
                DB::raw('NULL as creason'),
                DB::raw('NULL as cstatus'),
                DB::raw('NULL as chrdstat'),
                DB::raw('0 as fmanual'),
                'muser.cname',
                DB::raw("'Face' as tipe_absen"), // ✅ REVISI
                DB::raw("'mface_scan' as source_origin")
            )
            ->where('mface_scan.nuserId', $this->userId)
            ->whereBetween('mface_scan.dscanned', [
                $this->start . ' 00:00:00',
                $this->end . ' 23:59:59',
            ])
            ->get();

        return collect()
            ->merge($scanLogs)
            ->merge($manualLogs)
            ->merge($faceLogs)
            ->sortByDesc('dscanned')
            ->values();
    }
    public function headings(): array
    {
        return [
            'ID',
            'Tanggal & Waktu',
            'Lokasi',
            // 'Koordinat Scan',
            'Tipe Absen',
            'Alasan (Jika Manual)',
            'Status Approval',
            'User',
        ];
    }
    public function map($item): array
    {
        $lokasi = $item->location_name ?? '-'; // ✅ REVISI
        $statusText = '-';

        if ($item->tipe_absen !== 'Manual') {
            $statusText = 'Accepted';
        } else {
            if ($item->chrdstat === 'approved') {
                $statusText = 'Approved by HRD';
            } elseif ($item->chrdstat === 'rejected') {
                $statusText = 'Rejected by HRD';
            } elseif ($item->cstatus === 'approved') {
                $statusText = 'Approved by Captain';
            } elseif ($item->cstatus === 'rejected') {
                $statusText = 'Rejected by Captain';
            } elseif (!empty($item->fmanual)) {
                $statusText = 'Approved (Manual)';
            } else {
                $statusText = 'Pending by Captain';
            }
        }

        return [
            $item->nid,
            $item->dscanned,
            $lokasi,
            // $item->nlat . ', ' . $item->nlng,
            $item->tipe_absen,
            $item->creason ?? '-',
            $statusText,
            $item->cname ?? '-',
        ];
    }
}
