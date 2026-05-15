<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\muser;
use App\Models\Tusercontract;
use App\Models\MasterSchedule;
use App\Models\UserSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Jobs\CalculatePayrollJob;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserScheduleExport;
use Illuminate\Http\Request;
use DatePeriod;
use DateInterval;
use DateTime;

class ScheduleController extends Controller
{
    /**
     * 🔹 Halaman utama: Daftar shift, jadwal, dan kontrak kerja
     */
    public function index()
    {
        $authUser = auth()->user();

        // Hanya superadmin bisa lihat semua shift
        $masters = [];
        if ($authUser->fsuper == 1) {
            $masters = MasterSchedule::orderBy('cname')->get();
        }

        // Filter user berdasarkan departemen
        $query = muser::with('department')
            ->where('factive', 1)
            ->orderBy('cname');

        // ✅ HR lihat semua
        // ✅ Super & Captain hanya departemen sendiri
        if (!$authUser->fhrd) {
            $query->where('niddept', $authUser->niddept);
        }

        $users = $query->get();

        // Ambil semua kontrak kerja
        $contracts = Tusercontract::with('user')
            ->orderBy('dstart', 'desc')
            ->get();

        return view('schedule.index', compact('masters', 'users', 'authUser', 'contracts'));
    }

    /* ==========================================================
       🔸 CRUD SHIFT MASTER
    ========================================================== */

    public function store(Request $request)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'ctype' => 'required|in:normal,flexi',

            // FLEXI
            'ctotal' => 'required_if:ctype,flexi|nullable|integer|min:1|max:24',

