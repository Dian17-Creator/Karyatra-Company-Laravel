<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\muser;
use App\Models\mscan;
use App\Models\mrequest;
use App\Models\mdepartment;
use App\Models\Mrekening;
use App\Models\AdminDevice;
use App\Models\Tdeptlokasi;
use App\Models\Mcompany;
use App\Models\Mowner;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BackofficeController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user() ?? Auth::guard('owner')->user();
        $query = muser::with(['department', 'rekening'])->orderBy('cname');

        if ($authUser && $authUser->ccompany) {
            $query->where('ccompany', $authUser->ccompany);
        }

        if (!$authUser->fhrd) {
            $query->where('niddept', $authUser->niddept);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('cname', 'like', "%{$keyword}%")
                    ->orWhere('cemail', 'like', "%{$keyword}%")
                    ->orWhere('cfullname', 'like', "%{$keyword}%")
                    ->orWhereHas('department', function ($q2) use ($keyword) {
                        $q2->where('cname', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('dept')) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('cname', $request->dept);
            });
        }

        $status = $request->input('status', '1');
        if ($status !== null && $status !== '') {
            $query->where('factive', $status);
        }

        $users = $query->paginate(10)->withQueryString();

        $departmentsQuery = mdepartment::orderBy('nid');
        if ($authUser && $authUser->ccompany) {
            $departmentsQuery->where('ccompany', $authUser->ccompany);
        }
        $departments = $departmentsQuery->get();

        $rekeningsQuery = Mrekening::orderBy('id');
        if ($authUser && $authUser->ccompany) {
            $rekeningsQuery->where('ccompany', $authUser->ccompany);
        }
        $rekenings = $rekeningsQuery->get();

        $user = Auth::user() ?? Auth::guard('owner')->user();
        $deptLocationsQuery = Tdeptlokasi::with('department')
            ->orderBy('ndeptid', 'asc');

        if ($user && $user->ccompany) {
            $deptLocationsQuery->where('ccompany', $user->ccompany);
        }

        $deptLocations = $deptLocationsQuery->paginate(10, ['*'], 'dept_page')
            ->withQueryString();

        if ($request->ajax()) {
            if ($request->header('X-Component') === 'master_deptlokasi') {
                return view('backoffice.component.master_deptlokasi', compact('deptLocations', 'departments'))->render();
            }

            return view('backoffice.component.master_user', compact(
                'users',
                'departments',
                'rekenings'
            ))->render();
        }

        $devices = \App\Models\AdminDevice::with('user')
            ->orderByDesc('created_at')
            ->get();

        $adminsQuery = muser::where(function ($q) {
            $q->where('fadmin', 1)
                ->orWhere('fsuper', 1)
                ->orWhere('fhrd', 1);
        });
        if ($authUser && $authUser->ccompany) {
            $adminsQuery->where('ccompany', $authUser->ccompany);
        }
        $admins = $adminsQuery->orderBy('cname')->get();

        // Ambil data company milik user yang login
        $company = Mcompany::where('cname', $authUser->ccompany)->first();

        return view('backoffice.index', compact(
            'users',
            'departments',
            'rekenings',
            'devices',
            'admins',
            'deptLocations',
            'company'
        ));
    }

    public function storeUser(Request $request)
    {
        if (Auth::user()->fhrd != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menambah user.');
        }

        $request->merge([
            'fnotif' => $request->input('fnotif', 0)
        ]);

        $request->validate([
            'email'          => 'required|unique:muser,cemail',
            'name'           => 'required|string|max:255',
            'cfullname'      => 'nullable|string|max:255',
            'password'       => 'required|min:3',
            'niddept'        => 'required|exists:mdepartment,nid',
            'niddeptpayroll' => 'nullable|exists:mdepartment,nid',
            'cmailaddress'   => 'nullable|email|max:100|unique:muser,cmailaddress',
            'caccnumber'     => 'nullable|string|max:50',
            'cphone'         => 'nullable|string|max:20',
            'cktp'           => 'nullable|string|max:20',
            'finger_id'      => 'nullable|integer|unique:muser,finger_id',
            'dtanggalmasuk'  => 'nullable|date',
            'rekening_id'    => 'nullable|exists:mrekening,id',
            'bank'           => 'nullable|in:BCA,BRI,Mandiri',
            'fnotif'         => 'required|in:0,1'
        ]);

        $role = $request->input('role', 'crew');

        $user = muser::create([
            'cemail'         => $request->email,
            'cmailaddress'   => $request->input('cmailaddress'),
            'cphone'         => $request->input('cphone'),
            'caccnumber'     => $request->input('caccnumber'),
            'cname'          => $request->name,
            'cfullname'      => $request->input('cfullname'),
            'cktp'           => $request->input('cktp'),
            'cpassword'      => Hash::make($request->password),
            'fadmin'         => $role === 'fadmin' ? 1 : 0,
            'fsuper'         => $role === 'fsuper' ? 1 : 0,
            'fsenior'        => $role === 'fsenior' ? 1 : 0,
            'fhrd'           => 0,
            'factive'        => 1,
            'fnotif'         => (int)$request->input('fnotif', 0),
            'niddept'        => $request->niddept,
            'niddeptpayroll' => $request->input('niddeptpayroll'),
            'dcreated'       => now(),
            'finger_id'      => $request->input('finger_id') ?: null,
            'ccompany'       => Auth::user() ? Auth::user()->ccompany : null,
        ]);

        $bankInput     = $request->input('bank');
        $rekeningInput = $request->input('rekening_id');
        $accNumberRaw  = $request->input('caccnumber');
        $accNumber     = $accNumberRaw ? preg_replace('/\D+/', '', (string)$accNumberRaw) : null;

        $user->bank = $bankInput ? trim($bankInput) : null;
        $user->rekening_id = null;

        if ($bankInput) {

            $bankNormalized = trim($bankInput);

            if (strtolower($bankNormalized) === 'mandiri') {

                $user->rekening_id = $rekeningInput ? intval($rekeningInput) : null;
                $user->bank = 'Mandiri';
            } else {

                if ($accNumber) {

                    $rek = Mrekening::whereRaw('LOWER(bank)=?', [strtolower($bankNormalized)])
                        ->where('nomor_rekening', $accNumber)
                        ->first();

                    if ($rek) {
                        $user->rekening_id = $rek->id;
                    }
                }

                $user->bank = $bankNormalized;
            }
        }

        $user->save();

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, int $id)
    {
        if (Auth::user()->fhrd != 1) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit user.');
        }

        DB::transaction(function () use ($request, $id) {

            $user = muser::findOrFail($id);

            $oldActive = (int)$user->factive;

            $request->validate([
                'email'          => 'required|unique:muser,cemail,' . $user->nid . ',nid',
                'name'           => 'required|string|min:3|max:255',
                'cfullname'      => 'nullable|string|max:255',
                'password'       => 'nullable|min:4',
                'niddept'        => 'required|exists:mdepartment,nid',
                'niddeptpayroll' => 'nullable|exists:mdepartment,nid',
                'cmailaddress'   => 'nullable|email|max:100|unique:muser,cmailaddress,' . $user->nid . ',nid',
                'cphone'         => 'nullable|string|max:20',
                'cktp'           => 'nullable|string|max:20',
                'caccnumber'     => 'nullable|string|max:50',
                'finger_id'      => 'nullable|integer|unique:muser,finger_id,' . $user->nid . ',nid',
                'dtanggalmasuk'  => 'nullable|date',
                'rekening_id'    => 'nullable|exists:mrekening,id',
                'bank'           => 'nullable|in:BCA,BRI,Mandiri',
                'factive'        => 'nullable|in:0,1',
                'fnotif'         => 'required|in:0,1'
            ]);

            $user->cemail         = $request->email;
            $user->cmailaddress   = $request->input('cmailaddress');
            $user->caccnumber     = $request->input('caccnumber');
            $user->cphone         = $request->input('cphone');
            $user->cktp           = $request->input('cktp');
            $user->cname          = $request->name;
            $user->cfullname      = $request->input('cfullname');
            $user->niddept        = $request->niddept;
            $user->niddeptpayroll = $request->input('niddeptpayroll');
            $user->dtanggalmasuk  = $request->input('dtanggalmasuk');

            $user->fadmin  = $request->role === 'fadmin' ? 1 : 0;
            $user->fsuper  = $request->role === 'fsuper' ? 1 : 0;
            $user->fsenior = $request->role === 'fsenior' ? 1 : 0;

            $user->finger_id = $request->input('finger_id') ?: null;

            $user->factive = (int)$request->input('factive', 0);
            $user->fnotif  = (int)$request->input('fnotif', 0);

            $newActive = (int)$user->factive;

            $bankInput     = $request->input('bank');
            $rekeningInput = $request->input('rekening_id');
            $accNumberRaw  = $request->input('caccnumber');
            $accNumber     = $accNumberRaw ? preg_replace('/\D+/', '', $accNumberRaw) : null;

            $user->bank = $bankInput ? trim($bankInput) : null;
            $user->rekening_id = null;

            if ($bankInput) {

                $bankNormalized = trim($bankInput);

                if (strtolower($bankNormalized) === 'mandiri') {

                    $user->bank = 'Mandiri';
                    $user->rekening_id = $rekeningInput ? (int)$rekeningInput : null;
                } else {

                    if ($accNumber) {

                        $rek = Mrekening::whereRaw('LOWER(bank)=?', [strtolower($bankNormalized)])
                            ->where('nomor_rekening', $accNumber)
                            ->first();

                        $user->rekening_id = $rek ? $rek->id : null;
                    }

                    $user->bank = $bankNormalized;
                }
            }

            if (!empty($request->password)) {
                $user->cpassword = Hash::make($request->password);
            }

            if ($oldActive === 1 && $newActive === 0) {

                Log::warning("USER DEACTIVATED → CLEAR CSALARY nid={$user->nid}");

                DB::table('csalary')
                    ->where('user_id', $user->nid)
                    ->delete();
            }

            $user->save();
        });

        return back()->with('success', 'Data user berhasil diperbarui.');
    }

    public function updateCompany(Request $request)
    {
        if (Auth::user()->fsuper != 1) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah data company.');
        }

        $request->validate([
            'cname'  => 'required|string|max:255',
            'cemail' => 'required|string|max:255',
        ]);

        $authUser = Auth::user();
        $company  = Mcompany::where('cname', $authUser->ccompany)->firstOrFail();

        $oldCname = $company->cname;

        $company->update([
            'cname'  => $request->cname,
            'cemail' => $request->cemail,
        ]);

        // Sinkronisasi ccompany di muser & mowner jika nama company berubah
        if ($oldCname !== $request->cname) {
            muser::where('ccompany', $oldCname)->update(['ccompany' => $request->cname]);
            Mowner::where('ccompany', $oldCname)->update(['ccompany' => $request->cname]);
        }

        return back()->with('success', 'Data company berhasil diperbarui.');
    }

    public function addDepartment(Request $request)
    {
        if (Auth::user()->fsuper != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menambah departemen.');
        }

        $user = Auth::user() ?? Auth::guard('owner')->user();

        $request->validate([
            'cname' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('mdepartment', 'cname')->where(function ($query) use ($user) {
                    return $query->where('ccompany', $user ? $user->ccompany : null);
                })
            ],
        ]);

        mdepartment::create([
            'cname' => $request->cname,
            'ccompany' => $user ? $user->ccompany : null,
        ]);

        return back()->with('success', 'Departemen berhasil ditambahkan.');
    }
    public function updateDepartment(Request $request, int $id)
    {
        if (Auth::user()->fsuper != 1) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah departemen.');
        }

        $request->validate([
            'cname' => 'required|string|max:255',
        ]);

        $dept = mdepartment::findOrFail($id);
        $dept->update(['cname' => $request->cname]);

        return back()->with('success', 'Departemen berhasil diperbarui.');
    }
    public function deleteDepartment(Request $request)
    {
        if (Auth::user()->fsuper != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus departemen.');
        }

        $dept = mdepartment::findOrFail($request->dept_id);

        // Cegah penghapusan jika masih ada user di departemen ini
        if (muser::where('niddept', $dept->nid)->exists()) {
            return back()->with('error', 'Tidak dapat menghapus departemen karena masih ada user di dalamnya.');
        }

        $dept->delete();

        return back()->with('success', 'Departemen berhasil dihapus.');
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
    public function deleteRequests(Request $request)
    {
        if (Auth::user()->fsuper != 1) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus request.');
        }

        $userId = $request->user_id;
        $requests = mrequest::where('nuserId', $userId)->get();

        foreach ($requests as $req) {
            if ($req->cphoto_path && file_exists(public_path($req->cphoto_path))) {
                unlink(public_path($req->cphoto_path));
            }
            $req->delete();
        }

        return back()->with('success', 'Request user berhasil dihapus.');
    }
    public function viewLogs(int $userId)
    {
        $date = request('date');

        $startDate = request('start_date') ?? $date ?? now()->subMonth()->toDateString();
        $endDate   = request('end_date') ?? $date ?? now()->toDateString();
        $sort = request('sort', 'desc');
        $source = request('source');   // forgot / manual / scan / face
        $status = request('status');   // pending / approved / rejected

        // =========================
        // 🔹 Ambil data dari mscan
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
                'mscan.cplacename', // tambahkan cplacename agar konsisten
                DB::raw('NULL as ciswifi')
            )
            ->where('mscan.nuserId', $userId)
            ->whereBetween('mscan.dscanned', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get();

        // =========================
        // 🔹 Ambil data dari mscan_manual
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
                'mscan_manual.status', // ✅ ambil status MANUAL/FORGOT
                'muser.cname',
                'muser.cname',
                'muser.fadmin',
                'muser.fhrd',
                'muser.fsuper',
                DB::raw('0 as fmanual'),

                // 🔥 PENTING → source sekarang dari status
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
        // 🔹 Ambil data dari mface_scan (face logs)
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

        // 🔹 Gabungkan semua hasil dan urutkan berdasarkan tanggal
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

        // 🔹 Ambil info user
        $user = \App\Models\muser::findOrFail($userId);

        // 🔹 Pagination manual
        $page = request('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $paginatedLogs = new \Illuminate\Pagination\LengthAwarePaginator(
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
    public function viewRequests(Request $request, int $userId)
    {
        $user = muser::findOrFail($userId);
        $sort = $request->get('sort', 'desc'); // default terbaru dulu

        // ✅ Ambil semua kolom termasuk cplacename
        $requests = mrequest::with('user')
            ->select(
                'nid',
                'nuserid',
                'drequest',
                'fadmreq',
                'nlat',
                'nlng',
                'cplacename',
                'creason',
                'cphoto_path',
                'cstatus',
                'csuperstat',
                'chrdstat',
                'dcreated',
                'cdevstring'
            )
            ->where('nuserid', $user->nid)
            ->orderBy('drequest', $sort)
            ->paginate(10);

        return view('backoffice.requests', compact('user', 'requests', 'sort'));
    }
    public function viewRequestcard(Request $request, int $userId)
    {
        $user = muser::findOrFail($userId);
        $sort = $request->get('sort', 'desc'); // default terbaru dulu

        // ✅ Ambil semua kolom termasuk cplacename
        $requests = mrequest::select(
            'nid',
            'nuserid',
            'drequest',
            'fadmreq',
            'nlat',
            'nlng',
            'cplacename',
            'creason',
            'cphoto_path',
            'cstatus',
            'csuperstat',
            'chrdstat',
            'dupsuper',
            'duphrd',
            'dcreated',
            'cdevstring'
        )
            ->where('nuserid', $user->nid)
            ->orderBy('drequest', $sort)
            ->paginate(10);

        return view('backoffice.partial.requestcard', compact('user', 'requests', 'sort'));
    }
    public function apiLogs(int $userId)
    {
        try {
            $startDate = request('start_date', now()->subMonth()->toDateString());
            $endDate   = request('end_date', now()->toDateString());

            // =======================
            // SCAN (mscan)
            // =======================
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

            // =======================
            // MANUAL (mscan_manual)
            // =======================
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

            // =======================
            // FACE (mface_scan)
            // =======================
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

            // =======================
            // GABUNGKAN SEMUA
            // =======================
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

        $sheet = $sheets[3];
        $inserted = 0;

        foreach ($sheet->skip(1) as $row) {
            $fingerId   = $row[0]; // kolom A: ID
            $tanggalRaw = $row[3]; // kolom D: Tgl
            $jamInRaw   = $row[4] ?? null; // kolom E
            $jamOutRaw  = $row[5] ?? null; // kolom F

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

            // 🔴 kalau tanggal ini sudah punya scan, lewati
            $alreadyHasScan = mscan::where('nuserId', $user->nid)
                ->whereDate('dscanned', $tanggal)
                ->exists();

            if ($alreadyHasScan) {
                continue; // lanjut ke baris berikutnya di Excel
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
                        'nuserId'    => $user->nid,
                        'nkioskId'   => 0,
                        'ntokenId'   => $IMPORT_TOKEN_ID,
                        'dscanned'   => $scanIn,
                        'nlat'       => null,
                        'nlng'       => null,
                        'cplacename' => null,
                        'fmanual'    => 0,
                        'nadminid'   => null,
                        'creason'    => null,
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
                        'nuserId'    => $user->nid,
                        'nkioskId'   => 0,
                        'ntokenId'   => $IMPORT_TOKEN_ID,
                        'dscanned'   => $scanOut,
                        'nlat'       => null,
                        'nlng'       => null,
                        'cplacename' => null,
                        'fmanual'    => 0,
                        'nadminid'   => null,
                        'creason'    => null,
                        'cphoto_path' => null,
                    ]);
                    $inserted++;
                }
            }
        }

        return back()->with('success', "Import fingerprint selesai. Scan baru ditambahkan: {$inserted}.");
    }

    public function apiStoreUser(Request $request)
    {
        // if (!Auth::check() || Auth::user()->fhrd != 1) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Anda tidak memiliki izin untuk menambah user.'
        //     ], 403);
        // }

        $request->merge([
            'fnotif' => $request->input('fnotif', 0)
        ]);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email'          => 'required|unique:muser,cemail',
            'name'           => 'required|string|max:255',
            'cfullname'      => 'nullable|string|max:255',
            'password'       => 'required|min:3',
            'niddept'        => 'required|exists:mdepartment,nid',
            'niddeptpayroll' => 'nullable|exists:mdepartment,nid',
            'cmailaddress'   => 'nullable|email|max:100|unique:muser,cmailaddress',
            'caccnumber'     => 'nullable|string|max:50',
            'cphone'         => 'nullable|string|max:20',
            'cktp'           => 'nullable|string|max:20',
            'finger_id'      => 'nullable|integer|unique:muser,finger_id',
            'dtanggalmasuk'  => 'nullable|date',
            'rekening_id'    => 'nullable|exists:mrekening,id',
            'bank'           => 'nullable|in:BCA,BRI,Mandiri',
            'fnotif'         => 'required|in:0,1',
            'creator_id'     => 'nullable|exists:muser,nid',
            'admin_id'       => 'nullable|exists:muser,nid',
            'approver_id'    => 'nullable|exists:muser,nid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $role = $request->input('role', 'crew');

            $ccompany = $this->resolveCcompany($request);

            $user = muser::create([
                'cemail'         => $request->email,
                'cmailaddress'   => $request->input('cmailaddress'),
                'cphone'         => $request->input('cphone'),
                'caccnumber'     => $request->input('caccnumber'),
                'cname'          => $request->name,
                'cfullname'      => $request->input('cfullname'),
                'cktp'           => $request->input('cktp'),
                'cpassword'      => Hash::make($request->password),
                'dtanggalmasuk'  => $request->input('dtanggalmasuk'),
                'fadmin'         => $role === 'fadmin' ? 1 : 0,
                'fsuper'         => $role === 'fsuper' ? 1 : 0,
                'fsenior'        => $role === 'fsenior' ? 1 : 0,
                'fhrd'           => 0,
                'factive'        => 1,
                'fnotif'         => (int)$request->input('fnotif', 0),
                'niddept'        => $request->niddept,
                'niddeptpayroll' => $request->input('niddeptpayroll'),
                'dcreated'       => now(),
                'finger_id'      => $request->input('finger_id') ?: null,
                'ccompany'       => $ccompany,
            ]);

            $bankInput     = $request->input('bank');
            $rekeningInput = $request->input('rekening_id');
            $accNumberRaw  = $request->input('caccnumber');
            $accNumber     = $accNumberRaw ? preg_replace('/\D+/', '', (string)$accNumberRaw) : null;

            $user->bank = $bankInput ? trim($bankInput) : null;
            $user->rekening_id = null;

            if ($bankInput) {

                $bankNormalized = trim($bankInput);

                if (strtolower($bankNormalized) === 'mandiri') {

                    $user->rekening_id = $rekeningInput ? intval($rekeningInput) : null;
                    $user->bank = 'Mandiri';
                } else {

                    if ($accNumber) {

                        $rek = Mrekening::whereRaw('LOWER(bank)=?', [strtolower($bankNormalized)])
                            ->where('nomor_rekening', $accNumber)
                            ->first();

                        if ($rek) {
                            $user->rekening_id = $rek->id;
                        }
                    }

                    $user->bank = $bankNormalized;
                }
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User berhasil ditambahkan.',
                'data'    => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function apiDepartmentList(Request $request)
    {
        $ccompany = $this->resolveCcompany($request);
        $query = Mdepartment::orderBy('cname');
        if ($ccompany) {
            $query->where('ccompany', $ccompany);
        }
        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function apiBankList(Request $request)
    {
        $ccompany = $this->resolveCcompany($request);
        $query = Mrekening::select('bank');
        if ($ccompany) {
            $query->where('ccompany', $ccompany);
        }
        $banks = $query->distinct()->pluck('bank');

        return response()->json([
            'success' => true,
            'data' => $banks
        ]);
    }

    public function apiMandiriRekening(Request $request)
    {
        $ccompany = $this->resolveCcompany($request);
        $query = Mrekening::where('bank', 'Mandiri')->orderBy('nomor_rekening');
        if ($ccompany) {
            $query->where('ccompany', $ccompany);
        }
        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    private function resolveCcompany(Request $request)
    {
        // 1. Check direct ccompany in request parameter or headers
        if ($request->filled('ccompany')) {
            return $request->input('ccompany');
        }
        if ($request->header('X-Company')) {
            return $request->header('X-Company');
        }

        // 2. Check Auth user (standard or owner guard)
        $user = Auth::user() ?? Auth::guard('owner')->user();

        // 3. Fallback: check query/post parameters for user/admin ID
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

        // 4. Fallback: check request headers for user ID
        if (!$user && $request->header('X-User-Id')) {
            $user = muser::find($request->header('X-User-Id'));
        }

        return $user ? $user->ccompany : null;
    }
}
