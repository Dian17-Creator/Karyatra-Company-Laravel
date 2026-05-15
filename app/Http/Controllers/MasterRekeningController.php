<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mrekening;

class MasterRekeningController extends Controller
{
    public function index()
    {
        $mrekening = Mrekening::orderBy('bank')->orderBy('atas_nama')->get();
        return view('mrekening.index', compact('mrekening'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_rekening' => 'required|string|max:64',
            'bank' => 'required|in:BCA,Mandiri,BRI',
            'atas_nama' => 'required|string|max:191',
            'cabang' => 'nullable|string|max:191',
        ]);

        Mrekening::create($validated);

        // Redirect explicitly ke index agar tidak tergantung Referer
        return redirect()->route('penggajian.index')->with('success', 'Rekening berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $row = Mrekening::findOrFail($id);

        $validated = $request->validate([
            'nomor_rekening' => 'required|string|max:64',
            'bank' => 'required|in:BCA,Mandiri,BRI',
            'atas_nama' => 'required|string|max:191',
            'cabang' => 'nullable|string|max:191',
        ]);

        $row->update($validated);

        // Redirect eksplisit ke index agar konsisten di semua environment
        return redirect()->route('penggajian.index')->with('success', 'Rekening berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $row = Mrekening::findOrFail($id);
        $row->delete();

        // Redirect eksplisit ke index
        return redirect()->route('penggajian.index')->with('success', 'Rekening berhasil dihapus.');
    }

    // optional: show/edit jika butuh
    public function show($id)
    {
        $row = Mrekening::findOrFail($id);
        return view('mrekening.show', compact('row'));
    }

    public function byBank($bank)
    {
        $bank = trim($bank);
        $rows = Mrekening::where('bank', $bank)
            ->orderBy('atas_nama')
            ->get(['id', 'nomor_rekening', 'atas_nama', 'cabang']);

        return response()->json($rows);
    }
}
