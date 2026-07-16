<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\muser;
use App\Models\mrequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function viewRequests(Request $request, int $userId)
    {
        $user = muser::findOrFail($userId);
        $sort = $request->get('sort', 'desc');

        // Ambil semua kolom termasuk cplacename
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
        $sort = $request->get('sort', 'desc');

        // Ambil semua kolom termasuk cplacename
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
}
