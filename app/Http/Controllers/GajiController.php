<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Csalary;
use App\Models\Rsalary;
use App\Models\muser;
use App\Models\Mtunjangan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\mdepartment;
use App\Models\Mrekening;
use Illuminate\Support\Facades\Log;

class GajiController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;
        $selYear  = $request->year ?? now()->year;
        $selMonth = $request->month ?? now()->month;

        $authUser = auth()->user();

        // master data
        $usersQuery = muser::orderBy('cname');
        $departmentsQuery = mdepartment::orderBy('cname');
        $mtunjanganQuery = Mtunjangan::with('user')->orderByDesc('tanggal_berlaku');

        if ($authUser && $authUser->ccompany) {
            $usersQuery->where('ccompany', $authUser->ccompany);
            $departmentsQuery->where('ccompany', $authUser->ccompany);
            $mtunjanganQuery->whereHas('user', function ($q) use ($authUser) {
                $q->where('ccompany', $authUser->ccompany);
            });
        }

        $users = $usersQuery->get();
        $departments = $departmentsQuery->get();

        $mrekeningQuery = Mrekening::orderBy('bank');
        if ($authUser && $authUser->ccompany) {
            $mrekeningQuery->where('ccompany', $authUser->ccompany);
        }
        $mrekening = $mrekeningQuery->get();

        $mtunjangan = $mtunjanganQuery->get();

        // optional department filter from query string
        $depIdRaw = $request->input('department_id', null);
        $depId = null;
        if (!is_null($depIdRaw) && trim((string)$depIdRaw) !== '') {
            $depId = intval($depIdRaw) > 0 ? intval($depIdRaw) : null;
        }

        // Ambil payroll (apply department filter jika ada)
        $query = Csalary::with('user')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->orderBy('user_id');

        if ($authUser && $authUser->ccompany) {
            $query->whereHas('user', function ($q) use ($authUser) {
                $q->where('ccompany', $authUser->ccompany);
            });
        }

        if ($depId !== null) {
            // <-- IMPORTANT: muser primary key is `nid`, not `id` -> pluck('nid')
            $userIdsQuery = muser::where('niddept', $depId);
            if ($authUser && $authUser->ccompany) {
                $userIdsQuery->where('ccompany', $authUser->ccompany);
            }
            $userIds = $userIdsQuery->pluck('nid')->toArray();

            if (count($userIds) === 0) {
                // tidak ada user di departemen itu -> kembalikan result kosong
                $rows = collect([]);
            } else {
                $query->whereIn('user_id', $userIds);
                $rows = $query->get();
            }
        } else {
            $rows = $query->get();
        }

        // Format untuk view (tambahkan department_id agar blade bisa pakai data-department-id)
        $data = $rows->map(function ($model) {
            $user = $model->user;

            // Tentukan jabatan
            $jabatan = 'Crew';
            if ($user) {
                if (!empty($user->fhrd)) {
                    $jabatan = 'HRD';
                } elseif (!empty($user->fadmin)) {
                    $jabatan = 'Captain';
                } elseif (!empty($user->fsuper)) {
                    $jabatan = 'Supervisor';
                } elseif (!empty($user->jabatan)) {
                    $jabatan = $user->jabatan;
                }
            }

            $gaji_harian   = (float) $model->gaji_harian;
            $gaji_pokok    = (float) $model->gaji_pokok;
            $jumlah_masuk  = (int)   $model->jumlah_masuk;

            $jenisGaji = strtolower($model->jenis_gaji ?? 'pokok');

            if ($jenisGaji === 'harian') {
                $displayGaji      = $gaji_harian;
                $displayGajiPokok = $gaji_pokok;
            } else {
                $displayGaji      = $gaji_pokok;
                $displayGajiPokok = $gaji_pokok;
            }

            $fmt = fn($n) => 'Rp ' . number_format((float)$n, 2, ',', '.');

            return [
                'id' => $model->id,
                'user_id' => $model->user_id,
                // use nid from user relation (or null)
                'department_id' => $user->niddept ?? null,
                'user_name' => $user->cname ?? '-',
                'jabatan' => $jabatan,
                'jumlah_masuk' => $jumlah_masuk,

                'gaji' => $fmt($displayGaji),
                'gaji_harian' => $displayGaji,
                'gaji_pokok' => $fmt($displayGajiPokok),

                'tunjangan_makan' => $fmt($model->tunjangan_makan),
                'tunjangan_jabatan' => $fmt($model->tunjangan_jabatan),
                'tunjangan_transport' => $fmt($model->tunjangan_transport),
                'tunjangan_luar_kota' => $fmt($model->tunjangan_luar_kota),
                'tunjangan_masa_kerja' => $fmt($model->tunjangan_masa_kerja),
                'tunjangan_backup' => $fmt($model->tunjangan_backup),

                'gaji_lembur' => $fmt($model->gaji_lembur),
                'bonus_kehadiran' => $fmt($model->bonus_kehadiran),
                'tabungan_diambil' => $fmt($model->tabungan_diambil),
                'potongan_lain' => $fmt($model->potongan_lain),
                'potongan_tabungan' => $fmt($model->potongan_tabungan),
                'potongan_keterlambatan' => $fmt($model->potongan_keterlambatan),

                'total_gaji' => $fmt($model->total_gaji),
                'note' => $model->note,
                'keterangan_absensi' => $model->keterangan_absensi,
                'reasonedit' => $model->reasonedit,
                'status' => $model->status,
                'email_status' => $model->email_status,
                'user_note' => $model->user_note,
                'pdf_url' => $model->pdf_url ?? null,
            ];
        })->toArray();

        return view('penggajian.index', [
            'data'  => $data,
            'year'  => $year,
            'month' => $month,
            'mtunjangan' => $mtunjangan,
            'users' => $users,
            'selYear' => $selYear,
            'selMonth' => $selMonth,
            'departments' => $departments,
            'mrekening' => $mrekening,
        ]);
    }

    public function update(Request $request, $id)
    {
        $row = Csalary::findOrFail($id);

        $oldJumlahMasuk = (int) ($row->jumlah_masuk ?? 0);
        $oldTunjanganMakan = (float) ($row->tunjangan_makan ?? 0);
        $oldGajiPokok = (float) ($row->gaji_pokok ?? 0);

        $validated = $request->validate([
            'jumlah_masuk'            => 'nullable|integer|min:0',
            'gaji_harian'             => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'gaji_pokok'              => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'tunjangan_makan'         => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'tunjangan_jabatan'       => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'tunjangan_transport'     => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'tunjangan_luar_kota'     => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'tunjangan_masa_kerja'    => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'tunjangan_backup'        => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'gaji_lembur'             => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'bonus_kehadiran'         => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'tabungan_diambil'        => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'potongan_lain'           => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'potongan_tabungan'       => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'potongan_keterlambatan'  => 'nullable|regex:/^[0-9\.\,\sRp]+$/',
            'note'                    => 'nullable|string|max:1000',
            'reasonedit'              => 'nullable|string|max:1000',
        ]);

        $clean = function ($v) {
            if ($v === null || $v === '') {
                return 0;
            }
            if (is_numeric($v)) {
                return (float) $v;
            }
            $v = str_replace(['Rp', ' '], '', $v);
            if (str_contains($v, ',')) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            }
            return is_numeric($v) ? (float) $v : 0;
        };

        $newJumlahMasuk         = $validated['jumlah_masuk'] ?? $row->jumlah_masuk ?? 0;
        $newGajiHarian          = $clean($validated['gaji_harian']            ?? $request->input('gaji_harian'));
        $newGajiPokokInput      = $clean($validated['gaji_pokok']             ?? $request->input('gaji_pokok'));
        $newTunjanganMakan      = $oldTunjanganMakan; // default ke nilai lama dulu
        $newGajiPokok           = $oldGajiPokok;      // default ke nilai lama dulu
        $newTunjanganJabatan    = $clean($validated['tunjangan_jabatan']      ?? $request->input('tunjangan_jabatan'));
        $newTunjanganTransport  = $clean($validated['tunjangan_transport']    ?? $request->input('tunjangan_transport'));
        $newTunjanganLuarKota   = $clean($validated['tunjangan_luar_kota']    ?? $request->input('tunjangan_luar_kota'));
        $newTunjanganMasaKerja  = $clean($validated['tunjangan_masa_kerja']   ?? $request->input('tunjangan_masa_kerja'));
        $newTunjanganBackup     = $clean($validated['tunjangan_backup']       ?? $request->input('tunjangan_backup'));
        $newGajiLembur          = $clean($validated['gaji_lembur']            ?? $request->input('gaji_lembur'));
        $newBonusKehadiran      = $clean($validated['bonus_kehadiran']        ?? $request->input('bonus_kehadiran'));
        $newTabunganDiambil     = $clean($validated['tabungan_diambil']       ?? $request->input('tabungan_diambil'));
        $newPotonganLain        = $clean($validated['potongan_lain']          ?? $request->input('potongan_lain'));
        $newPotonganTabungan    = $clean($validated['potongan_tabungan']      ?? $request->input('potongan_tabungan'));
        $newPotonganKeterlambatan = $clean($validated['potongan_keterlambatan'] ?? $request->input('potongan_keterlambatan'));
        $newNote                = $validated['note']       ?? $request->input('note');
        $newReasonEdit          = $validated['reasonedit'] ?? $request->input('reasonedit');

        // =====================================================
        // KONDISI KHUSUS: jumlah_masuk dari 0 → > 0
        // Ambil dari master tunjangan
        // =====================================================
        if ($oldJumlahMasuk == 0 && $newJumlahMasuk > 0) {
            $latestTunjangan = \App\Models\Mtunjangan::where('nid', $row->user_id)
                ->whereDate('tanggal_berlaku', '<=', now())
                ->orderByDesc('tanggal_berlaku')
                ->orderByDesc('id')
                ->first();

            if ($latestTunjangan) {
                // === GAJI ===
                if (strtolower($latestTunjangan->jenis_gaji) === 'harian') {
                    $newGajiHarian = (float) $latestTunjangan->nominal_gaji;
                    $newGajiPokok  = $newGajiHarian * $newJumlahMasuk;
                } else {
                    $newGajiPokok  = (float) $latestTunjangan->nominal_gaji;
                    $newGajiHarian = $newJumlahMasuk > 0 ? ($newGajiPokok / $newJumlahMasuk) : 0;
                }

                // === TUNJANGAN MAKAN ===
                if ($latestTunjangan->tunjangan_makan > 0) {
                    $newTunjanganMakan = $latestTunjangan->tunjangan_makan * $newJumlahMasuk;
                }

                $newTunjanganJabatan   = (float) $latestTunjangan->tunjangan_jabatan;
                $newTunjanganTransport = (float) $latestTunjangan->tunjangan_transport;
                $newTunjanganLuarKota  = (float) $latestTunjangan->tunjangan_luar_kota;
                $newTunjanganMasaKerja = (float) $latestTunjangan->tunjangan_masa_kerja;
                $newTunjanganBackup    = (float) $latestTunjangan->tunjangan_backup;
            }
        }

        // =====================================================
        // GAJI POKOK — logic sama persis seperti tunjangan makan
        // =====================================================
        $jenisGajiRow = strtolower($row->jenis_gaji ?? 'pokok');

        // Rate per hari: untuk gaji harian gunakan nominal gaji_harian,
        // untuk gaji pokok gunakan gaji_pokok / jumlah_masuk (proporsional)
        if ($jenisGajiRow === 'harian') {
            $gajiPerHari = (float) ($row->gaji_harian ?? 0);
        } else {
            $gajiPerHari = $oldJumlahMasuk > 0 ? $oldGajiPokok / $oldJumlahMasuk : 0;
        }

        // Guard: jangan overwrite dari input jika kondisi 0 -> >0
        // (sudah dihandle dari master di blok atas)
        if (!($oldJumlahMasuk == 0 && $newJumlahMasuk > 0)) {
            // Tidak ambil dari input — biarkan nilai lama dulu (sama seperti tunjangan makan)
            $newGajiPokok = $oldGajiPokok;
        }

        // Kalkulasi ulang proporsional jika jumlah_masuk berubah
        if (
            $oldJumlahMasuk > 0 &&
            $newJumlahMasuk != $oldJumlahMasuk &&
            $gajiPerHari > 0
        ) {
            $newGajiPokok = $gajiPerHari * $newJumlahMasuk;
        }

        // Sync gaji harian dari gaji pokok final
        if ($jenisGajiRow === 'harian') {
            // Untuk gaji harian, gaji_harian tetap rate per hari (tidak berubah)
            $newGajiHarian = $gajiPerHari;
        } else {
            $newGajiHarian = $newJumlahMasuk > 0 ? $newGajiPokok / $newJumlahMasuk : 0;
        }

        // =====================================================
        // TUNJANGAN MAKAN — logic seperti sebelumnya
        // =====================================================
        $tunjanganPerHari = $oldJumlahMasuk > 0 ? $oldTunjanganMakan / $oldJumlahMasuk : 0;

        // Guard: jangan overwrite dari input jika kondisi 0 -> >0
        if (!($oldJumlahMasuk == 0 && $newJumlahMasuk > 0)) {
            $newTunjanganMakan = $clean(
                $validated['tunjangan_makan'] ?? $request->input('tunjangan_makan')
            );
        }

        // Kalkulasi ulang proporsional jika jumlah_masuk berubah
        if (
            $oldJumlahMasuk > 0 &&
            $newJumlahMasuk != $oldJumlahMasuk &&
            $tunjanganPerHari > 0
        ) {
            $newTunjanganMakan = $tunjanganPerHari * $newJumlahMasuk;
        }

        // =====================================================
        // GUARD ALASAN EDIT
        // =====================================================
        $watchedFields = [
            'jumlah_masuk'            => [$row->jumlah_masuk,          $newJumlahMasuk],
            'gaji_harian'             => [$row->gaji_harian,           $newGajiHarian],
            'gaji_pokok'              => [$row->gaji_pokok,            $newGajiPokok],
            'tunjangan_makan'         => [$row->tunjangan_makan,       $newTunjanganMakan],
            'tunjangan_jabatan'       => [$row->tunjangan_jabatan,     $newTunjanganJabatan],
            'tunjangan_transport'     => [$row->tunjangan_transport,   $newTunjanganTransport],
            'tunjangan_luar_kota'     => [$row->tunjangan_luar_kota,   $newTunjanganLuarKota],
            'tunjangan_masa_kerja'    => [$row->tunjangan_masa_kerja,  $newTunjanganMasaKerja],
            'tunjangan_backup'        => [$row->tunjangan_backup,      $newTunjanganBackup],
            'gaji_lembur'             => [$row->gaji_lembur,           $newGajiLembur],
            'bonus_kehadiran'         => [$row->bonus_kehadiran,       $newBonusKehadiran],
            'tabungan_diambil'        => [$row->tabungan_diambil,      $newTabunganDiambil],
            'potongan_lain'           => [$row->potongan_lain,         $newPotonganLain],
            'potongan_tabungan'       => [$row->potongan_tabungan,     $newPotonganTabungan],
            'potongan_keterlambatan'  => [$row->potongan_keterlambatan, $newPotonganKeterlambatan],
        ];

        $isChanged = false;
        foreach ($watchedFields as $field => [$old, $new]) {
            if ((float) $old !== (float) $new) {
                $isChanged = true;
                break;
            }
        }

        if ($isChanged && empty(trim($newReasonEdit ?? ''))) {
            return redirect()->back()
                ->withErrors(['reasonedit' => 'Alasan edit wajib diisi jika ada perubahan data.'])
                ->withInput();
        }

        // =====================================================
        // SIMPAN
        // =====================================================
        $row->jumlah_masuk           = $newJumlahMasuk;
        $row->gaji_harian            = $newGajiHarian;
        $row->gaji_pokok             = $newGajiPokok;
        $row->tunjangan_makan        = $newTunjanganMakan;
        $row->tunjangan_jabatan      = $newTunjanganJabatan;
        $row->tunjangan_transport    = $newTunjanganTransport;
        $row->tunjangan_luar_kota    = $newTunjanganLuarKota;
        $row->tunjangan_masa_kerja   = $newTunjanganMasaKerja;
        $row->tunjangan_backup       = $newTunjanganBackup;
        $row->gaji_lembur            = $newGajiLembur;
        $row->bonus_kehadiran        = $newBonusKehadiran;
        $row->tabungan_diambil       = $newTabunganDiambil;
        $row->potongan_lain          = $newPotonganLain;
        $row->potongan_tabungan      = $newPotonganTabungan;
        $row->potongan_keterlambatan = $newPotonganKeterlambatan;
        $row->note                   = $newNote;
        $row->reasonedit             = $newReasonEdit;

        // Total tunjangan
        $totalTunjangan = ($row->tunjangan_makan        ?? 0)
            + ($row->tunjangan_jabatan       ?? 0)
            + ($row->tunjangan_transport     ?? 0)
            + ($row->tunjangan_luar_kota     ?? 0)
            + ($row->tunjangan_masa_kerja    ?? 0)
            + ($row->tunjangan_backup        ?? 0);

        // Total potongan
        $totalPotongan  = ($row->potongan_lain           ?? 0)
            + ($row->potongan_tabungan        ?? 0)
            + ($row->potongan_keterlambatan   ?? 0);

        // Total gaji
        $row->total_gaji = round(
            ($row->gaji_pokok       ?? 0)
                + $totalTunjangan
                + ($row->gaji_lembur    ?? 0)
                + ($row->bonus_kehadiran ?? 0)
                + ($row->tabungan_diambil ?? 0)
                - $totalPotongan,
            2
        );

        $row->status          = 'PENDING';
        $row->user_note       = null;
        $row->user_updated_at = null;

        $row->save();
        $row->refresh()->regenerateSlipPdf();

        return redirect()->back()->with('success', 'Payroll berhasil diperbarui.');
    }

    public function getLatestTunjangan($nid)
    {
        $row = Mtunjangan::where('nid', $nid)
            ->orderByDesc('tanggal_berlaku')
            ->orderByDesc('id')
            ->first();

        if (!$row) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'jenis_gaji' => $row->jenis_gaji,
            'nominal_gaji' => $row->nominal_gaji,
            't_makan' => $row->tunjangan_makan,
            't_jabatan' => $row->tunjangan_jabatan,
            't_transport' => $row->tunjangan_transport,
            't_luarkota' => $row->tunjangan_luar_kota,
            't_masakerja' => $row->tunjangan_masa_kerja,
            't_backup' => $row->tunjangan_backup,
        ]);
    }

    public function tunjanganIndex()
    {
        $data = Mtunjangan::with('user')
            ->orderByDesc('tanggal_berlaku')
            ->get();

        return view('penggajian.tunjangan_index', [
            'data' => $data
        ]);
    }

    public function tunjanganStore(Request $request)
    {
        $validated = $request->validate([
            'nid' => 'nullable|exists:muser,nid',
            'tanggal_berlaku' => 'required|date',
            'jenis_gaji' => 'required|in:pokok,harian',

            'nominal_gaji' => 'nullable',
            'tunjangan_makan' => 'nullable',
            'tunjangan_jabatan' => 'nullable',
            'tunjangan_transport' => 'nullable',
            'tunjangan_luar_kota' => 'nullable',
            'tunjangan_masa_kerja' => 'nullable',
            'tunjangan_backup' => 'nullable',
        ]);

        // 🔑 NORMALISASI ANGKA (FORMAT ID → FLOAT)
        $clean = function ($v) {
            if ($v === null || $v === '') {
                return 0;
            }

            // ✅ JIKA SUDAH NUMERIC, JANGAN DISENTUH
            if (is_numeric($v)) {
                return (float) $v;
            }

            // ❗ HANYA UNTUK FORMAT LOKAL
            $v = str_replace(['Rp', ' '], '', $v);

            // jika format ID (ada koma)
            if (str_contains($v, ',')) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            }

            return is_numeric($v) ? (float) $v : 0;
        };

        $validated['nominal_gaji']          = $clean($request->nominal_gaji);
        $validated['tunjangan_makan']       = $clean($request->tunjangan_makan);
        $validated['tunjangan_jabatan']     = $clean($request->tunjangan_jabatan);
        $validated['tunjangan_transport']   = $clean($request->tunjangan_transport);
        $validated['tunjangan_luar_kota']   = $clean($request->tunjangan_luar_kota);
        $validated['tunjangan_masa_kerja']  = $clean($request->tunjangan_masa_kerja);
        $validated['tunjangan_backup']      = $clean($request->tunjangan_backup);

        Mtunjangan::create($validated);

        return back()->with('success', 'Tunjangan berhasil ditambahkan.');
    }

    public function tunjanganDelete($id)
    {
        $row = Mtunjangan::findOrFail($id);
        $row->delete();

        return back()->with('success', 'Data tunjangan berhasil dihapus.');
    }

    public function getSlipInfo($id)
    {
        $row = Csalary::find($id);

        if (!$row) {
            return response()->json([
                'success' => false,
                'bulan' => 'Bulan ini',
                'wa_footer' => env(
                    'WA_FOOTER'
                )
            ]);
        }

        // Hitung bulan
        try {
            $periodMonth = $row->period_month ?? $row->month ?? null;
            $periodYear  = $row->period_year ?? $row->year ?? null;

            if ($periodMonth && $periodYear) {
                $bulan = Carbon::createFromDate(
                    (int)$periodYear,
                    (int)$periodMonth,
                    1
                )->translatedFormat('F Y');
            } else {
                $bulan = Carbon::parse($row->created_at)->translatedFormat('F Y');
            }
        } catch (\Exception $e) {
            $bulan = Carbon::now()->translatedFormat('F Y');
        }

        return response()->json([
            'success'   => true,
            'bulan'    => $bulan,
            'wa_footer' => env(
                'WA_FOOTER'
            ),
        ]);
    }
    public function filterByDepartment(Request $req)
    {
        $year  = (int) ($req->input('year', now()->year));
        $month = (int) ($req->input('month', now()->month));
        $depIdRaw = $req->input('department_id', null);

        $depId = null;
        if ($depIdRaw !== null && trim((string)$depIdRaw) !== '') {
            $depId = is_numeric($depIdRaw) ? intval($depIdRaw) : null;
        }

        try {
            $authUser = auth()->user();
            $query = Csalary::with('user')
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->orderBy('user_id');

            if ($authUser && $authUser->ccompany) {
                $query->whereHas('user', function ($q) use ($authUser) {
                    $q->where('ccompany', $authUser->ccompany);
                });
            }

            $userIds = [];
            if ($depId !== null) {
                $muserQuery = muser::where('niddeptpayroll', $depId);
                if ($authUser && $authUser->ccompany) {
                    $muserQuery->where('ccompany', $authUser->ccompany);
                }
                $userIds = $muserQuery->pluck('nid')->toArray();

                if (count($userIds) === 0) {
                    $rows = collect([]);
                } else {
                    $query->whereIn('user_id', $userIds);
                    $rows = $query->get();
                }
            } else {
                // if depIdRaw is non-numeric string (e.g. 'CK'), try to match it directly against muser.niddeptpayroll
                if ($depIdRaw !== null && trim((string)$depIdRaw) !== '') {
                    // treat depIdRaw as string code/cname fallback
                    $muserQuery = muser::where('niddeptpayroll', $depIdRaw);
                    if ($authUser && $authUser->ccompany) {
                        $muserQuery->where('ccompany', $authUser->ccompany);
                    }
                    $userIds = $muserQuery->pluck('nid')->toArray();

                    if (count($userIds) === 0) {
                        // try finding by department cname
                        $depQuery = mdepartment::where('cname', $depIdRaw)->orWhere('code', $depIdRaw);
                        if ($authUser && $authUser->ccompany) {
                            $depQuery->where('ccompany', $authUser->ccompany);
                        }
                        $dep = $depQuery->first();
                        if ($dep) {
                            $muserQuery2 = muser::where('niddeptpayroll', $dep->nid ?? $dep->id);
                            if ($authUser && $authUser->ccompany) {
                                $muserQuery2->where('ccompany', $authUser->ccompany);
                            }
                            $userIds = $muserQuery2->pluck('nid')->toArray();
                        }
                    }

                    if (count($userIds) === 0) {
                        $rows = collect([]);
                    } else {
                        $query->whereIn('user_id', $userIds);
                        $rows = $query->get();
                    }
                } else {
                    $rows = $query->get();
                }
            }

            $data = $rows->map(function ($model) {
                $user = $model->user;
                $jabatan = 'Crew';
                if ($user) {
                    if (!empty($user->fhrd)) {
                        $jabatan = 'HRD';
                    } elseif (!empty($user->fadmin)) {
                        $jabatan = 'Captain';
                    } elseif (!empty($user->fsuper)) {
                        $jabatan = 'Supervisor';
                    } elseif (!empty($user->jabatan)) {
                        $jabatan = $user->jabatan;
                    }
                }

                $gaji_harian   = (float) $model->gaji_harian;
                $gaji_pokok    = (float) $model->gaji_pokok;
                $jumlah_masuk  = (int)   $model->jumlah_masuk;
                $jenisGaji = strtolower($model->jenis_gaji ?? 'pokok');
                $displayGaji = $jenisGaji === 'harian' ? $gaji_harian : $gaji_pokok;

                $fmt = fn($n) => 'Rp ' . number_format((float)$n, 2, ',', '.');
                $rp  = fn($n) => '' . number_format((float)$n, 2, ',', '.'); // tabel

                return [
                    'id' => $model->id,
                    'user_id' => $model->user_id,
                    'department_id' => $user ? (string) ($user->niddeptpayroll ?? '') : '',
                    'user_name' => $user ? ($user->cname ?? '-') : ('(no-user-' . $model->user_id . ')'),
                    'jabatan' => $jabatan,
                    'jumlah_masuk' => $jumlah_masuk,
                    'gaji' => $fmt($displayGaji),

                    'gaji_harian' => $fmt($displayGaji),
                    'gaji_harian_rp' => $rp($displayGaji),
                    'gaji_pokok' => $fmt($gaji_pokok),
                    'gaji_pokok_rp' => $rp($gaji_pokok),

                    'tunjangan_makan' => $fmt($model->tunjangan_makan),
                    'tunjangan_makan_rp' => $rp($model->tunjangan_makan),

                    'tunjangan_jabatan' => $fmt($model->tunjangan_jabatan),
                    'tunjangan_jabatan_rp' => $rp($model->tunjangan_jabatan),

                    'tunjangan_transport' => $fmt($model->tunjangan_transport),
                    'tunjangan_transport_rp' => $rp($model->tunjangan_transport),

                    'tunjangan_luar_kota' => $fmt($model->tunjangan_luar_kota),
                    'tunjangan_luar_kota_rp' => $rp($model->tunjangan_luar_kota),

                    'tunjangan_masa_kerja' => $fmt($model->tunjangan_masa_kerja),
                    'tunjangan_masa_kerja_rp' => $rp($model->tunjangan_masa_kerja),

                    'tunjangan_backup' => $fmt($model->tunjangan_backup),
                    'tunjangan_backup_rp' => $rp($model->tunjangan_backup),

                    'gaji_lembur' => $fmt($model->gaji_lembur),
                    'gaji_lembur_rp' => $rp($model->gaji_lembur),

                    'bonus_kehadiran' => $fmt($model->bonus_kehadiran),
                    'bonus_kehadiran_rp' => $rp($model->bonus_kehadiran),

                    'tabungan_diambil' => $fmt($model->tabungan_diambil),
                    'tabungan_diambil_rp' => $rp($model->tabungan_diambil),

                    'potongan_lain' => $fmt($model->potongan_lain),
                    'potongan_lain_rp' => $rp($model->potongan_lain),

                    'potongan_tabungan' => $fmt($model->potongan_tabungan),
                    'potongan_tabungan_rp' => $rp($model->potongan_tabungan),

                    'potongan_keterlambatan' => $fmt($model->potongan_keterlambatan),
                    'potongan_keterlambatan_rp' => $rp($model->potongan_keterlambatan),

                    'total_gaji' => $fmt($model->total_gaji),
                    'total_gaji_rp' => $rp($model->total_gaji),

                    'note' => $model->note,
                    'keterangan_absensi' => $model->keterangan_absensi,
                    'reasonedit' => $model->reasonedit,
                    'status' => $model->status,
                    'email_status' => $model->email_status,
                    'user_note' => $model->user_note,
                    'pdf_url' => $model->pdf_url,
                ];
            })->toArray();

            // render partial rows (blade expects rows only)
            $html = view('penggajian.components.table_payroll_rows', [
                'data'  => $data,
                'year'  => $year,
                'month' => $month,
            ])->render();

            // prepare 'users' array grouped by department for client logging / counting
            $usersGrouped = [];
            foreach ($data as $row) {
                $did = (string) ($row['department_id'] ?? '(no-dept)');
                $usersGrouped[$did] = $usersGrouped[$did] ?? [];
                $usersGrouped[$did][] = $row['user_name'] ?? '(no-name)';
            }

            // respond with html, users_by_dept and user_ids (flat)
            $flatUserIds = array_values(array_unique(array_map(function ($r) {
                return (string) ($r['user_id'] ?? '');
            }, $data)));

            return response()->json([
                'success' => true,
                'html' => $html,
                'count' => count($data),
                'users_by_dept' => $usersGrouped,
                'user_ids' => $flatUserIds,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server error saat memproses filter. Cek log server.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