            // NORMAL
            'dstart'  => 'required_if:ctype,normal|nullable|date_format:H:i',
            'dend'    => 'required_if:ctype,normal|nullable|date_format:H:i|after:dstart',
            'dstart2' => 'nullable|date_format:H:i',
            'dend2'   => 'nullable|date_format:H:i|after:dstart2',
        ]);

        // ================= VALIDASI KHUSUS NORMAL =================
        if ($request->ctype === 'normal') {

            if ($request->filled('dstart2') || $request->filled('dend2')) {

                if (!$request->filled('dstart2') || !$request->filled('dend2')) {
                    return back()
                        ->withErrors(['split' => 'Jam split harus diisi lengkap'])
                        ->withInput();
                }

                $s1Start = strtotime($request->dstart);
                $s1End   = strtotime($request->dend);
                $s2Start = strtotime($request->dstart2);
                $s2End   = strtotime($request->dend2);

                if ($s2End <= $s2Start) {
                    return back()
                        ->withErrors(['split' => 'Jam selesai split harus lebih besar dari jam mulai'])
                        ->withInput();
                }

                if ($s2Start < $s1End && $s2End > $s1Start) {
                    return back()
                        ->withErrors(['split' => 'Jam split tidak boleh overlap'])
                        ->withInput();
                }
            }
        }

        $ctotal = null;

        if ($request->ctype === 'flexi') {
            $ctotal = $request->ctotal;
        }

        if ($request->ctype === 'normal') {
            $ctotal = $this->calculateTotalJamNormal(
                $request->dstart,
                $request->dend,
                $request->dstart2,
                $request->dend2
            );
        }

        MasterSchedule::create([
            'cname'    => $request->cname,
            'ctype'    => $request->ctype,
            'ctotal'   => $ctotal,
            'dstart'   => $request->ctype === 'normal' ? $request->dstart : null,
            'dend'     => $request->ctype === 'normal' ? $request->dend : null,
            'dstart2'  => $request->ctype === 'normal' ? $request->dstart2 : null,
            'dend2'    => $request->ctype === 'normal' ? $request->dend2 : null,
            'dcreated' => now(),
        ]);

        return back()->with('success', '✅ Shift berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cname' => 'required|string|max:255',
            'ctype' => 'required|in:normal,flexi',

            'ctotal' => 'required_if:ctype,flexi|nullable|integer|min:1|max:24',

            'dstart'  => 'required_if:ctype,normal|nullable|date_format:H:i',
            'dend'    => 'required_if:ctype,normal|nullable|date_format:H:i|after:dstart',
            'dstart2' => 'nullable|date_format:H:i',
            'dend2'   => 'nullable|date_format:H:i|after:dstart2',
        ]);

        // 🔴 INI YANG KAMU LUPA
        $shift = MasterSchedule::findOrFail($id);

        // ================= VALIDASI SPLIT NORMAL =================
        if ($request->ctype === 'normal') {

            if ($request->filled('dstart2') || $request->filled('dend2')) {

                if (!$request->filled('dstart2') || !$request->filled('dend2')) {
                    return back()
                        ->withErrors(['split' => 'Jam split harus diisi lengkap'])
                        ->with('edit_shift_id', $id)
                        ->withInput();
                }

                $s1Start = strtotime($request->dstart);
                $s1End   = strtotime($request->dend);
                $s2Start = strtotime($request->dstart2);
                $s2End   = strtotime($request->dend2);

                if ($s2End <= $s2Start) {
                    return back()
                        ->withErrors(['split' => 'Jam split tidak valid'])
                        ->with('edit_shift_id', $id)
                        ->withInput();
                }

                if ($s2Start < $s1End && $s2End > $s1Start) {
                    return back()
                        ->withErrors(['split' => 'Jam split overlap'])
                        ->with('edit_shift_id', $id)
                        ->withInput();
                }
            }
        }

        // ================= HITUNG TOTAL JAM =================
        if ($request->ctype === 'flexi') {
            $ctotal = $request->ctotal;
        } else {
            $ctotal = $this->calculateTotalJamNormal(
                $request->dstart,
                $request->dend,
                $request->dstart2,
                $request->dend2
            );
        }

        // ================= UPDATE =================
        $shift->update([
            'cname'   => $request->cname,
            'ctype'   => $request->ctype,
            'ctotal'  => $ctotal,
            'dstart'  => $request->ctype === 'normal' ? $request->dstart : null,
            'dend'    => $request->ctype === 'normal' ? $request->dend : null,
            'dstart2' => $request->ctype === 'normal' ? $request->dstart2 : null,
            'dend2'   => $request->ctype === 'normal' ? $request->dend2 : null,
        ]);

        return back()->with('success', '✅ Shift berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $sched = MasterSchedule::findOrFail($id);
        $sched->delete();

        return redirect()->back()->with('success', '🗑 Shift berhasil dihapus.');
    }

    /* ==========================================================
       🔸 ASSIGN JADWAL PEGAWAI
    ========================================================== */

    public function showAssignForm(Request $request)
    {
        $request->validate([
            'nuserid' => 'required|exists:muser,nid',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $nuserid = $request->nuserid;
        $start = new DateTime($request->start_date);
        $end = new DateTime($request->end_date);
        $end->modify('+1 day');

        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        $masters = MasterSchedule::orderBy('cname')->get();
        $user = muser::find($nuserid);

        $existingSchedules = UserSchedule::where('nuserid', $nuserid)
            ->whereBetween('dwork', [$request->start_date, $request->end_date])
            ->pluck('nidsched', 'dwork')
            ->toArray();

        return view('schedule.assign', compact('user', 'masters', 'period', 'existingSchedules'));
    }

    public function assignSchedule(Request $request)
    {
        $request->validate([
            'nuserid' => 'required|exists:muser,nid',
            'dates'   => 'required|array'
        ]);

        foreach ($request->dates as $day => $schedId) {

            // Jika kosong → hapus
            if (empty($schedId)) {
                UserSchedule::where('nuserid', $request->nuserid)
                    ->where('dwork', $day)
                    ->delete();
                continue;
            }

            $master = MasterSchedule::find($schedId);
            if (!$master) {
                continue;
            }

            // ✅ SIMPAN 1 BARIS SAJA (ISI dstart2 & dend2)
            UserSchedule::updateOrCreate(
                [
                    'nuserid' => $request->nuserid,
                    'dwork'   => $day
                ],
                [
                    'dstart'     => $master->dstart,
                    'dend'       => $master->dend,
                    'dstart2'    => $master->dstart2, // 🔥 INI
                    'dend2'      => $master->dend2,   // 🔥 INI
                    'nidsched'   => $schedId,
                    'cschedname' => $master->cname
                ]
            );
        }

        return redirect()
            ->route('schedule.index')
            ->with('success', '✅ Jadwal berhasil disimpan.');
    }

    /* ==========================================================
       🔸 API UNTUK MOBILE
    ========================================================== */

    public function apiUserSchedule($userId)
    {
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID required'
            ], 400);
        }

        $rows = UserSchedule::where('nuserid', $userId)
            ->orderBy('dwork', 'asc')
            ->orderBy('dstart', 'asc')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // 🔹 GROUP PER TANGGAL
        $grouped = $rows->groupBy('dwork');

        $data = $grouped->map(function ($items, $date) {

            $sessions = [];

            foreach ($items as $row) {

                // Session 1 (WAJIB)
                if ($row->dstart && $row->dend) {
                    $sessions[] = [
                        'start' => substr($row->dstart, 0, 5),
                        'end'   => substr($row->dend, 0, 5),
                    ];
                }

                // Session 2 (OPSIONAL – SPLIT)
                if ($row->dstart2 && $row->dend2) {
                    $sessions[] = [
                        'start' => substr($row->dstart2, 0, 5),
                        'end'   => substr($row->dend2, 0, 5),
                    ];
                }
            }

            return [
                'date'      => $date,
                'shiftName' => $items->first()->cschedname,
                'sessions'  => $sessions
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function apiTodayShift($userId)
    {
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID required'
            ], 400);
        }

        // Pakai timezone lokal
        $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();

        // Ambil SATU baris (karena sekarang 1 row = 1 hari)
        $row = UserSchedule::where('nuserid', $userId)
            ->where('dwork', $today)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        $sessions = [];

        // ✅ Session 1 (WAJIB)
        if ($row->dstart && $row->dend) {
            $sessions[] = [
                'start' => substr($row->dstart, 0, 5),
                'end'   => substr($row->dend, 0, 5),
            ];
        }

        // ✅ Session 2 (OPSIONAL – SPLIT)
        if ($row->dstart2 && $row->dend2) {
            $sessions[] = [
                'start' => substr($row->dstart2, 0, 5),
                'end'   => substr($row->dend2, 0, 5),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date'      => $today,
                'shiftName' => $row->cschedname,
                'sessions'  => $sessions
            ]
        ]);
    }

    public function storeContract(Request $request)
    {
        $request->validate([
            'nuserid'       => 'required|exists:muser,nid',
            'dstart'        => 'required|date',
            'dend'          => 'required|date|after:dstart',
            'dstart2'       => 'nullable|date',
            'dend2'         => 'nullable|date|after:dstart2',
            'nterm'         => 'required|in:3,6,12',
            'ctermtype'     => 'required|in:probation,promotion,evaluation',
            'cnotes'        => 'nullable|string|max:255',
        ]);

        // Clean nominal
        $nominal = preg_replace('/[^0-9]/', '', $request->nominal_gaji) ?: 0;
        $nominal = (float)$nominal;

        // Nonaktifkan kontrak aktif sebelumnya
        \DB::table('tusercontract')
            ->where('nuserid', $request->nuserid)
            ->where('cstatus', 'active')
            ->update(['cstatus' => 'terminated']);

        // Insert kontrak baru
        $contract = Tusercontract::create([
            'nuserid'      => $request->nuserid,
            'dstart'       => $request->dstart,
            'dend'         => $request->dend,
            'nterm'        => $request->nterm,
            'ctermtype'    => $request->ctermtype,
            'cnotes'       => $request->cnotes,
        ]);

        $year  = now()->year;
        $month = now()->month;

        // Hitung payroll SEKETIKA (tanpa queue)
        (new CalculatePayrollJob(
            $contract->nuserid,
            $year,
            $month,
            true // force overwrite
        ))->handle();

        return redirect()
            ->route('schedule.index')
            ->with('success', 'Kontrak kerja berhasil ditambahkan dan payroll tersinkron!');
    }

    // Update kontrak
    public function updateContract(Request $request, $id)
    {
        $contract = Tusercontract::findOrFail($id);

        $request->validate([
            'nuserid'      => 'required|exists:muser,nid',
            'dstart'       => 'required|date',
            'dend'         => 'required|date|after:dstart',
            'nterm'        => 'required|in:3,6,12',
            'ctermtype'    => 'required|in:probation,promotion,evaluation',
            'cnotes'       => 'nullable|string|max:255',
        ]);

        // Clean nominal
        $nominal = preg_replace('/[^0-9]/', '', $request->nominal_gaji) ?: 0;
        $nominal = (float)$nominal;

        // Update kontrak
        $contract->update([
            'nuserid'      => $request->nuserid,
            'dstart'       => $request->dstart,
            'dend'         => $request->dend,
            'nterm'        => $request->nterm,
            'ctermtype'    => $request->ctermtype,
            'cnotes'       => $request->cnotes,
        ]);

        $year  = now()->year;
        $month = now()->month;

        // Hitung payroll langsung
        (new CalculatePayrollJob(
            $contract->nuserid,
            $year,
            $month,
            true
        ))->handle();

        return redirect()
            ->route('schedule.index')
            ->with('success', 'Kontrak berhasil diperbarui & payroll tersinkron!');
    }
    // Hapus kontrak
    public function destroyContract($id)
    {
        $contract = Tusercontract::findOrFail($id);
        $contract->delete();

        return redirect()->route('schedule.index')->with('success', 'Kontrak kerja berhasil dihapus!');
    }

    //Contract calendar fun
    public function contractCalendar(Request $request)
    {
        $contracts = Tusercontract::with('user')
            ->where('cstatus', 'active')
            ->get();

        return response()->json(
            $contracts->map(function ($c) {
                return [
                    'id'    => $c->nid,
                    'title' => $c->user->cname . ' (' . ucfirst($c->ctermtype) . ')',
                    'start' => $c->dend->toDateString(), // ⬅️ SATU TANGGAL SAJA
                    'allDay' => true,
                    'color' => $this->contractColor($c),
                ];
            })
        );
    }

    public function contractByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        return response()->json(
            Tusercontract::with('user')
                ->whereDate('dend', $request->date)
                ->where('cstatus', 'active')
                ->get()
                ->map(fn ($c) => [
                    'name' => $c->user->cname ?? '-',
                    'type' => ucfirst($c->ctermtype),
                ])
        );
    }

    private function contractColor($c)
    {
        if ($c->remaining_days <= 0) {
            return '#dc3545';
        } // merah
        if ($c->remaining_days <= 30) {
            return '#fd7e14';
        } // oranye

        return match ($c->ctermtype) {
            'probation'  => '#ffc107',
            'promotion'  => '#0dcaf0',
            default      => '#6c757d',
        };
    }

    // helper Overlap
    private function timeOverlap($startA, $endA, $startB, $endB)
    {
        return $startA < $endB && $endA > $startB;
    }

    private function calculateTotalJamNormal(
        string $dstart,
        string $dend,
        ?string $dstart2 = null,
        ?string $dend2 = null
    ): int {
        $totalMinutes = 0;

        // session 1
        $start1 = Carbon::createFromFormat('H:i', $dstart);
        $end1   = Carbon::createFromFormat('H:i', $dend);
        $totalMinutes += $start1->diffInMinutes($end1);

        // session 2 (split)
        if ($dstart2 && $dend2) {
            $start2 = Carbon::createFromFormat('H:i', $dstart2);
            $end2   = Carbon::createFromFormat('H:i', $dend2);
            $totalMinutes += $start2->diffInMinutes($end2);
        }

        return (int) floor($totalMinutes / 60);
    }

    // Export jadwal ke Excel
    public function exportSchedule(Request $request)
    {
        $request->validate([
            'nuserid' => 'required|exists:muser,nid',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = new \DateTime($request->start_date);
        $end   = new \DateTime($request->end_date);
        $end->modify('+1 day');

        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);

        // ambil schedule existing
        $schedules = UserSchedule::where('nuserid', $request->nuserid)
            ->whereBetween('dwork', [$request->start_date, $request->end_date])
            ->get()
            ->keyBy('dwork'); // 🔥 biar cepat lookup

        $result = [];

        foreach ($period as $date) {
            $tgl = $date->format('Y-m-d');
            $row = $schedules->get($tgl);

            if ($row) {
                $shift = $row->cschedname;

                // session 1
                if ($row->dstart && $row->dend) {
                    $shift .= ' (' . substr($row->dstart, 0, 5) . ' - ' . substr($row->dend, 0, 5);
                }

                // session 2 (split)
                if ($row->dstart2 && $row->dend2) {
                    $shift .= ' | ' . substr($row->dstart2, 0, 5) . ' - ' . substr($row->dend2, 0, 5);
                }

                $shift .= ')';
            } else {
                $shift = '-';
            }

            $result[] = [
                'tanggal' => $date->format('d/m/Y'),
                'shift'   => $shift
            ];
        }

        $user = muser::find($request->nuserid);

        return Excel::download(
            new UserScheduleExport($result),
            'jadwal-' . str_replace(' ', '-', strtolower($user->cname)) . '.xlsx'
        );
    }

}
