<?php

namespace App\Imports;

use App\Models\timport_userschedule;
use App\Models\UserSchedule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

ini_set('max_execution_time', 9000);
ini_set('memory_limit', '512M');

class ImportSchedule implements ToCollection
{
    private array $shiftMap = [
        'KT1' => 'Pagi',
        'BR1' => 'Pagi',
        'SV1' => 'Pagi',

        'KT2' => 'Siang',
        'BR2' => 'Siang',
        'SV2' => 'Siang',

        'SP'  => 'Split',
        'MD'  => 'Middle',

        'OFF' => 'Libur',
        'KT3' => 'Pagi(Ramadhan)',
        'SV3' => 'Pagi(Ramadhan)',
    ];

    public function collection(Collection $rows)
    {
        Log::info('[IMPORT] Matrix import mulai', [
            'total_rows_excel' => $rows->count()
        ]);

        // 🔥 clear temp table
        //DB::table('timport_userschedule')->delete();

        /**
         * ===============================
         * DETEKSI BARIS HEADER TANGGAL
         * ===============================
         * Baris yang berisi banyak angka (1–31)
         */
        $headerTanggal = null;

        foreach ($rows as $rowIndex => $row) {
            $numericCount = 0;

            foreach ($row as $cell) {
                if (is_numeric($cell)) {
                    $numericCount++;
                }
            }

            // kalau satu baris punya >= 10 angka → ini header tanggal
            if ($numericCount >= 10) {
                $headerTanggal = $row;
                Log::info('[IMPORT] Header tanggal ditemukan', [
                    'row_index' => $rowIndex
                ]);
                break;
            }
        }

        if (!$headerTanggal) {
            Log::error('[IMPORT] Header tanggal tidak ditemukan');
            return;
        }

        $insert = [];

        foreach ($rows as $rowIndex => $row) {

            $nama = trim((string)($row[0] ?? ''));

            // skip baris non-user
            if (
                $nama === '' ||
                strtoupper($nama) === 'NAMA' ||
                strtoupper($nama) === 'NOTE:' ||
                strtoupper($nama) === 'BAR' ||
                strtoupper($nama) === 'KITCHEN' ||
                strtoupper($nama) === 'SERVER'
            ) {
                continue;
            }

            // loop kolom tanggal
            foreach ($headerTanggal as $colIndex => $day) {

                if ($colIndex < 3 || !is_numeric($day)) {
                    continue;
                }

                $kodeRaw = $row[$colIndex] ?? null;
                if (!$kodeRaw) {
                    continue;
                }

                $kode = strtoupper(str_replace(' ', '', trim($kodeRaw)));
                $cschedname = $this->shiftMap[$kode] ?? null;

                if (!$cschedname || $cschedname === 'Libur') {
                    continue;
                }

                // tanggal = hari ke-n di bulan berjalan
                $tanggal = now()
                    ->startOfMonth()
                    ->addDays(((int)$day) - 1)
                    ->format('Y-m-d');

                $insert[] = [
                    'cusername'  => $nama,
                    'dwork'      => $tanggal,
                    'cschedname' => $cschedname,
                ];
            }
        }

        Log::info('[IMPORT] Parsed matrix result', [
            'total_insert' => count($insert)
        ]);

        if (empty($insert)) {
            Log::warning('[IMPORT] Tidak ada data valid');
            return;
        }

        // ===== INSERT TEMP =====
        timport_userschedule::insert($insert);

        // ===== MATCH USER =====
        DB::statement("
            UPDATE timport_userschedule t
            JOIN muser u
              ON TRIM(LOWER(t.cusername)) = TRIM(LOWER(u.cname))
            SET t.nuserid = u.nid
        ");

        // ===== MATCH SCHEDULE =====
        DB::statement("
            UPDATE timport_userschedule t
            JOIN mschedule s
              ON t.cschedname = s.cname
            SET
              t.nidsched = s.nid,
              t.dstart   = s.dstart,
              t.dend     = s.dend,
              t.dstart2  = s.dstart2,
              t.dend2    = s.dend2
        ");

        // ===== FINAL UPSERT =====
        $final = timport_userschedule::whereNotNull('nuserid')
            ->whereNotNull('nidsched')
            ->get()
            ->map(fn ($r) => [
                'nuserid'    => $r->nuserid,
                'dwork'      => $r->dwork,
                'dstart'     => $r->dstart,
                'dend'       => $r->dend,
                'dstart2'    => $r->dstart2,
                'dend2'      => $r->dend2,
                'nidsched'   => $r->nidsched,
                'cschedname' => $r->cschedname,
            ])
            ->toArray();

        UserSchedule::upsert(
            $final,
            ['nuserid', 'dwork'],
            ['dstart', 'dend', 'dstart2', 'dend2', 'nidsched', 'cschedname']
        );

        Log::info('[IMPORT] Matrix import selesai', [
            'final_rows' => count($final)
        ]);
    }
}
