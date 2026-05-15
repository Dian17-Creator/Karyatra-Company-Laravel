<?php

namespace App\Http\Controllers;

use App\Models\muser;
use App\Models\mdepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MasterUserController extends Controller
{
    /**
     * Menampilkan daftar user
     */
    public function index()
    {
        // Ambil semua user dengan relasi departemen
        $users = muser::with('department')->get();

        return view('masteruser.index', compact('users'));
    }

    /**
     * Menampilkan form tambah user
     */
    public function create()
    {
        $departments = mdepartment::all();
        return view('masteruser.create', compact('departments'));
    }

    /**
     * Simpan user baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'cemail' => 'required|email|unique:muser,cemail',
            'cname' => 'required|string|max:255',
            'cpassword' => 'required|string|min:6',
            'niddept' => 'required|exists:mdepartment,nid',
        ]);

        muser::create([
            'cemail' => $request->cemail,
            'cname' => $request->cname,
            'cpassword' => Hash::make($request->cpassword),
            'fadmin' => $request->fadmin ?? 0,
            'fsuper' => $request->fsuper ?? 0,
            'fsenior' => $request->fsuper ?? 0,
            'niddept' => $request->niddept,
        ]);

        return redirect()->route('masteruser.index')->with('success', 'User berhasil ditambahkan!');
    }

    /**
     * Menampilkan form edit user
     */
    public function edit($id)
    {
        $user = muser::findOrFail($id);
        $departments = mdepartment::all();
        return view('masteruser.edit', compact('user', 'departments'));
    }

    /**
     * Update data user
     */
    public function update(Request $request, $id)
    {
        $user = muser::findOrFail($id);

        $request->validate([
            'cemail' => 'required|email|unique:muser,cemail,' . $id . ',nid',
            'cname' => 'required|string|max:255',
            'niddept' => 'required|exists:mdepartment,nid',
        ]);

        $data = $request->only(['cemail', 'cname', 'fadmin', 'fsuper', 'fsenior', 'niddept']);

        // Jika password diisi, ganti
        if ($request->filled('cpassword')) {
            $data['cpassword'] = Hash::make($request->cpassword);
        }

        // Pastikan nilai boolean dikonversi ke 0/1 jika checkbox tidak dicentang
        $data['fadmin'] = $request->fadmin ?? 0;
        $data['fsuper'] = $request->fsuper ?? 0;
        $data['fhrd'] = $request->fhrd ?? 0;
        $data['fsenior'] = $request->fsenior ?? 0; // ✅ Tambahkan ini

        $user->update($data);

        return redirect()->route('masteruser.index')->with('success', 'User berhasil diperbarui!');
    }

    /**
     * Hapus user
     */
    public function destroy($id)
    {
        $user = muser::findOrFail($id);
        $user->delete();

        return redirect()->route('masteruser.index')->with('success', 'User berhasil dihapus!');
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
