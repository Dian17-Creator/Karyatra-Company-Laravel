<?php

namespace App\Http\Controllers;

use App\Models\Mowner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MownerController extends Controller
{
    public function index()
    {
        $owners = Mowner::all();
        return view('mowner.index', compact('owners'));
    }

    public function create()
    {
        return view('mowner.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'cemail' => 'required|email|unique:mowner,cemail',
            'cname' => 'required|string|max:255',
            'cpassword' => 'required|string',
            'ccompany' => 'nullable|string|max:100',
        ]);

        Mowner::create([
            'cemail' => $request->cemail,
            'cname' => $request->cname,
            'cpassword' => Hash::make($request->cpassword),
            'ccompany' => $request->ccompany,
            'dcreated' => now(),
        ]);

        return redirect()->route('mowner.index')->with('success', 'Owner berhasil ditambahkan!');
    }

    public function show($id)
    {
        $owner = Mowner::findOrFail($id);
        return view('mowner.show', compact('owner'));
    }

    public function edit($id)
    {
        $owner = Mowner::findOrFail($id);
        return view('mowner.edit', compact('owner'));
    }

    public function update(Request $request, $id)
    {
        $owner = Mowner::findOrFail($id);

        $request->validate([
            'cemail' => 'required|email|unique:mowner,cemail,' . $id . ',nid',
            'cname' => 'required|string|max:255',
            'ccompany' => 'nullable|string|max:100',
        ]);

        $data = $request->only(['cemail', 'cname', 'ccompany']);

        if ($request->filled('cpassword')) {
            $data['cpassword'] = Hash::make($request->cpassword);
        }

        $owner->update($data);

        return redirect()->route('mowner.index')->with('success', 'Owner berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $owner = Mowner::findOrFail($id);
        $owner->delete();

        return redirect()->route('mowner.index')->with('success', 'Owner berhasil dihapus!');
    }
}
