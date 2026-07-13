<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Csalary;
use App\Models\Tusercontract;
use App\Models\muser;
use App\Models\Mtunjangan;
use App\Models\Mrekening;
use App\Models\mdepartment;
use App\Jobs\CalculatePayrollJob;
use Illuminate\Http\Request;
use App\Mail\SlipKirimGaji;
use Carbon\Carbon;
use DB;

class PayrollCalculationController extends Controller
{
    public function recalcAll(Request $request)
    {
        Log::info("========== PAYROLL RECALC START ==========");

        // ✅ LOG request mentah
        Log::info("RAW REQUEST", $request->all());

        $request->validate([
            "year" => "nullable|integer",
            "month" => "nullable|integer|min:1|max:12",
            "split_by_change" => "nullable|in:0,1",
            "department_id" => "nullable|integer",
        ]);

        $year = (int) ($request->year ?? now()->year);
        $month = (int) ($request->month ?? now()->month);

        Log::info("PERIOD SELECTED", [
            "year" => $year,
            "month" => $month,
        ]);

        // =========================
        // HANDLE DEPARTMENT
        // =========================

        $departmentIdRaw = $request->input("department_id");

        Log::info("DEPARTMENT RAW INPUT", [
            "raw_value" => $departmentIdRaw,
            "type" => gettype($departmentIdRaw),
        ]);

        $departmentId = null;

        if (
            !is_null($departmentIdRaw) &&
            trim((string) $departmentIdRaw) !== "" &&
            is_numeric($departmentIdRaw)
        ) {
            $departmentId = (int) $departmentIdRaw;
        }

        Log::info("DEPARTMENT PARSED ID", [
            "departmentId" => $departmentId,
        ]);

        // =========================
        // CONVERT TO NID
        // =========================

        $departmentNid = null;

        if ($departmentId !== null) {
            $dept = mdepartment::find($departmentId);

            if ($dept) {
                $departmentNid = $dept->nid;

                Log::info("DEPARTMENT CONVERSION SUCCESS", [
                    "input_id" => $departmentId,
                    "name" => $dept->cname,
                    "nid" => $departmentNid,
                ]);
            } else {
                Log::error("DEPARTMENT NOT FOUND", [
                    "department_id" => $departmentId,
                ]);

                return redirect()
                    ->back()
                    ->withErrors([
                        "department_id" => "Department tidak ditemukan",
                    ])
                    ->withInput();
            }
        }

        // =========================
        // QUERY RSALARY
        // =========================

        $rsQuery = DB::table("rsalary")
            ->join("muser", "muser.nid", "=", "rsalary.user_id")
            ->where("muser.factive", 1)
            ->where("rsalary.period_year", $year)
            ->where("rsalary.period_month", $month);

        if ($departmentNid !== null) {
            Log::info("APPLY DEPARTMENT FILTER TO RSALARY", [
                "niddeptpayroll" => $departmentNid,
            ]);

            $rsQuery->where("muser.niddeptpayroll", $departmentNid);
        }

        $rsExists = $rsQuery->exists();

        Log::info("RSALARY EXISTS?", [
            "exists" => $rsExists,
        ]);

        // =========================
        // USER QUERY
        // =========================

        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $userQuery = DB::table("mtunjangan")
            ->join("muser", "muser.nid", "=", "mtunjangan.nid")
            ->where("muser.factive", 1)
            ->whereDate("mtunjangan.tanggal_berlaku", "<=", $periodEnd);

        if ($departmentNid !== null) {
            Log::info("APPLY DEPARTMENT FILTER TO USER QUERY", [
                "niddeptpayroll" => $departmentNid,
            ]);

            $userQuery->where("muser.niddeptpayroll", $departmentNid);
        }

        $userIds = $userQuery
            ->select("mtunjangan.nid")
            ->distinct()
            ->pluck("mtunjangan.nid")
            ->toArray();

        Log::info("USER IDS FOR RECALC", [
            "count" => count($userIds),
            "ids" => $userIds,
        ]);

        // =========================
        // LOOP PAYROLL
        // =========================

        foreach ($userIds as $uid) {
            Log::info("PROCESS USER PAYROLL", ["uid" => $uid]);

            (new CalculatePayrollJob(
                (int) $uid,
                $year,
                $month,
                $request->has("recalculate"),
                $request->input("split_by_change") == "1",
            ))->handle();
        }

        // =========================
        // REDIRECT
        // =========================

        $redirectParams = [
            "year" => $year,
            "month" => $month,
        ];

        if ($departmentId !== null) {
            $redirectParams["department_id"] = $departmentId;
        }

        Log::info("REDIRECT PARAMS", $redirectParams);

        Log::info("========== PAYROLL RECALC END ==========");

        return redirect()
            ->route("penggajian.index", $redirectParams)
            ->with(
                "success",
                "Perhitungan payroll selesai (hanya karyawan aktif).",
            );
    }

