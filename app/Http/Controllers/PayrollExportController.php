<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Csalary;
use App\Models\muser;
use App\Models\mdepartment;
use App\Models\Mrekening;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollExport;
use App\Exports\PayrollMultiSheetExport;
use App\Exports\PayrollExportMandiriExcel;
use App\Exports\PayrollExportBca;
use App\Exports\PayrollExportBri;

class PayrollExportController extends Controller
{
    public function exportMandiriExcel(Request $req)
    {
        $period = $req->input('period') ?: Carbon::now()->format('Y-m');
        [$year, $month] = explode('-', $period);

        $year  = (int) $year;
        $month = (int) $month;

        $query = Csalary::where('period_year', $year)
            ->where('period_month', $month)
            ->with('user');

        // ==========================
        // 🔥 FILTER DEPARTMENT (STRICT)
        // ==========================
        if ($req->filled('department_id')) {

            $depId = trim((string)$req->input('department_id'));

            $userIds = muser::where('niddept', $depId)
                ->pluck('nid')
                ->toArray();

            // kalau tidak ada user → paksa kosong
            if (empty($userIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('user_id', $userIds);
            }
        }

        $data = $query->get();

        $routeUrl = route('payroll.mandiri.csv', [
            'period' => $period,
            'department_id' => $req->input('department_id')
        ]);

        $dateYmd = Carbon::now()->format('Ymd');

        $export = new PayrollExportMandiriExcel(
            $data,
            '1710007401451',
            'BANK MANDIRI',
            '15786538',
            $dateYmd,
            $routeUrl
        );

        $filename = "mandiri_payroll_{$period}.xlsx";

        return Excel::download($export, $filename);
    }

    /**
     * ==========================
     * EXPORT CSV (MANDIRI)
     * ==========================
     */
    public function exportMandiriCsv(Request $req)
    {
        $period = $req->input('period') ?: Carbon::now()->format('Y-m');
        [$year, $month] = explode('-', $period);

        $year  = (int) $year;
        $month = (int) $month;

        $query = Csalary::where('period_year', $year)
            ->where('period_month', $month)
            ->with('user');

        // ==========================
        // 🔥 FILTER DEPARTMENT (STRICT)
        // ==========================
        if ($req->filled('department_id')) {

            $depId = trim((string)$req->input('department_id'));

            $userIds = muser::where('niddept', $depId)
                ->pluck('nid')
                ->toArray();

            // kalau tidak ada user → hasil kosong
            if (empty($userIds)) {
                return response()->streamDownload(function () {
                    $handle = fopen('php://output', 'w');
                    fclose($handle);
                }, 'mandiri_empty.csv', [
                    'Content-Type' => 'text/csv',
                ]);
            }

            $query->whereIn('user_id', $userIds);
        }

        $data = $query->get();

        $dateYmd = Carbon::now()->format('Ymd');

        $export = new PayrollExportMandiriExcel(
            $data,
            '1710007401451',
            'BANK MANDIRI',
            null,
            $dateYmd
        );

        $rows = $export->toMandiriCsvArray();

        $filename = "mandiri_payroll_{$dateYmd}.csv";

        return response()->streamDownload(function () use ($rows) {

            $handle = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fwrite($handle, implode(',', $row) . "\n");
            }

            fclose($handle);

        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportExcel(Request $request)
    {
        // Accept either: bulan=YYYY-MM (from modal_report_gaji) OR month & year (old behavior)
        $bulanInput = $request->input('bulan', null);
        if ($bulanInput && preg_match('/^\d{4}-\d{2}$/', $bulanInput)) {
            [$year, $month] = explode('-', $bulanInput);
            $year = (int) $year;
            $month = (int) $month;
        } else {
            $month = (int) $request->input('month', now()->month);
            $year  = (int) $request->input('year', now()->year);
        }

        // source table (csalary / rsalary)
        $source = strtolower((string) $request->input('source_table', 'csalary'));
        $modelClass = $source === 'rsalary' ? Rsalary::class : Csalary::class;
        $source = $source === 'rsalary' ? 'rsalary' : 'csalary';

        // parse selected_ids (CSV, JSON array, or array)
        $selectedRaw = $request->input('selected_ids', null);
        $selectedIds = [];
        if ($selectedRaw !== null && trim((string)$selectedRaw) !== '') {
            if (is_string($selectedRaw)) {
                $decoded = json_decode($selectedRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $selectedIds = array_values(array_filter($decoded, function ($v) {
                        return is_numeric($v) && intval($v) > 0;
                    }));
                } else {
                    $selectedIds = array_values(array_filter(array_map('trim', explode(',', $selectedRaw)), function ($v) {
                        return is_numeric($v) && intval($v) > 0;
                    }));
                }
            } elseif (is_array($selectedRaw)) {
                $selectedIds = array_values(array_filter($selectedRaw, function ($v) {
                    return is_numeric($v) && intval($v) > 0;
                }));
            }
        }

        // base query for chosen model
        $query = $modelClass::with('user')
            ->where('period_year', $year)
            ->where('period_month', $month);

        // if explicit selected ids -> restrict by those csalary/rsalary ids
        if (count($selectedIds) > 0) {
            $query->whereIn('id', $selectedIds);
        } else {
            // department filter if provided
            $departmentInput = $request->input('department_id', null);
            if (!is_null($departmentInput) && trim((string)$departmentInput) !== '') {
                $userIds = [];

                if (is_numeric($departmentInput)) {
                    // numeric -> treat as mdepartment.nid (or id)
                    $depVal = intval($departmentInput);
                    $dep = mdepartment::where('nid', $depVal)->orWhere('id', $depVal)->first();
                    if ($dep) {
                        // muser.niddept likely stores dep->nid (or dep->id depending on your db)
                        // try both possibilities: nid then id
                        $userIds = muser::where('niddept', $dep->nid ?? $dep->id)->pluck('nid')->toArray();
                    } else {
                        // fallback: assume muser.niddept equals given numeric
                        $userIds = muser::where('niddept', $depVal)->pluck('nid')->toArray();
                    }
                } else {
                    // string -> may be cname, code, or directly the value stored in muser.niddept
                    $dep = mdepartment::where('cname', $departmentInput)
                        ->orWhere('code', $departmentInput)
                        ->first();

                    if ($dep) {
                        $userIds = muser::where('niddept', $dep->nid ?? $dep->id)->pluck('nid')->toArray();
                    } else {
                        // fallback: treat departmentInput as direct muser.niddept value (e.g. 'CK')
                        $userIds = muser::where('niddept', $departmentInput)->pluck('nid')->toArray();
                    }
                }

                if (empty($userIds)) {
                    return back()->with('error', 'Tidak ada karyawan pada departemen yang dipilih.');
                }

                $query->whereIn('user_id', $userIds);
            }
        }

        // get rows
        $data = $query->get();

        // debug
        \Log::info('exportExcel called', [
            'source' => $source,
            'year' => $year, 'month' => $month,
            'selected_count' => count($selectedIds),
            'rows' => $data->count(),
            'department' => $request->input('department_id', null),
        ]);

        if ($data->count() === 0) {
            return back()->with('error', 'Tidak ada data untuk periode / filter yang dipilih.');
        }

        // produce filename and download using existing PayrollExport
        $fileName = "ReportGaji-{$month}-{$year}.xlsx";
        return Excel::download(
            new PayrollExport($data, $month, $year),
            $fileName
        );
    }

    public function exportReport(Request $request)
    {
        $request->validate([
            'bulan' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'department_id' => ['nullable'],
            'selected_ids' => ['nullable'],
        ]);

        // ===============================
        // PARSE PERIODE
        // ===============================
        [$year, $month] = explode('-', $request->input('bulan'));
        $year  = (int) $year;
        $month = (int) $month;

        // ===============================
        // 🔥 AMBIL CHECKBOX (PRIORITAS UTAMA)
        // ===============================
        $selectedIds = json_decode($request->input('selected_ids', '[]'), true);

        if (!is_array($selectedIds)) {
            $selectedIds = [];
        }

        // ===============================
        // DEPARTMENT (untuk HEADER + fallback filter)
        // ===============================
        $departmentInput = $request->input('department_id', null);
        $departmentName  = null;
        $userIds         = [];

        if (!empty($departmentInput)) {

            // numeric -> nid
            if (is_numeric($departmentInput)) {
                $dep = mdepartment::where('nid', intval($departmentInput))->first();

                if ($dep) {
                    $departmentNameRaw = trim((string)$dep->cname);

                    $userIds = muser::where('niddeptpayroll', $dep->nid)->pluck('nid')->toArray();

                } else {
                    $departmentNameRaw = (string)$departmentInput;

                    $userIds = muser::where('niddeptpayroll', intval($departmentInput))->pluck('nid')->toArray();

                }

            } else {
                // string -> cname / code
                $dep = mdepartment::where('cname', $departmentInput)
                    ->orWhere('code', $departmentInput)
                    ->first();

                if ($dep) {
                    $departmentNameRaw = trim((string)$dep->cname);

                    $userIds = muser::where('niddeptpayroll', $dep->nid)->pluck('nid')->toArray();

                } else {
                    $departmentNameRaw = trim((string)$departmentInput);

                    $userIds = muser::where('niddeptpayroll', $departmentInput)->pluck('nid')->toArray();

                }
            }

            // ===== HEADER EXCEL NAME =====
            $rawUpper = strtoupper($departmentNameRaw ?? '');

            if ($rawUpper === 'CK' || $rawUpper === 'CENTRAL KITCHEN') {
                $departmentName = 'CENTRAL KITCHEN MATAHATI CAFE';
            } else {
                $departmentName = $rawUpper !== ''
                    ? $rawUpper . ' MATAHATI CAFE'
                    : null;
            }
        }

        // ===============================
        // BUILD QUERY
        // ===============================
        $query = Csalary::with('user')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->orderBy('user_id');

        // 🔥🔥🔥 PRIORITAS CHECKBOX DULU
        if (!empty($selectedIds)) {

            // checkbox = csalary.id
            $query->whereIn('id', $selectedIds);

        }
        // fallback department
        elseif (!empty($userIds)) {

            $query->whereIn('user_id', $userIds);

        }

        $data = $query->get();

        if ($data->count() === 0) {
            return back()->with('error', 'Tidak ada data payroll untuk periode / filter yang dipilih.');
        }

        // ===============================
        // EXPORT
        // ===============================
        $fileName = sprintf('Report-Gaji-%04d-%02d.xlsx', $year, $month);

        // ======================================
        // JIKA SEMUA DEPARTMENT
        // ======================================

        if (empty($departmentInput)) {

            // ambil semua department sekali saja
            $departments = mdepartment::pluck('cname', 'nid')->toArray();

            // group berdasarkan id department payroll
            $grouped = $data->groupBy(function ($item) {
                return $item->user->niddeptpayroll ?? 0;
            });

            $sheets = [];

            foreach ($grouped as $deptId => $rows) {

                $rawName = strtoupper($departments[$deptId] ?? 'OTHER');

                // nama sheet pendek
                switch ($rawName) {

                    case 'BACKOFFICE':
                        $sheetName = 'Backoffice';
                        $header = 'BACKOFFICE MATAHATI CAFE';
                        break;

                    case 'CAFE PANGGUL':
                        $sheetName = 'Cafe Panggul';
                        $header = 'CAFE PANGGUL MATAHATI CAFE';
                        break;

                    case 'CAFE TA':
                        $sheetName = 'Cafe TA';
                        $header = 'CAFE TA MATAHATI CAFE';
                        break;

                    case 'CK':
                    case 'CENTRAL KITCHEN':
                        $sheetName = 'Central Kitchen';
                        $header = 'CENTRAL KITCHEN MATAHATI CAFE';
                        break;

                    case 'IT':
                        $sheetName = 'IT';
                        $header = 'IT MATAHATI CAFE';
                        break;

                    case 'MARKETING':
                        $sheetName = 'Marketing';
                        $header = 'MARKETING MATAHATI CAFE';
                        break;

                    default:
                        $sheetName = ucfirst(strtolower($rawName));
                        $header = $rawName . ' MATAHATI CAFE';
                }

                $sheets[$sheetName] = [
                    'rows' => $rows,
                    'header' => $header
                ];
            }

            return Excel::download(
                new PayrollMultiSheetExport($sheets, $month, $year),
                $fileName
            );
        }

        // ======================================
        // JIKA DEPARTMENT TERTENTU
        // ======================================

        return Excel::download(
            new PayrollExport($data, $month, $year, $departmentName),
            $fileName
        );
    }

    public function exportBank(Request $req)
    {
        // validasi dasar (department_id & selected_ids bersifat optional/tolerant)
        $req->validate([
            'bulan'        => 'required',
            'bank'         => 'required|string',
            'payroll_date' => 'nullable|date',
            'mrekening_id' => 'nullable|exists:mrekening,id',
            'department_id' => 'nullable',
            'selected_ids'  => 'nullable',
        ]);

        // parse periode YYYY-MM
        $bulan = $req->input('bulan');
        if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            return back()->with('error', 'Format periode tidak valid. Gunakan YYYY-MM.');
        }
        [$year, $month] = explode('-', $bulan);
        $year = (int) $year;
        $month = (int) $month;

        // payroll_date (default ke tanggal 5)
        $defaultDay = 5;
        if ($req->filled('payroll_date')) {
            try {
                $payrollDate = Carbon::parse($req->input('payroll_date'));
            } catch (\Exception $e) {
                $payrollDate = Carbon::create($year, $month, $defaultDay);
            }
        } else {
            $payrollDate = Carbon::create($year, $month, $defaultDay);
        }

        // normalize bank & mandiri requirement
        $bankInput = (string) $req->input('bank', '');
        $bankLower = strtolower(trim($bankInput));
        $mrekeningId = $req->input('mrekening_id') ? intval($req->input('mrekening_id')) : null;
        if ($bankLower === 'mandiri' && !$mrekeningId) {
            return back()->with('error', 'Silakan pilih Rekening Sumber untuk Mandiri.');
        }

        // base query: csalary for period
        $query = Csalary::with(['user', 'user.rekening'])
            ->where('period_year', $year)
            ->where('period_month', $month);

        // --- support explicit selected_ids (client may send CSV or JSON array) ---
        $selectedRaw = $req->input('selected_ids', null);
        $selectedIds = [];
        if ($selectedRaw !== null && trim((string)$selectedRaw) !== '') {
            if (is_string($selectedRaw)) {
                $decoded = json_decode($selectedRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $selectedIds = array_values(array_filter($decoded, function ($v) {
                        return is_numeric($v) && intval($v) > 0;
                    }));
                } else {
                    $selectedIds = array_values(array_filter(array_map('trim', explode(',', $selectedRaw)), function ($v) {
                        return is_numeric($v) && intval($v) > 0;
                    }));
                }
            } elseif (is_array($selectedRaw)) {
                $selectedIds = array_values(array_filter($selectedRaw, function ($v) {
                    return is_numeric($v) && intval($v) > 0;
                }));
            }
        }

        if (count($selectedIds) > 0) {
            // jika ada selected ids, batasi hasil berdasarkan id csalary
            $query->whereIn('id', $selectedIds);
        } else {
            // no explicit selected_ids → gunakan department filter jika dikirim
            $departmentInput = $req->input('department_id', null);

            if (!is_null($departmentInput) && trim((string)$departmentInput) !== '') {

                $userIds = [];

                if (is_numeric($departmentInput)) {
                    // department_id numeric → itu adalah mdepartment.nid
                    $depVal = intval($departmentInput);

                    // pastikan departemen ada
                    $dep = mdepartment::where('nid', $depVal)->first();

                    if ($dep) {
                        $userIds = muser::where('niddeptpayroll', $dep->nid)->pluck('nid')->toArray();
                    } else {
                        $userIds = muser::where('niddeptpayroll', $depVal)->pluck('nid')->toArray();
                    }
                } else {
                    $dep = mdepartment::where('cname', $departmentInput)
                        ->orWhere('code', $departmentInput)
                        ->first();

                    if ($dep) {
                        $userIds = muser::where('niddeptpayroll', $dep->nid)->pluck('nid')->toArray();
                    } else {
                        $userIds = muser::where('niddeptpayroll', $departmentInput)->pluck('nid')->toArray();
                    }
                }

                if (empty($userIds)) {
                    return back()->with('error', 'Tidak ada karyawan pada departemen yang dipilih.');
                }

                $query->whereIn('user_id', $userIds);
            }

        }

        if ($bankLower === 'mandiri') {
            $query->whereHas('user.rekening', function ($q) use ($mrekeningId) {
                $q->where('id', $mrekeningId);
            });
        } else {
            $query->where(function ($qb) use ($bankLower) {
                $qb->whereHas('user', function ($qUser) use ($bankLower) {
                    // use whereRaw inside whereHas so 'muser' is referenced in the subquery (safe)
                    $qUser->whereRaw('LOWER(bank) LIKE ?', ["%{$bankLower}%"]);
                });

                $qb->orWhereHas('user.rekening', function ($qRek) use ($bankLower) {
                    $qRek->whereRaw('LOWER(bank) LIKE ?', ["%{$bankLower}%"]);
                });
            });
        }

        // ambil data
        $data = $query->get();

        if ($data->count() === 0) {
            return back()->with('error', 'Tidak ada data payroll yang sesuai kriteria (periode / bank / rekening / departemen).');
        }

        // ---------- export sesuai bank ----------
        if ($bankLower === 'bca') {
            $fileName = "payroll_bca_{$month}-{$year}.xlsx";
            return Excel::download(
                new PayrollExportBca($data, $month, $year, $payrollDate),
                $fileName
            );
        }

        if ($bankLower === 'mandiri') {
            $companyAccount = '';
            $companyAlias = '';
            $reference = '';

            if ($mrekeningId) {
                $rek = Mrekening::find($mrekeningId);
                if ($rek) {
                    $companyAlias = 'BANK MANDIRI';
                    $companyAccount = $rek->nomor_rekening ?: ($rek->nomor ?? '');
                }
            }

            // build bankList — (ambil dari implementasi lama / helper)
            $bankLines = <<<'TXT'
                BANK INDONESIA|BI|INDOIDJA|0010016
                PT. BANK RAKYAT INDONESIA (PERSERO)|BRI|BRINIDJA|0020307
                PT. BANK MANDIRI (PERSERO) TBK|BANK MANDIRI|BMRIIDJA|0080017
                PT. BANK NEGARA INDONESIA (PERSERO)|BANK BNI|BNINIDJA|0090010
                PT. BANK DANAMON INDONESIA Tbk.|BANK DANAMON|BDINIDJA|0110042
                PT. BANK DANAMON INDONESIA UNIT USAHA SYARIAH|BANK DANAMON UUS|SYBDIDJ1|0119920
                PT. BANK PERMATA,TBK|BANK PERMATA|BBBAIDJA|0130475
                PT. BANK PERMATA,TBK UNIT USAHA SYARIAH|BANK PERMATA UUS|SYBBIDJ1|0139926
                PT. BANK CENTRAL ASIA Tbk.|BCA|CENAIDJA|0140397
                PT. BANK MAYBANK INDONESIA Tbk.|BANK MAYBANK|IBBKIDJA|0160131
                PT. BANK MAYBANK INDONESIA Tbk. UNIT USAHA SYARIAH|BANK MAYBANK UUS|SYBKIDJ1|0169925
                PT. PANIN BANK Tbk.|PANIN BANK|PINBIDJA|0190017
                PT. BANK CIMB NIAGA TBK|BANK CIMB|BNIAIDJA|0220026
                PT. BANK CIMB NIAGA TBK - UNIT USAHA SYARIAH|BANK CIMB NIAGA UUS|SYNAIDJ1|0229920
                PT. BANK UOB INDONESIA|UOB INDONESIA|BBIJIDJA|0230016
                PT. BANK OCBC NISP, Tbk.|BANK OCBC NISP|NISPIDJA|0280024
                PT.BANK OCBC NISP TBK - UNIT USAHA SYARIAH|BANK OCBC NISP - UUS|SYONIDJ1|0289928
                CITIBANK, NA|CITIBANK|CITIIDJX|0310305
                KC JPMORGAN CHASE BANK, N.A|JPMORGAN BANK|CHASIDJX|0320308
                BANK OF AMERICA NA|BOA|BOFAID2X|0330301
                PT. BANK WINDU KENTJANA INTERNASIONAL, TBK|BANK WINDU KENTJANA|MCORIDJA|0360300
                PT. BANK ARTHA GRAHA INTERNASIONAL, TBK|BAG INTERNASIONAL|ARTGIDJA|0370028
                PT. BANK SUMITOMO MITSUI INDONESIA|SUMITOMO|SUNIIDJA|0450304
                PT. BANK DBS INDONESIA|DBS|DBSBIDJA|0460307
                PT. BANK RESONA PERDANIA|BANK RESONA|BPIAIDJA|0470300
                PT. BANK MIZUHO INDONESIA|BANK MIZUHO|MHCCIDJA|0480303
                STANDARD CHARTERED BANK|STANDCHARD|SCBLIDJX|0500306
                PT. BANK BTPN|BTPN|TAPEIDJ1|2130101
                PT. BANK VICTORIA SYARIAH|BANK VICTORIASYARIAH|SWAGIDJ1|4050072
                PT. BANK SYARIAH BRI|SYARIAH BRI|DJARIDJ1|4220051
                PT. BANK MEGA Tbk.|BANK MEGA|MEGAIDJA|4260121
                PT BANK BNI SYARIAH|BNI SYARIAH|SYNIIDJ1|4270027
                PT. BANK BUKOPIN Tbk.|BUKOPIN|BBUKIDJA|4410010
                PT. BANK SYARIAH MANDIRI Tbk.|BSM|BSMDIDJA|4510017
                PT. BANK BISNIS INTERNASIONAL|BANK BISNIS|BUSTIDJ1|4590011
                PT. BANK ANDARA|BANK ANDARA|RIPAIDJ1|4660019
                PT. BANK JASA JAKARTA|BANK JASA JAKARTA|JSABIDJ1|4720014
                PT. BANK KEB HANA INDONESIA|BANK KEB HANA|HNBNIDJA|4840017
                PT. BANK MNC INTERNASIONAL, TBK|MNC BANK|BUMIIDJA|4850010
                PT. BANK YUDHA BHAKTI|BANK YUDHA BHAKTI|YUDBIDJ1|4900012
                PT. BANK MITRANIAGA|BANK MITRANIAGA|MGABIDJ1|4910015
                PT. BANK RAKYAT INDONESIA AGRONIAGA, TBK|AGRONIAGA|AGTBIDJA|4940014
                PT. BANK SBI INDONESIA|BANK SBI|IDMOIDJ1|4980016
                PT. BANK ROYAL INDONESIA|BANK ROYAL|ROYBIDJ1|5010011
                PT. BANK NATIONALNOBU|BANK NATIONALNOBU|LFIBIDJ1|5030017
                PT. BANK MEGA SYARIAH|BANK MEGA SYARIAH|BUTGIDJ1|5060016
                PT. BANK INA PERDANA|BANK INA|INPBIDJ1|5130014
                PT. BANK PANIN SYARIAH|BANK PANIN SYARIAH|ARFAIDJ1|5170016
                PT. PRIMA MASTER BANK|PRIMA MASTER|PMASIDJ1|5200012
                PT. BANK SYARIAH BUKOPIN|BANK SYARIAH BUKOPIN|SDOBIDJ1|5210031
                PT. BANK SAHABAT SAMPOERNA|BANK SAMPOERNA|BDIPIDJ1|5230011
                PT. BANK DINAR INDONESIA|BANK DINAR|LMANIDJ1|5260010
                PT. BANK AMAR INDONESIA|BANK AMAR|LOMAIDJA|5310012
                PT. BANK KESEJAHTERAAN EKONOMI|BANK KESEJAHTERAAN|KSEBIDJ1|5350014
                PT. BANK BCA SYARIAH|BANK BCA SYARIAH|SYCAIDJ1|5360017
                PT. BANK ARTOS INDONESIA|BANK ARTOS|ATOSIDJA|5420012
                PT. BANK TABUNGAN PENSIUNAN NASIONAL SYARIAH|BANK BTPN SYARIAH|PUBAIDJ1|5470046
                PT. BANK MULTI ARTA SENTOSA|Bank MAS|MASBIDJ1|5480010
                PT. BANK MAYORA|BANK MAYORA|MAYOIDJA|5530012
                PT. BANK PUNDI INDONESIA, TBK|BANK PUNDI|EKSTIDJ1|5580017
                PT. CENTRATAMA NASIONAL BANK|BANK CNB|CNBAIDJ1|5590036
                PT. BANK FAMA INTERNATIONAL|BANK FAMA|FAMAIDJA|5620016
                PT. BANK MANDIRI TASPEN POS|BANK MANDIRI TASPEN POS|SIHBIDJ1|5640012
                PT. BANK VICTORIA INTERNATIONAL|BANK VICTORIA|VICTIDJ1|5660018
                PT. BANK HARDA INTERNATIONAL|BANK HARDA|HRDAIDJ1|5670011
                PT. BANK AGRIS|BANK AGRIS|AGSSIDJA|9450305
                PT. BANK MAYBANK SYARIAH INDONESIA|MAYBANK SYARIAH|MBBEIDJA|9470302
                PT. BANK CTBC INDONESIA|CTBC INDONESIA|CTCBIDJA|9490307
                PT. BANK COMMONWEALTH|BANK COMMONWEALTH|BICNIDJA|9500307
            TXT;

            $bankList = [];
            foreach (preg_split('/\r\n|\n|\r/', trim($bankLines)) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) < 4) {
                    continue;
                }
                $bankList[] = [
                    'nama' => $parts[0],
                    'singkat' => $parts[1],
                    'bic' => $parts[2],
                    'kode' => $parts[3],
                ];
            }

            $export = new \App\Exports\MultiPayrollMandiriExport(
                $data,
                $bankList,
                $companyAccount,
                $companyAlias,
                $reference,
                $payrollDate->format('Ymd'),
                route('payroll.mandiri.csv', [
                    'period' => sprintf('%04d-%02d', $year, $month),
                    'department_id' => $req->input('department_id')
                ]),
                (int)$payrollDate->format('d'),
                $year,
                $month
            );

            $fileName = "payroll_mandiri_{$month}-{$year}.xlsx";
            return Excel::download($export, $fileName);
        }

        if ($bankLower === 'bri') {
            $fileName = "payroll_bri_{$month}-{$year}.xlsx";
            return Excel::download(
                new PayrollExportBri($data, $fileName),
                $fileName
            );
        }

        // fallback
        return back()->with('error', 'Format bank tidak dikenali.');
    }

