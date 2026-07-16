<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Backoffice\Traits\ResolveCompany;
use App\Models\muser;
use App\Models\mdepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    use ResolveCompany;

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
                Rule::unique('mdepartment', 'cname')->where(function ($query) use ($user) {
                    return $query->where('ccompany', $user ? $user->ccompany : null);
                })
            ],
        ]);

        mdepartment::create([
            'cname'    => $request->cname,
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

    public function apiDepartmentList(Request $request)
    {
        $ccompany = $this->resolveCcompany($request);
        $query = mdepartment::orderBy('cname');
        if ($ccompany) {
            $query->where('ccompany', $ccompany);
        }
        $data = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }
}
