<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mrekening;
use Illuminate\Support\Facades\Log;

class MasterRekeningController extends Controller
{
    public function index()
    {
        $user = auth()->user() ?? auth('owner')->user();
        $query = Mrekening::orderBy('bank')->orderBy('atas_nama');

        if ($user && $user->ccompany) {
            $query->where('ccompany', $user->ccompany);
        }

        $mrekening = $query->get();
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

        $user = auth()->user() ?? auth('owner')->user();
        if ($user) {
            $validated['ccompany'] = $user->ccompany;
        }

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
        $user = auth()->user() ?? auth('owner')->user();
        $query = Mrekening::where('bank', $bank);

        if ($user && $user->ccompany) {
            $query->where('ccompany', $user->ccompany);
        }

        $rows = $query->orderBy('atas_nama')
            ->get(['id', 'nomor_rekening', 'atas_nama', 'cabang']);

        return response()->json($rows);
    }
}