    //Export Kehadiran USEr
    public function exportKehadiran(Request $request)
    {
        $request->validate([
            'bulan' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'department_id' => ['nullable'],
            'selected_ids' => ['nullable'],
        ]);

        // ===============================
        // PARSE PERIODE
        // ===============================
        [$year, $month] = explode('-', $request->input('bulan'));
        $year  = (int) $year;
        $month = (int) $month;

        // ===============================
        // CHECKBOX
        // ===============================
        $selectedIds = json_decode($request->input('selected_ids', '[]'), true);
        if (!is_array($selectedIds)) {
            $selectedIds = [];
        }

        // ===============================
        // DEPARTMENT
        // ===============================
        $departmentInput = $request->input('department_id', null);
        $departmentName  = null;
        $userIds         = [];

        if (!empty($departmentInput)) {

            if (is_numeric($departmentInput)) {
                $dep = mdepartment::where('nid', intval($departmentInput))->first();

                if ($dep) {
                    $departmentNameRaw = trim((string)$dep->cname);
                    $userIds = muser::where('niddeptpayroll', $dep->nid)->pluck('nid')->toArray();
                } else {
                    $departmentNameRaw = (string)$departmentInput;
                    $userIds = muser::where('niddeptpayroll', intval($departmentInput))->pluck('nid')->toArray();
                }

            } else {
                $dep = mdepartment::where('cname', $departmentInput)
                    ->orWhere('code', $departmentInput)
                    ->first();

                if ($dep) {
                    $departmentNameRaw = trim((string)$dep->cname);
                    $userIds = muser::where('niddeptpayroll', $dep->nid)->pluck('nid')->toArray();
                } else {
                    $departmentNameRaw = trim((string)$departmentInput);
                    $userIds = muser::where('niddeptpayroll', $departmentInput)->pluck('nid')->toArray();
                }
            }

            $rawUpper = strtoupper($departmentNameRaw ?? '');

            if ($rawUpper === 'CK' || $rawUpper === 'CENTRAL KITCHEN') {
                $departmentName = 'CENTRAL KITCHEN MATAHATI CAFE';
            } else {
                $departmentName = $rawUpper !== ''
                    ? $rawUpper . ' MATAHATI CAFE'
                    : null;
            }
        }

        // ===============================
        // 🔥 QUERY FINAL (MUSER + FILTER CSALARY)
        // ===============================
        $query = muser::with(['salary' => function ($q) use ($year, $month) {
            $q->where('period_year', $year)
              ->where('period_month', $month);
        }])
            ->whereHas('salary', function ($q) use ($year, $month) {
                $q->where('period_year', $year)
                  ->where('period_month', $month);
            });

        // checkbox (pakai csalary.id)
        if (!empty($selectedIds)) {
            $query->whereHas('salary', function ($q) use ($selectedIds) {
                $q->whereIn('id', $selectedIds);
            });
        }
        // filter department
        elseif (!empty($userIds)) {
            $query->whereIn('nid', $userIds);
        }

        $users = $query->get();

        if ($users->count() === 0) {
            return back()->with('error', 'Tidak ada data kehadiran untuk periode / filter.');
        }

        $fileName = sprintf('Report-Kehadiran-%04d-%02d.xlsx', $year, $month);

        // ===============================
        // MULTI SHEET
        // ===============================
        if (empty($departmentInput)) {

            $departments = mdepartment::pluck('cname', 'nid')->toArray();

            $grouped = $users->groupBy(function ($user) {
                return $user->niddeptpayroll ?? 0;
            });

            $sheets = [];

            foreach ($grouped as $deptId => $rows) {

                $rawName = strtoupper($departments[$deptId] ?? 'OTHER');

                switch ($rawName) {
                    case 'BACKOFFICE':
                        $sheetName = 'Backoffice';
                        $header = 'BACKOFFICE MATAHATI CAFE';
                        break;

                    case 'CAFE PANGGUL':
                        $sheetName = 'Cafe Panggul';
                        $header = 'CAFE PANGGUL MATAHATI CAFE';
                        break;

                    case 'CAFE TA':
                        $sheetName = 'Cafe TA';
                        $header = 'CAFE TA MATAHATI CAFE';
                        break;

                    case 'CK':
                    case 'CENTRAL KITCHEN':
                        $sheetName = 'Central Kitchen';
                        $header = 'CENTRAL KITCHEN MATAHATI CAFE';
                        break;

                    case 'IT':
                        $sheetName = 'IT';
                        $header = 'IT MATAHATI CAFE';
                        break;

                    case 'MARKETING':
                        $sheetName = 'Marketing';
                        $header = 'MARKETING MATAHATI CAFE';
                        break;

                    default:
                        $sheetName = ucfirst(strtolower($rawName));
                        $header = $rawName . ' MATAHATI CAFE';
                }

                $sheets[$sheetName] = [
                    'rows' => $rows,
                    'header' => $header
                ];
            }

            return Excel::download(
                new \App\Exports\KehadiranMultiSheetExport($sheets, $month, $year),
                $fileName
            );
        }

        // ===============================
        // SINGLE SHEET
        // ===============================
        return Excel::download(
            new \App\Exports\KehadiranExport($users, $month, $year, $departmentName),
            $fileName
        );
    }
}
