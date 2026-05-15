<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MscanManual;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MscanManualController extends Controller
{
    /**
     * Simpan data absen manual dari mobile.
     */
    public function store(Request $request)
    {
        $isJson = $request->isJson();
        $data = $isJson ? $request->json()->all() : $request->all();

        $request->validate([
            'nuserId' => 'required|integer',
            'creason' => 'required|string',
            'nlat' => 'nullable|numeric',
            'nlng' => 'nullable|numeric',
            'photoBase64' => 'nullable|string',
            'photo' => 'nullable|file|image|max:4096',
        ]);

        $photoPath = null;

        // 📸 Simpan foto
        if (!empty($data['photoBase64'])) {
            $image = base64_decode($data['photoBase64']);
            $filename = 'photo_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.jpg';
            $path = 'uploads/manual/' . $filename;
            Storage::disk('public')->put($path, $image);
            $photoPath = 'storage/' . $path;
        } elseif ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('uploads/manual', 'public');
            $photoPath = 'storage/' . $path;
        }

        // 🧮 Simpan ke database
        $manual = MscanManual::create([
            'nuserId' => $data['nuserId'],
            'nlat' => $data['nlat'] ?? null,
            'nlng' => $data['nlng'] ?? null,
            'creason' => $data['creason'],
            'cphoto_path' => $photoPath,
            'dscanned' => now(),
            'cstatus' => 'pending',
            'chrdstat' => 'pending',
            'status' => $data['type'] ?? 'manual',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Absen manual berhasil disimpan',
            'data' => $manual
        ]);
    }

    /**
     * Captain Approve / Reject (bebas dari HRD)
     */
    public function approveCaptain(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:approved,rejected',
            ]);

            if (!Auth::check()) {
                return response()->json(['success' => false, 'message' => 'Session expired. Silakan login ulang.'], 401);
            }

            $manual = MscanManual::find($id);
            if (!$manual) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            $manual->update([
                'cstatus' => $request->status,
                'nadminid' => Auth::id(),
                'dapproved' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Captain telah ' . $request->status . ' permintaan ini',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * HRD Approve / Reject (bebas dari Captain)
     */
    public function approveHrd(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:approved,rejected',
            ]);

            if (!Auth::check()) {
                return response()->json(['success' => false, 'message' => 'Session expired. Silakan login ulang.'], 401);
            }

            $manual = MscanManual::find($id);
            if (!$manual) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            // ❌ Tidak ada lagi cek Captain/Supervisor
            $manual->update([
                'chrdstat' => $request->status,
                'cstatus' => $request->status,
                'duhrd' => now(),
                'nhrdid' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'HRD telah ' . $request->status . ' permintaan ini',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        // Cari data user berdasarkan NID
        $user = \DB::table('muser')->where('nid', $id)->first();

        if (!$user) {
            abort(404, 'Data user tidak ditemukan.');
        }

        // Redirect ke halaman log milik user ini
        return redirect()->to(url("/backoffice/logs/" . $user->nid));
    }

}
