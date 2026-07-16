<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\muser;
use App\Models\mscan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class FingerprintController extends Controller
{
    public function importFingerprint(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $IMPORT_TOKEN_ID = 2782;

        // Ambil semua sheet sebagai collection
        $sheets = Excel::toCollection(null, $request->file('file'));

        if ($sheets->count() < 4) {
            return back()->with('error', 'File fingerprint tidak memiliki sheet ke-4 (Exception Stat).');
        }

        $sheet    = $sheets[3];
        $inserted = 0;

        foreach ($sheet->skip(1) as $row) {
            $fingerId   = $row[0];
            $tanggalRaw = $row[3];
            $jamInRaw   = $row[4] ?? null;
            $jamOutRaw  = $row[5] ?? null;

            if (!$fingerId || !$tanggalRaw) {
                continue;
            }

            $user = muser::where('finger_id', $fingerId)->first();
            if (!$user) {
                continue;
            }

            // --- konversi tanggal ---
            if (is_numeric($tanggalRaw)) {
                $tanggal = Carbon::instance(ExcelDate::excelToDateTimeObject($tanggalRaw))->format('Y-m-d');
            } else {
                $tanggal = Carbon::parse($tanggalRaw)->format('Y-m-d');
            }

            // kalau tanggal ini sudah punya scan, lewati
            $alreadyHasScan = mscan::where('nuserId', $user->nid)
                ->whereDate('dscanned', $tanggal)
                ->exists();

            if ($alreadyHasScan) {
                continue;
            }

            // --- helper konversi jam ---
            $convertTime = function ($timeRaw) use ($tanggal) {
                if (!$timeRaw) {
                    return null;
                }

                if (is_numeric($timeRaw)) {
                    $dt = Carbon::instance(ExcelDate::excelToDateTimeObject($timeRaw));
                    return $dt->format('Y-m-d H:i:s');
                }

                return Carbon::parse($tanggal . ' ' . $timeRaw)->format('Y-m-d H:i:s');
            };

            $scanIn  = $convertTime($jamInRaw);
            $scanOut = $convertTime($jamOutRaw);

            // ====== INSERT SCAN IN ======
            if ($scanIn) {
                $exists = mscan::where('nuserId', $user->nid)
                    ->where('dscanned', $scanIn)
                    ->exists();

                if (!$exists) {
                    mscan::create([
                        'nuserId'     => $user->nid,
                        'nkioskId'    => 0,
                        'ntokenId'    => $IMPORT_TOKEN_ID,
                        'dscanned'    => $scanIn,
                        'nlat'        => null,
                        'nlng'        => null,
                        'cplacename'  => null,
                        'fmanual'     => 0,
                        'nadminid'    => null,
                        'creason'     => null,
                        'cphoto_path' => null,
                    ]);
                    $inserted++;
                }
            }

            // ====== INSERT SCAN OUT ======
            if ($scanOut) {
                $exists = mscan::where('nuserId', $user->nid)
                    ->where('dscanned', $scanOut)
                    ->exists();

                if (!$exists) {
                    mscan::create([
                        'nuserId'     => $user->nid,
                        'nkioskId'    => 0,
                        'ntokenId'    => $IMPORT_TOKEN_ID,
                        'dscanned'    => $scanOut,
                        'nlat'        => null,
                        'nlng'        => null,
                        'cplacename'  => null,
                        'fmanual'     => 0,
                        'nadminid'    => null,
                        'creason'     => null,
                        'cphoto_path' => null,
                    ]);
                    $inserted++;
                }
            }
        }

        return back()->with('success', "Import fingerprint selesai. Scan baru ditambahkan: {$inserted}.");
    }
}
