<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\muser;
use App\Models\mdepartment;
use App\Models\Mrekening;
use App\Models\AdminDevice;
use App\Models\Tdeptlokasi;
use App\Models\Mcompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user() ?? Auth::guard('owner')->user();
        $query = muser::with(['department', 'rekening'])->orderBy('cname');

        if ($authUser && $authUser->ccompany) {
            $query->where('ccompany', $authUser->ccompany);
        }

        if (!$authUser->fhrd) {
            $query->where('niddept', $authUser->niddept);
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('cname', 'like', "%{$keyword}%")
                    ->orWhere('cemail', 'like', "%{$keyword}%")
                    ->orWhere('cfullname', 'like', "%{$keyword}%")
                    ->orWhereHas('department', function ($q2) use ($keyword) {
                        $q2->where('cname', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('dept')) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('cname', $request->dept);
            });
        }

        $status = $request->input('status', '1');
        if ($status !== null && $status !== '') {
            $query->where('factive', $status);
        }

        $users = $query->paginate(10)->withQueryString();

        $departmentsQuery = mdepartment::orderBy('nid');
        if ($authUser && $authUser->ccompany) {
            $departmentsQuery->where('ccompany', $authUser->ccompany);
        }
        $departments = $departmentsQuery->get();

        $rekeningsQuery = Mrekening::orderBy('id');
        if ($authUser && $authUser->ccompany) {
            $rekeningsQuery->where('ccompany', $authUser->ccompany);
        }
        $rekenings = $rekeningsQuery->get();

        $user = Auth::user() ?? Auth::guard('owner')->user();
        $deptLocationsQuery = Tdeptlokasi::with('department')
            ->orderBy('ndeptid', 'asc');

        if ($user && $user->ccompany) {
            $deptLocationsQuery->where('ccompany', $user->ccompany);
        }

        $deptLocations = $deptLocationsQuery->paginate(10, ['*'], 'dept_page')
            ->withQueryString();

        if ($request->ajax()) {
            if ($request->header('X-Component') === 'master_deptlokasi') {
                return view('backoffice.component.master_deptlokasi', compact('deptLocations', 'departments'))->render();
            }

            return view('backoffice.component.master_user', compact(
                'users',
                'departments',
                'rekenings'
            ))->render();
        }

        $devices = AdminDevice::with('user')
            ->orderByDesc('created_at')
            ->get();

        $adminsQuery = muser::where(function ($q) {
            $q->where('fadmin', 1)
                ->orWhere('fsuper', 1)
                ->orWhere('fhrd', 1);
        });
        if ($authUser && $authUser->ccompany) {
            $adminsQuery->where('ccompany', $authUser->ccompany);
        }
        $admins = $adminsQuery->orderBy('cname')->get();

        // Ambil data company milik user yang login
        $company = Mcompany::where('cname', $authUser->ccompany)->first();

        return view('backoffice.index', compact(
            'users',
            'departments',
            'rekenings',
            'devices',
            'admins',
            'deptLocations',
            'company'
        ));
    }
}
