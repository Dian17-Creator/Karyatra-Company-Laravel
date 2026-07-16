<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Backoffice\Traits\ResolveCompany;
use App\Models\muser;
use App\Models\Mrekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ResolveCompany;

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

    public function apiStoreUser(Request $request)
    {
        $request->merge([
            'fnotif' => $request->input('fnotif', 0)
        ]);

        $validator = Validator::make($request->all(), [
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
}
