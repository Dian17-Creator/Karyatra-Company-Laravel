<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminDevice;
use App\Models\muser;

class AdminDeviceController extends Controller
{
    public function create()
    {
        $admins = muser::where(function ($q) {
            $q->where('fadmin', 1)
              ->orWhere('fsuper', 1)
              ->orWhere('fhrd', 1);
        })->orderBy('cname')->get();

        return view('admin_devices.create', compact('admins'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'admin_id'  => 'required|exists:muser,nid',
            'device_id' => 'required|string|max:191',
        ]);

        AdminDevice::create([
            'admin_id'        => $request->admin_id,
            'device_id'       => $request->device_id,
            'approval_status' => 'pending', // 🔑 WAJIB
            'is_active'       => 1,
            'last_used_at'    => now(),
        ]);

        return redirect()
            ->route('backoffice.index')
            ->with('success', 'Device berhasil ditambahkan (menunggu approval)');
    }

    public function toggle($id)
    {
        $device = AdminDevice::findOrFail($id);
        $device->is_active = !$device->is_active;
        $device->save();

        return back()->with('success', 'Status device diperbarui');
    }

    public function destroy($id)
    {
        AdminDevice::findOrFail($id)->delete();

        return back()->with('success', 'Device dihapus');
    }

    public function approve($id)
    {
        $device = AdminDevice::findOrFail($id);

        $device->update([
            'approval_status' => 'approved',
            'is_active' => 1
        ]);

        return back()->with('success', 'Device berhasil disetujui');
    }

    public function reject($id)
    {
        $device = AdminDevice::findOrFail($id);

        $device->update([
            'approval_status' => 'rejected',
            'is_active' => 0
        ]);

        return back()->with('success', 'Device ditolak');
    }

}