    public function recalcAjax(Request $req)
    {
        $req->validate([
            "department_id" => "nullable|integer",
            "user_ids.*" => "integer",
            "period_month" => "required|numeric|min:1|max:12",
            "period_year" => "required|integer",
        ]);

        $departmentNid = $req->input("department_id");

        $userQuery = DB::table("muser")->where("factive", 1);

        if ($departmentNid) {
            $userQuery->where("niddeptpayroll", $departmentNid);
        }

        $userIds = $userQuery->pluck("nid")->toArray();

        $month = (int) $req->input("period_month");
        $year = (int) $req->input("period_year");

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // jika bulan berjalan, batasi sampai hari ini
        $now = Carbon::now();
        $loopEnd =
            $now->year === $year && $now->month === $month
                ? $now->endOfDay()
                : $end;

        \Log::info("recalcAjax START", [
            "users" => $userIds,
            "period" => "$year-$month",
            "start" => $start->toDateString(),
            "loopEnd" => $loopEnd->toDateString(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Ambil SEMUA SCAN (mscan + manual + face)
        |--------------------------------------------------------------------------
        */
        $scans = collect()
            ->merge(
                DB::table("mscan")
                    ->selectRaw("nuserid, DATE(dscanned) as ddate")
                    ->whereIn("nuserid", $userIds)
                    ->whereBetween(DB::raw("DATE(dscanned)"), [
                        $start,
                        $loopEnd,
                    ])
                    ->groupBy("nuserid", DB::raw("DATE(dscanned)"))
                    ->get(),
            )
            ->merge(
                DB::table("mscan_manual")
                    ->selectRaw("nuserId as nuserid, DATE(dscanned) as ddate")
                    ->whereIn("nuserId", $userIds)
                    ->whereBetween(DB::raw("DATE(dscanned)"), [
                        $start,
                        $loopEnd,
                    ])
                    ->groupBy("nuserId", DB::raw("DATE(dscanned)"))
                    ->get(),
            )
            ->merge(
                DB::table("mface_scan")
                    ->selectRaw("nuserId as nuserid, DATE(dscanned) as ddate")
                    ->whereIn("nuserId", $userIds)
                    ->whereBetween(DB::raw("DATE(dscanned)"), [
                        $start,
                        $loopEnd,
                    ])
                    ->groupBy("nuserId", DB::raw("DATE(dscanned)"))
                    ->get(),
            );

        // presentMap[user_id][date] = true
        $presentMap = [];
        foreach ($scans as $row) {
            $uid = (int) $row->nuserid;
            $ds = $row->ddate;
            $presentMap[$uid][$ds] = true;
        }

        // Ambil REQUEST (izin / sakit) yang approved
        $requests = DB::table("mrequest")
            ->selectRaw("nuserid, DATE(drequest) as ddate, category")
            ->whereIn("nuserid", $userIds)
            ->whereBetween(DB::raw("DATE(drequest)"), [$start, $loopEnd])
            ->where(function ($q) {
                $q->whereRaw("LOWER(IFNULL(cstatus,''))='approved'")
                    ->orWhereRaw("LOWER(IFNULL(chrdstat,''))='approved'")
                    ->orWhereRaw("LOWER(IFNULL(csuperstat,''))='approved'");
            })
            ->get();

        $requestMap = [];
        foreach ($requests as $r) {
            $cat = strtolower($r->category ?? "izin");
            if (str_contains($cat, "sakit")) {
                $requestMap[$r->nuserid][$r->ddate] = "sakit";
            } else {
                $requestMap[$r->nuserid][$r->ddate] = "izin";
            }
        }

        // =========================
        // AMBIL JADWAL KERJA USER
        // =========================
        $scheduleMap = [];

        $schedules = DB::table("user_schedule")
            ->whereIn("nuserid", $userIds)
            ->whereBetween("dwork", [$start, $loopEnd])
            ->get();

        foreach ($schedules as $s) {
            $uid = (int) $s->nuserid;
            $ds = Carbon::parse($s->dwork)->toDateString();
            $scheduleMap[$uid][$ds] = true;
        }

        // LOOP PER USER
        $results = [];

        foreach ($userIds as $uid) {
            $A = $I = $S = 0;

            $workdays = array_keys($scheduleMap[$uid] ?? []);

            foreach ($workdays as $ds) {
                if (!empty($presentMap[$uid][$ds])) {
                    continue; // hadir
                }

                if (!empty($requestMap[$uid][$ds])) {
                    $requestMap[$uid][$ds] === "sakit" ? $S++ : $I++;
                    continue;
                }

                // 🔥 BARU BOLEH ALPHA
                $A++;
            }

            $jumlahMasuk = count($presentMap[$uid] ?? []);

            $ket = "A = $A, I = $I, S = $S";

            DB::table("csalary")
                ->where("user_id", $uid)
                ->where("period_year", $year)
                ->where("period_month", $month)
                ->update([
                    "jumlah_masuk" => $jumlahMasuk,
                    "keterangan_absensi" => $ket,
                    "updated_at" => now(),
                ]);
        }

        \Log::info("recalcAjax DONE", ["count" => count($results)]);

        return response()->json(["results" => $results]);
    }

    public function getUserSalary($userId, Request $request)
    {
        $targetUser = muser::find($userId);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        $ccompany = $this->resolveCcompany($request);
        if ($ccompany && $targetUser->ccompany !== $ccompany) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses ke user ini.'
            ], 403);
        }

        $year = $request->get("year");
        $month = $request->get("month");

        $query = Csalary::where("user_id", $userId);

        if ($year) {
            $query->where("period_year", $year);
        }

        if ($month) {
            $query->where("period_month", $month);
        }

        $rows = $query
            ->orderByDesc("period_year")
            ->orderByDesc("period_month")
            ->get();

        $data = $rows->map(function ($gaji) {
            return [
                "id" => $gaji->id,
                "period_year" => $gaji->period_year,
                "period_month" => $gaji->period_month,
                "jabatan" => $gaji->jabatan,
                "jumlah_masuk" => $gaji->jumlah_masuk,
                "status" => $gaji->status,
                "note" => $gaji->user_note,

                // 🔥 PENGHASILAN DINAMIS
                "penghasilan" => [
                    ["label" => "Gaji Pokok", "value" => $gaji->gaji_pokok],
                    [
                        "label" => "Tunjangan Makan",
                        "value" => $gaji->tunjangan_makan,
                    ],
                    [
                        "label" => "Tunjangan Jabatan",
                        "value" => $gaji->tunjangan_jabatan,
                    ],
                    [
                        "label" => "Tunjangan Transport",
                        "value" => $gaji->tunjangan_transport,
                    ],
                    [
                        "label" => "Tunjangan Luar Kota",
                        "value" => $gaji->tunjangan_luar_kota,
                    ],
                    [
                        "label" => "Tunjangan Masa Kerja",
                        "value" => $gaji->tunjangan_masa_kerja,
                    ],
                    [
                        "label" => "Tunjangan Backup",
                        "value" => $gaji->tunjangan_backup,
                    ],
                    ["label" => "Gaji Lembur", "value" => $gaji->gaji_lembur],
                    [
                        "label" => "Bonus Kehadiran",
                        "value" => $gaji->bonus_kehadiran,
                    ], // 🔥 BARU
                    [
                        "label" => "Tabungan Diambil",
                        "value" => $gaji->tabungan_diambil,
                    ],
                ],

                // 🔥 POTONGAN DINAMIS
                "potongan" => [
                    [
                        "label" => "Keterlambatan",
                        "value" => $gaji->potongan_keterlambatan,
                    ],
                    ["label" => "Lain-Lain", "value" => $gaji->potongan_lain],
                    [
                        "label" => "Tabungan",
                        "value" => $gaji->potongan_tabungan,
                    ],
                ],

                "total_gaji" => $gaji->total_gaji,
            ];
        });

        return response()->json([
            "success" => true,
            "data" => $data,
        ]);
    }

