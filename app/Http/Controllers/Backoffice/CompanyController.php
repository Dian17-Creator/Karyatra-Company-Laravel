<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\muser;
use App\Models\Mcompany;
use App\Models\Mowner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function checkCompany(Request $request)
    {
        $authUser = Auth::user() ?? Auth::guard('owner')->user();

        $company = Mcompany::where('cname', $authUser->ccompany)->first();

        $query = Mcompany::query();

        if ($company) {
            $query->where('id', '!=', $company->id);
        }

        $nameExists = false;
        $domainExists = false;

        if ($request->filled('cname')) {
            $nameExists = (clone $query)
                ->whereRaw('LOWER(cname)=?', [strtolower(trim($request->cname))])
                ->exists();
        }

        if ($request->filled('cemail')) {
            $domainExists = (clone $query)
                ->whereRaw('LOWER(cemail)=?', [strtolower(trim($request->cemail))])
                ->exists();
        }

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

            // 2. Jika nama company berubah, sinkronisasi ccompany di muser & mowner
            if ($oldCname !== $newCname) {
                muser::where('ccompany', $oldCname)->update(['ccompany' => $newCname]);
                Mowner::where('ccompany', $oldCname)->update(['ccompany' => $newCname]);
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
}
