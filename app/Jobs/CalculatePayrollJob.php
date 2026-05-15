<?php

namespace App\Jobs;

use App\Models\Csalary;
use App\Models\muser;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CalculatePayrollJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $userId;
    public int $year;
    public int $month;
    public bool $force;
    public bool $splitByChange;

    private function toNumber($value): float
    {
        if ($value === null || $value === "") {
            return 0;
        }

        // Kalau sudah numeric dari DB
        if (is_numeric($value)) {
            return (float) $value;
        }

        // String "Rp 1.040.014,30"
        $clean = str_replace(["Rp", " ", "."], "", $value);
        $clean = str_replace(",", ".", $clean);

        return is_numeric($clean) ? (float) $clean : 0;
    }

    public function __construct(
        int $userId,
        int $year,
        int $month,
        bool $force = false,
        bool $splitByChange = false,
    ) {
        $this->userId = $userId;
        $this->year = $year;
        $this->month = $month;
        $this->force = $force;
        $this->splitByChange = $splitByChange;
    }

    public function handle()
    {
        $userId = $this->userId;
        $year = $this->year;
        $month = $this->month;

        //Untuk lihat user aktif
        $user = muser::select("nid", "factive")->where("nid", $userId)->first();

        if (!$user || (int) $user->factive !== 1) {
            //log::info("SKIP payroll: user inactive user={$userId}");
            return;
        }

        $userName = DB::table("muser")->where("nid", $userId)->value("cname");

        //Filter department ck dan backoffice
        $userDept = DB::table("muser as u")
            ->join("mdepartment as d", "u.niddept", "=", "d.nid")
            ->where("u.nid", $userId)
            ->value("d.cname");

        $ignoreLaterDepartments = ["CK", "BACKOFFICE"];
        // $ignoreLaterDepartments = [''];
        $ignoreLate = in_array(
            strtoupper(trim($userDept)),
            $ignoreLaterDepartments,
        );

        //Log::info("PAYROLL START user={$userId} {$year}-{$month}");

        /** ===============================
         * PERIODE HITUNG
         * =============================== */
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // ⛔ Jangan hitung tanggal setelah hari ini
        $today = Carbon::today()->endOfDay();
        if ($periodEnd->gt($today)) {
            $periodEnd = $today;
        }

        /** ===============================
         * 1️⃣ AMBIL SEMUA TANGGAL HADIR
         * =============================== */
        $scanDates = DB::select(
            "
            SELECT DISTINCT DATE(dscanned) AS dt FROM (
                SELECT dscanned FROM mscan WHERE nuserid = ?
                UNION
                SELECT dscanned FROM mscan_manual WHERE nuserid = ?
                UNION
                SELECT dscanned FROM mface_scan WHERE nuserid = ?
            ) x
            WHERE DATE(dscanned) BETWEEN ? AND ?
        ",
            [$userId, $userId, $userId, $periodStart, $periodEnd],
        );

        $hadirMap = [];
        foreach ($scanDates as $row) {
            $hadirMap[$row->dt] = true;
        }

        /** ===============================
         * 2️⃣ IZIN / SAKIT APPROVED SAJA
         * =============================== */
        $izinRows = DB::table("mrequest")
            ->select(DB::raw("DATE(drequest) as izin_date"), "category")
            ->where("nuserid", $userId)
            ->whereIn("category", ["izin", "sakit"])
            ->where(function ($q) {
                $q->where("cstatus", "approved")
                    ->orWhere("chrdstat", "approved")
                    ->orWhere("csuperstat", "approved");
            })
            ->whereBetween("drequest", [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->get();

        $izinMap = [];
        foreach ($izinRows as $r) {
            $izinMap[$r->izin_date] =
                strtolower($r->category) === "sakit" ? "S" : "I";
        }

        //Hitung Berdasarkan Shift Kerja User
        $workdays = DB::table("tuserschedule")
            ->where("nuserid", $userId)
            ->whereBetween("dwork", [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->pluck("dwork")
            ->toArray();

        // 3️⃣ HITUNG ABSENSI HARIAN
        $A = $I = $S = $H = 0;

        foreach ($workdays as $day) {
            if (isset($hadirMap[$day])) {
                // Hadir
                $H++;
            } elseif (isset($izinMap[$day])) {
                // Izin / Sakit
                if ($izinMap[$day] === "I") {
                    $I++;
                } elseif ($izinMap[$day] === "S") {
                    $S++;
                }
            } else {
                // Absen
                $A++;
            }
        }

        $jumlahMasuk = $H;
        $keteranganAbsensi = "A = {$A}, I = {$I}, S = {$S}";

        // Untuk menghitung bonus kehadiran
        $totalWorkdays = count($workdays);
        $bonusKehadiran = $this->calculateBonusKehadiran(
            $H,
            $I,
            $S,
            $A,
            $totalWorkdays,
        );

        //Log::info("BONUS KEHADIRAN = {$bonusKehadiran}");
        //Log::info("ATTENDANCE RESULT", compact("A", "I", "S", "H"));

        /** ===============================
         * 4️⃣ HITUNG GAJI
         * =============================== */
        $master = DB::table("mtunjangan")
            ->where("nid", $userId)
            ->whereDate("tanggal_berlaku", "<=", $periodEnd)
            ->orderByDesc("tanggal_berlaku")
            ->orderByDesc("id")
            ->first();

        if (!$master) {
            //Log::warning("SKIP payroll: no mtunjangan user={$userId}");
            return;
        }

        $jenis = strtolower($master->jenis_gaji ?? "bulanan");
        $nominal = $this->toNumber($master->nominal_gaji ?? 0);

        $gajiPokok = $jenis === "harian" ? $nominal * $jumlahMasuk : $nominal;

        $jumlahVisitLuar = $this->calculateTransportVisits(
            $userId,
            $periodStart,
            $periodEnd,
            25, // km dari dept
            500, // meter clustering
        );

        //Log::info("TRANSPORT VISIT LUAR = {$jumlahVisitLuar}");

        // HITUNG TUNJANGAN
        $tMakan = $this->toNumber($master->tunjangan_makan ?? 0) * $jumlahMasuk;
        $tJabatan = $this->toNumber($master->tunjangan_jabatan ?? 0);
        $tTransport =
            $this->toNumber($master->tunjangan_transport ?? 0) *
            $jumlahVisitLuar;
        $tLuarKota = $this->toNumber($master->tunjangan_luar_kota ?? 0);
        $tMasaKerja = $this->toNumber($master->tunjangan_masa_kerja ?? 0);
        $tBackup = $this->toNumber($master->tunjangan_backup ?? 0);

        $totalTunjangan =
            $tMakan +
            $tJabatan +
            $tTransport +
            $tLuarKota +
            $tMasaKerja +
            $tBackup;

        // TOTAL GAJI
        $totalGaji = $gajiPokok + $totalTunjangan + $bonusKehadiran;

        //Potongan Tabungan
        $potonganTabungan = 0;
        $noteTabungan = null;
        $userJoinDate = DB::table("muser")
            ->where("nid", $userId)
            ->value("dtanggalmasuk"); // ⚠️ sesuaikan nama kolom jika beda

        if ($userJoinDate) {
            $joinDate = Carbon::parse($userJoinDate)->startOfMonth();
            $currentMonth = Carbon::create($year, $month, 1)->startOfMonth();

            // bulan kerja (1-based)
            $bulanKe = $joinDate->diffInMonths($currentMonth) + 1;

            // potong 6 bulan pertama
            if ($bulanKe <= 6) {
                $potonganTabungan = 50000;
                $noteTabungan = "Potongan tabungan ke - {$bulanKe}";
            }
        }

        //Potongan Keterlambatan
        $potonganKeterlambatan = 0;

        if (!$ignoreLate) {
            $rows = DB::table("tuserschedule as us")
                ->join("mschedule as ms", "ms.nid", "=", "us.nidsched")
                ->leftJoin(
                    DB::raw('(
            SELECT nuserid, DATE(dscanned) d, MIN(TIME(dscanned)) in_time
            FROM (
                SELECT nuserid, dscanned FROM mscan
                UNION ALL
                SELECT nuserid, dscanned FROM mscan_manual
                UNION ALL
                SELECT nuserid, dscanned FROM mface_scan
            ) x
            GROUP BY nuserid, DATE(dscanned)
        ) s'),
                    function ($j) {
                        $j->on("s.nuserid", "=", "us.nuserid")->on(
                            "s.d",
                            "=",
                            "us.dwork",
                        );
                    },
                )
                ->where("us.nuserid", $userId)
                ->whereBetween("us.dwork", [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ])
                ->get();

            foreach ($rows as $r) {
                if (!$r->in_time || !$r->dstart) {
                    continue;
                }

                // ⏱️ Jam mulai shift
                $shiftStart = strtotime($r->dstart);
                $checkIn = strtotime($r->in_time);

                // ❌ TANPA TOLERANSI
                if ($checkIn > $shiftStart) {
                    $lateMinutes = floor(($checkIn - $shiftStart) / 60);
                    $potonganKeterlambatan += $lateMinutes * 1000; // 1000 / menit
                }
            }
        }

        //Logic Gaji Lembur
        $overtimeRows = DB::table("tuserschedule as us")
            ->join("mschedule as ms", "ms.nid", "=", "us.nidsched")
            ->select("us.dwork", "ms.dend", "ms.dend2")
            ->where("us.nuserid", $userId)
            ->whereBetween("us.dwork", [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->get();

        $gajiLembur = 0;

        foreach ($overtimeRows as $r) {
            // 👉 Tentukan jam selesai kerja
            $scheduledOutTime = $r->dend;
            if (!empty($r->dend2)) {
                $scheduledOutTime = $r->dend2; // shift split
            }

            // ✅ FIX: Gunakan MIN untuk ambil checkout PERTAMA setelah jam selesai
            $checkout = DB::selectOne(
                "
                SELECT MAX(dscanned) AS t
                FROM (
                    SELECT dscanned FROM mface_scan
                    WHERE nuserid=? AND DATE(dscanned)=? AND TIME(dscanned)>=?
                    UNION ALL
                    SELECT dscanned FROM mscan
                    WHERE nuserid=? AND DATE(dscanned)=? AND TIME(dscanned)>=?
                    UNION ALL
                    SELECT dscanned FROM mscan_manual
                    WHERE nuserid=? AND DATE(dscanned)=? AND TIME(dscanned)>=?
                ) x
            ",
                [
                    $userId,
                    $r->dwork,
                    $scheduledOutTime,
                    $userId,
                    $r->dwork,
                    $scheduledOutTime,
                    $userId,
                    $r->dwork,
                    $scheduledOutTime,
                ],
            );

            if (!$checkout || !$checkout->t) {
                // ⚠️ DEBUG: //Log jika tidak ada checkout
                // Log::warning(
                //     "No checkout found for user={$userId} date={$r->dwork} after={$scheduledOutTime}",
                // );
                continue;
            }

            $out = Carbon::parse($r->dwork . " " . $scheduledOutTime);
            $chk = Carbon::parse($checkout->t);

            // ✅ FIX: Hanya skip jika checkout SEBELUM jam selesai (bukan sama dengan)
            if ($chk->lt($out)) {
                continue;
            }

            $minutes = $out->diffInMinutes($chk);

            // ⚠️ DEBUG: //Log untuk tracking
            // Log::info(
            //     "OVERTIME CHECK user={$userId} date={$r->dwork} scheduled={$scheduledOutTime} checkout={$checkout->t} minutes={$minutes}",
            // );

            $jamLembur = $this->roundOvertimeMinutes($minutes);

            if ($jamLembur <= 0) {
                // Log::info(
                //     "OVERTIME ROUNDED TO ZERO user={$userId} date={$r->dwork} minutes={$minutes}",
                // );
                continue;
            }

            $rate = $this->getOvertimeRate($userName, $userDept);
            $overtimePay = $jamLembur * $rate;
            $gajiLembur += $overtimePay;

            // ✅ LOG DETAIL
            // Log::info(
            //     "OVERTIME CALCULATED user={$userId} date={$r->dwork} hours={$jamLembur} rate={$rate} pay={$overtimePay}",
            // );
        }

        //Log::info("TOTAL OVERTIME user={$userId} total={$gajiLembur}");

        $totalGaji =
            $totalGaji -
            $potonganKeterlambatan -
            $potonganTabungan +
            $gajiLembur;

        /** ===============================
         * JABATAN
         * =============================== */
        $jabatan = "Crew";
        $role = DB::table("muser")
            ->select("fadmin", "fsuper", "fsenior", "fhrd")
            ->where("nid", $userId)
            ->first();

        if ($role) {
            if ($role->fhrd) {
                $jabatan = "HRD";
            } elseif ($role->fadmin) {
                $jabatan = "Captain";
            } elseif ($role->fsuper) {
                $jabatan = "Supervisor";
            } elseif ($role->fsenior) {
                $jabatan = "Senior Crew";
            }
        }

        // 5️⃣ SIMPAN KE CSALARY
        $payload = [
            "user_id" => $userId,
            "period_year" => $year,
            "period_month" => $month,
            "jabatan" => $jabatan,
            "jumlah_masuk" => $jumlahMasuk,

            "gaji_harian" => $jenis === "harian" ? round($nominal, 2) : 0, // 🔥 penting
            "gaji_pokok" => round($gajiPokok, 2),

            "tunjangan_makan" => round($tMakan, 2),
            "tunjangan_jabatan" => round($tJabatan, 2),
            "tunjangan_transport" => round($tTransport, 2),
            "tunjangan_luar_kota" => round($tLuarKota, 2),
            "tunjangan_masa_kerja" => round($tMasaKerja, 2),
            "tunjangan_backup" => round($tBackup, 2),

            "gaji_lembur" => round($gajiLembur, 2),
            "bonus_kehadiran" => round($bonusKehadiran, 2),

            "potongan_tabungan" => round($potonganTabungan, 2),
            "potongan_keterlambatan" => round($potonganKeterlambatan, 2),

            "total_gaji" => round($totalGaji, 2),
            "keterangan_absensi" => $keteranganAbsensi,
            "note" => $noteTabungan,
            "status" => "PENDING",

            "updated_at" => now(),
        ];

        $existing = Csalary::where("user_id", $userId)
            ->where("period_year", $year)
            ->where("period_month", $month)
            ->first();

        if ($existing) {
            if (
                !$this->force &&
                in_array($existing->status, ["approved", "locked"])
            ) {
                //Log::info("SKIP payroll locked user={$userId}");
                return;
            }
            $existing->update($payload);
        } else {
            $payload["created_at"] = now();
            $payload["status"] = "PENDING";
            Csalary::create($payload);
        }

        // GENERATE SLIP GAJI PDF (FINAL SAFE VERSION)
        try {
            // Log::info(
            //     "PDF: start generate slip user={$userId} {$year}-{$month}",
            // );

            // ===== ambil salary dulu TANPA update =====
            $salary = Csalary::where([
                "user_id" => $userId,
                "period_year" => $year,
                "period_month" => $month,
            ])->first();

            if (!$salary) {
                throw new \Exception("Salary row not found");
            }

            $salary->load("user");

            $folder = public_path("hrd/slipgaji");
            File::ensureDirectoryExists($folder);

            $slug = Str::slug($salary->user->cname ?? "user");

            $filename =
                "SlipGaji_{$slug}_{$year}" .
                str_pad($month, 2, "0", STR_PAD_LEFT) .
                ".pdf";

            $path = $folder . "/" . $filename;

            //Log::info("PDF: filename={$filename}");

            // ===== generate =====
            $template = $salary->getSlipTemplate();

            $pdfBinary = Pdf::loadView($template, $salary->toSlipPdfData())
                ->setPaper("A4", "portrait")
                ->output();

            File::put($path, $pdfBinary);

            //Log::info("PDF: file saved size=" . File::size($path));

            // ===== URL FIX (hardcode domain saja biar aman) =====
            //$url = "https://absensi.matahati.my.id/laravel/public/uploads/slipgaji/{$filename}";
            $url = url("hrd/slipgaji/" . $filename);

            // ===== UPDATE HANYA KOLOM INI (anti overwrite payload lain) =====
            Csalary::where("id", $salary->id)->update([
                "pdf_url" => $url,
            ]);

            //Log::info("PDF: url updated => {$url}");
        } catch (\Throwable $e) {
            // Log::error(
            //     "PDF GENERATE FAILED user={$userId} => " . $e->getMessage(),
            //     [
            //         "trace" => $e->getTraceAsString(),
            //     ],
            // );
        }
        //Log::info("PAYROLL DONE user={$userId}");
    }

    private function roundOvertimeMinutes(int $minutes): int
    {
        if ($minutes < 30) {
            return 0;
        }
        if ($minutes < 55) {
            return 1;
        }
        return (int) ceil($minutes / 60);
    }

    private function getOvertimeRate($userName, $dept): int
    {
        //Untuk User Umay
        if (strtolower($userName) === "umay") {
            return 10000;
        }

        //Untuk Department Cafe
        if ($dept && str_contains(strtolower($dept), "cafe")) {
            return 5000;
        }

        //Untuk Department Ck
        if ($dept && str_contains(strtolower($dept), "ck")) {
            return 10000;
        }

        //Untuk Department Ck
        if ($dept && str_contains(strtolower($dept), "backoffice")) {
            return 10000;
        }

        return 0;
    }

    private function haversine($lat1, $lon1, $lat2, $lon2): float
    {
        $earth = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) *
                cos(deg2rad($lat2)) *
                sin($dLon / 2) *
                sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth * $c; // meter
    }

    private function calculateTransportVisits(
        int $userId,
        $periodStart,
        $periodEnd,
        int $thresholdKm = 25,
        int $clusterMeter = 500,
    ): int {
        // ambil dept id
        $deptId = DB::table("muser")->where("nid", $userId)->value("niddept");

        // ambil semua lokasi dept
        $deptLocations = DB::table("tdeptlokasi")
            ->where("ndeptid", $deptId)
            ->get();

        if ($deptLocations->isEmpty()) {
            return 0;
        }

        // ambil semua scan manual
        $manualScans = DB::table("mscan_manual")
            ->select("nlat", "nlng")
            ->where("nuserid", $userId)
            ->whereBetween("dscanned", [$periodStart, $periodEnd])
            ->whereNotNull("nlat")
            ->whereNotNull("nlng")
            ->get();

        $visitPoints = [];

        foreach ($manualScans as $scan) {
            // cari jarak terdekat dari semua lokasi dept
            $minDistance = PHP_INT_MAX;

            foreach ($deptLocations as $loc) {
                $d = $this->haversine(
                    $scan->nlat,
                    $scan->nlng,
                    $loc->nlat,
                    $loc->nlng,
                );
                $minDistance = min($minDistance, $d);
            }

            // kalau masih dekat kantor → skip
            if ($minDistance <= $thresholdKm * 1000) {
                continue;
            }

            // cek apakah sudah ada visit point yg dekat
            $isNewVisit = true;

            foreach ($visitPoints as $vp) {
                $d = $this->haversine(
                    $scan->nlat,
                    $scan->nlng,
                    $vp["lat"],
                    $vp["lng"],
                );

                if ($d <= $clusterMeter) {
                    $isNewVisit = false;
                    break;
                }
            }

            if ($isNewVisit) {
                $visitPoints[] = [
                    "lat" => $scan->nlat,
                    "lng" => $scan->nlng,
                ];
            }
        }

        return count($visitPoints);
    }

    private function calculateBonusKehadiran(
        int $hadir,
        int $izin,
        int $sakit,
        int $alpha,
        int $totalWorkdays,
    ): int {
        // ✅ SYARAT:
        // - hadir = semua hari kerja
        // - tidak ada izin/sakit/alpha
        if (
            $hadir === $totalWorkdays &&
            $izin === 0 &&
            $sakit === 0 &&
            $alpha === 0
        ) {
            return 100000;
        }

        return 0;
    }
}
