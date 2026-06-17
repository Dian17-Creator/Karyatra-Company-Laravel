<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tdeptlokasi;
use App\Models\mdepartment;

class TdeptlokasiController extends Controller
{
    /**
     * Display a listing of the locations.
     */
    public function index(Request $request)
    {
        $locations = Tdeptlokasi::with('department')->orderBy('nid', 'desc')->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $locations
            ]);
        }

        return view('tdeptlokasi.index', compact('locations'));
    }

    /**
     * Store a newly created location in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ndeptid' => 'required|integer|exists:mdepartment,nid',
            'cssid'   => 'nullable|string|max:191',
            'nlat'    => 'nullable|numeric',
            'nlng'    => 'nullable|numeric',
            'nradius' => 'nullable|numeric',
        ]);

        // Auto-fill dcreated with current date & time
        $validated['dcreated'] = now();

        $location = Tdeptlokasi::create($validated);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Lokasi departemen berhasil ditambahkan.',
                'data' => $location
            ], 201);
        }

        return redirect()->back()->with('success', 'Lokasi departemen berhasil ditambahkan.');
    }

    /**
     * Display the specified location.
     */
    public function show(Request $request, $id)
    {
        $location = Tdeptlokasi::with('department')->findOrFail($id);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $location
            ]);
        }

        return view('tdeptlokasi.show', compact('location'));
    }

    /**
     * Update the specified location in storage.
     */
    public function update(Request $request, $id)
    {
        $location = Tdeptlokasi::findOrFail($id);

        $validated = $request->validate([
            'ndeptid' => 'required|integer|exists:mdepartment,nid',
            'cssid'   => 'nullable|string|max:191',
            'nlat'    => 'nullable|numeric',
            'nlng'    => 'nullable|numeric',
            'nradius' => 'nullable|numeric',
        ]);

        $location->update($validated);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Lokasi departemen berhasil diperbarui.',
                'data' => $location
            ]);
        }

        return redirect()->back()->with('success', 'Lokasi departemen berhasil diperbarui.');
    }

    /**
     * Remove the specified location from storage.
     */
    public function destroy(Request $request, $id)
    {
        $location = Tdeptlokasi::findOrFail($id);
        $location->delete();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Lokasi departemen berhasil dihapus.'
            ]);
        }

        return redirect()->back()->with('success', 'Lokasi departemen berhasil dihapus.');
    }
}