    public function updateSalaryStatus(Request $request)
    {
        $request->validate([
            "user_id" => "required|integer",
            "year" => "required|integer",
            "month" => "required|integer",
            "status" => "required|in:APPROVED,REJECTED",
            "note" => "nullable|string",
        ]);

        $ccompany = $this->resolveCcompany($request);

        $salary = Csalary::where("user_id", $request->user_id)
            ->where("period_year", $request->year)
            ->where("period_month", $request->month)
            ->first();

        if (!$salary) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Data salary tidak ditemukan",
                ],
                404,
            );
        }

        $salary->load('user');
        if ($ccompany && $salary->user && $salary->user->ccompany !== $ccompany) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses ke data salary ini.'
            ], 403);
        }

        $salary->status = $request->status;

        // kalau reject simpan alasan
        if ($request->status === "REJECTED") {
            $salary->user_note = $request->note;
        } else {
            $salary->user_note = null;
        }

        $salary->user_updated_at = now();
        $salary->save();

        /*
        ========================================
        🔥 KIRIM EMAIL OTOMATIS JIKA APPROVED
        ========================================
        */
        if ($request->status === "APPROVED") {
            $salary->load("user");

            $user = $salary->user;

            if ($user && $user->cmailaddress && $salary->pdf_url) {
                try {
                    $filename = basename($salary->pdf_url);
                    $path = public_path("karyatrahrd/slipgaji/" . $filename);

                    Mail::to($user->cmailaddress)->send(
                        new SlipKirimGaji($salary, $path),
                    );

                    $salary->update([
                        "email_status" => "SENT",
                        "email_sent_at" => now(),
                    ]);
                } catch (\Throwable $e) {
                    $salary->update(["email_status" => "FAILED"]);

                    Log::error("APPROVE EMAIL ERROR: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            "success" => true,
            "message" => "Status berhasil diupdate",
        ]);
    }

    public function resendEmail($uid, $year, $month)
    {
        $salary = Csalary::where([
            "user_id" => $uid,
            "period_year" => $year,
            "period_month" => $month,
        ])->firstOrFail();

        $salary->load("user");

        try {
            $filename = basename($salary->pdf_url);
            $path = public_path("karyatrahrd/slipgaji/" . $filename);

            Mail::to($salary->user->cmailaddress)->send(
                new SlipKirimGaji($salary, $path),
            );

            $salary->update([
                "email_status" => "RESENT",
                "email_sent_at" => now(),
            ]);

            return back()->with("success", "Email berhasil dikirim ulang");
        } catch (\Throwable $e) {
            $salary->update([
                "email_status" => "FAILED",
            ]);

            Log::error("RESEND ERROR: " . $e->getMessage());

            Log::error($e->getMessage());

            return back()->with("error", "Email gagal dikirim");
        }
    }

    private function resolveCcompany(Request $request)
    {
        if ($request->filled('ccompany')) {
            return $request->input('ccompany');
        }
        if ($request->header('X-Company')) {
            return $request->header('X-Company');
        }

        $user = Auth::user() ?? Auth::guard('owner')->user();

        if (!$user) {
            $userId = $request->input('user_id')
                ?: $request->input('admin_id')
                ?: $request->input('creator_id')
                ?: $request->input('added_by')
                ?: $request->input('approver_id');

            if ($userId) {
                $user = muser::find($userId);
            }
        }

        if (!$user && $request->header('X-User-Id')) {
            $user = muser::find($request->header('X-User-Id'));
        }

        return $user ? $user->ccompany : null;
    }
}
