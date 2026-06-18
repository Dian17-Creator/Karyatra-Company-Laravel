<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\mrequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MrequestController extends Controller
{
    /**
     * 🔹 Ambil semua data request
     * - HRD bisa lihat semua
     * - Captain bisa lihat pending di levelnya
     * - User hanya lihat miliknya sendiri
     */
    public function index()
    {
        $user = Auth::user();
        $query = mrequest::with(['user']);

        if ($user && $user->ccompany) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('ccompany', $user->ccompany);
            });
        }

        if ($user->role === 'hrd') {
            $requests = $query->orderBy('dcreated', 'desc')
                ->get();
        } elseif ($user->role === 'captain') {
            $requests = $query->where('cstatus', 'pending')
                ->orderBy('dcreated', 'desc')
                ->get();
        } else {
            // pegawai biasa
            $requests = $query->where('nuserId', $user->nid)
                ->orderBy('dcreated', 'desc')
                ->get();
        }

        return response()->json($requests);
    }

    /**
     * 🔹 Captain Approve / Reject
     */
    public function approveCaptain(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $req = mrequest::findOrFail($id);
        $user = Auth::user();

        // ❌ Tidak boleh approve diri sendiri
        if ((int)$req->nuserid === (int)$user->nid) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak boleh approve request milik sendiri.'
            ], 403);
        }

        // ❌ Hanya Captain
        if ($user->fadmin != 1 || $user->fhrd == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak approval.'
            ], 403);
        }

        // ❌ Jika pembuat adalah Captain → hanya HRD boleh approve
        $requestOwner = \App\Models\muser::find($req->nuserid);

        if ($requestOwner && $requestOwner->fadmin == 1 && $requestOwner->fhrd == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Request Captain hanya bisa di-approve oleh HRD.'
            ], 403);
        }

        // ✅ Update
        $req->update([
            'cstatus'  => $request->status,
            'nadminid' => $user->nid,
            'dupdated' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Captain telah ' . $request->status . ' permintaan ini',
        ]);
    }

    /**
     * 🔹 HRD Approve / Reject (langsung, tanpa menunggu Captain)
     */
    public function approveHrd(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:approved,rejected',
            ]);

            $req = mrequest::find($id);
            if (!$req) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            $user = Auth::user();

            $req->update([
                'chrdstat' => $request->status,
                'nhrdid' => $user->nid,
                'duphrd' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'HRD telah ' . $request->status . ' permintaan ini',
                'approved_at' => now()->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'approved_by' => $user->cname ?: 'HRD', // PASTIKAN ini ada dan tidak null
            ]);
        } catch (\Throwable $e) {
            Log::error('Approve HRD Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        // Cari data izin berdasarkan ID request
        $izin = \DB::table('mrequest')->where('nid', $id)->first();

        if (!$izin) {
            abort(404, 'Data izin tidak ditemukan.');
        }

        // Gunakan NID user (pegawai) untuk redirect
        $userId = $izin->nuserid ?? null;

        if (!$userId) {
            abort(404, 'User ID tidak ditemukan pada request ini.');
        }

        // 🔁 Redirect ke halaman request milik user tersebut
        return redirect()->to(url("/backoffice/requestcard/" . $userId));
    }

}
