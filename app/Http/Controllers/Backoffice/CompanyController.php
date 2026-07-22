<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\muser;
use App\Models\Mcompany;
use App\Models\Mowner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    public function checkCompany(Request $request)
    {
        Log::info("Web Check Company - Request Data", $request->all());
        $authUser = Auth::user() ?? Auth::guard('owner')->user();
        $company = Mcompany::where('cname', $authUser->ccompany)->first();
        $query = Mcompany::query();
        if ($company) {
            $query->where('id', '!=', $company->id);
        }
        $nameExists = false;
        $domainExists = false;
        if ($request->filled('cname')) {
            $q = (clone $query)->whereRaw('LOWER(cname)=?', [strtolower(trim($request->cname))]);
            Log::info("Web Check Company - Name Check SQL: " . $q->toSql());
            Log::info("Web Check Company - Name Check Bindings: ", $q->getBindings());
            $nameExists = $q->exists();
        }
        if ($request->filled('cemail')) {
            $q = (clone $query)->whereRaw('LOWER(cemail)=?', [strtolower(trim($request->cemail))]);
            Log::info("Web Check Company - Domain Check SQL: " . $q->toSql());
            Log::info("Web Check Company - Domain Check Bindings: ", $q->getBindings());
            $domainExists = $q->exists();
        }
        Log::info("Web Check Company - Results: name_exists=$nameExists, domain_exists=$domainExists");
        return response()->json([
            'name_exists'   => $nameExists,
            'domain_exists' => $domainExists
        ]);
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

        $authUser = Auth::user() ?? Auth::guard('owner')->user();
        $company  = Mcompany::where('cname', $authUser->ccompany)->firstOrFail();

        $oldCname  = $company->cname;
        $oldCemail = $company->cemail;
        $newCname  = $request->cname;
        $newCemail = $request->cemail;

        DB::transaction(function () use ($company, $oldCname, $oldCemail, $newCname, $newCemail) {

            // 1. Update tabel mcompany
            $company->update([
                'cname'  => $newCname,
                'cemail' => $newCemail,
            ]);

            // 2. Jika nama company berubah, sinkronisasi ccompany di tabel-tabel terkait
            if ($oldCname !== $newCname) {
                muser::where('ccompany', $oldCname)->update(['ccompany' => $newCname]);
                Mowner::where('ccompany', $oldCname)->update(['ccompany' => $newCname]);
                DB::table('mdepartment')->where('ccompany', $oldCname)->update(['ccompany' => $newCname]);
                DB::table('mrekening')->where('ccompany', $oldCname)->update(['ccompany' => $newCname]);
                DB::table('mschedule')->where('ccompany', $oldCname)->update(['ccompany' => $newCname]);
                DB::table('tdeptlokasi')->where('ccompany', $oldCname)->update(['ccompany' => $newCname]);
            }

            // 3. Jika domain email berubah, update cemail semua user & owner
            //    Hanya ganti bagian domain setelah '@', username tetap
            if ($oldCemail !== $newCemail) {
                $users = muser::where('ccompany', $newCname)->get();
                foreach ($users as $u) {
                    $parts     = explode('@', $u->cemail, 2);
                    $u->cemail = $parts[0] . '@' . $newCemail;
                    $u->save();
                }

                $owners = Mowner::where('ccompany', $newCname)->get();
                foreach ($owners as $o) {
                    $parts     = explode('@', $o->cemail, 2);
                    $o->cemail = $parts[0] . '@' . $newCemail;
                    $o->save();
                }
            }
        });

        return back()->with('success', 'Data company berhasil diperbarui. Email semua user telah disesuaikan dengan domain baru.');
    }

    //API For mobile apk
    public function apiCheckCompany(Request $request)
    {
        Log::info("API Check Company - Request Data", $request->all());

        $request->validate([
            'user_id' => 'required|integer',
            'cname'   => 'nullable|string|max:255',
            'cemail'  => 'nullable|string|max:255',
        ]);

        $user = muser::find($request->user_id);

        if (!$user) {
            Log::warning("API Check Company - User not found: " . $request->user_id);
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.'
            ], 404);
        }

        $company = Mcompany::where('cname', $user->ccompany)->first();

        $query = Mcompany::query();

        // Abaikan company milik sendiri saat pengecekan
        if ($company) {
            $query->where('id', '!=', $company->id);
        }

        $nameExists = false;
        $domainExists = false;

        if ($request->filled('cname')) {
            $q = (clone $query)->whereRaw('LOWER(cname) = ?', [strtolower(trim($request->cname))]);
            Log::info("API Check Company - Name Check SQL: " . $q->toSql());
            Log::info("API Check Company - Name Check Bindings: ", $q->getBindings());
            $nameExists = $q->exists();
        }

        if ($request->filled('cemail')) {
            $q = (clone $query)->whereRaw('LOWER(cemail) = ?', [strtolower(trim($request->cemail))]);
            Log::info("API Check Company - Domain Check SQL: " . $q->toSql());
            Log::info("API Check Company - Domain Check Bindings: ", $q->getBindings());
            $domainExists = $q->exists();
        }

        Log::info("API Check Company - Results: name_exists=$nameExists, domain_exists=$domainExists");

        return response()->json([
            'success' => true,
            'data' => [
                'name_exists'   => $nameExists,
                'domain_exists' => $domainExists
            ]
        ]);
    }

    public function apiUpdateCompany(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'cname'   => 'required|string|max:255',
            'cemail'  => 'required|string|max:255',
        ]);

        $user = muser::find($request->user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.'
            ], 404);
        }

        if ($user->fsuper != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengubah data company.'
            ], 403);
        }

        $company = Mcompany::where('cname', $user->ccompany)->firstOrFail();

        $oldCname  = $company->cname;
        $oldCemail = $company->cemail;
        $newCname  = trim($request->cname);
        $newCemail = trim($request->cemail);

        DB::transaction(function () use ($company, $oldCname, $oldCemail, $newCname, $newCemail) {

            // Update company
            $company->update([
                'cname'  => $newCname,
                'cemail' => $newCemail,
            ]);

            // Sinkronisasi nama company
            if ($oldCname !== $newCname) {

                muser::where('ccompany', $oldCname)
                    ->update(['ccompany' => $newCname]);

                Mowner::where('ccompany', $oldCname)
                    ->update(['ccompany' => $newCname]);

                DB::table('mdepartment')
                    ->where('ccompany', $oldCname)
                    ->update(['ccompany' => $newCname]);

                DB::table('mrekening')
                    ->where('ccompany', $oldCname)
                    ->update(['ccompany' => $newCname]);

                DB::table('mschedule')
                    ->where('ccompany', $oldCname)
                    ->update(['ccompany' => $newCname]);

                DB::table('tdeptlokasi')
                    ->where('ccompany', $oldCname)
                    ->update(['ccompany' => $newCname]);
            }

            // Sinkronisasi domain email
            if ($oldCemail !== $newCemail) {

                $users = muser::where('ccompany', $newCname)->get();

                foreach ($users as $user) {
                    $username = explode('@', $user->cemail, 2)[0];

                    $user->update([
                        'cemail' => $username . '@' . $newCemail
                    ]);
                }

                $owners = Mowner::where('ccompany', $newCname)->get();

                foreach ($owners as $owner) {
                    $username = explode('@', $owner->cemail, 2)[0];

                    $owner->update([
                        'cemail' => $username . '@' . $newCemail
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Data company berhasil diperbarui.',
            'data' => [
                'company' => [
                    'cname'  => $newCname,
                    'cemail' => $newCemail,
                ]
            ]
        ]);
    }

    public function apiGetCompany(Request $request)
    {
        $user = muser::find($request->user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.'
            ], 404);
        }

        $company = Mcompany::where('cname', $user->ccompany)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'cname'  => $company->cname,
                'cemail' => $company->cemail,
            ]
        ]);
    }
}
