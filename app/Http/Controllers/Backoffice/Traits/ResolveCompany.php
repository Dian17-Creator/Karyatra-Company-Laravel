<?php

namespace App\Http\Controllers\Backoffice\Traits;

use App\Models\muser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait ResolveCompany
{
    protected function resolveCcompany(Request $request): ?string
    {
        if ($request->filled('ccompany')) {
            return $request->input('ccompany');
        }
        if ($request->header('X-Company')) {
            return $request->header('X-Company');
        }

        $user = Auth::user() ?? Auth::guard('owner')->user();

        if (!$user) {
            $userId = $request->input('user_id')
                ?: $request->input('admin_id')
                ?: $request->input('creator_id')
                ?: $request->input('added_by')
                ?: $request->input('approver_id');

            if ($userId) {
                $user = muser::find($userId);
            }
        }

        if (!$user && $request->header('X-User-Id')) {
            $user = muser::find($request->header('X-User-Id'));
        }

        return $user ? $user->ccompany : null;
    }
}
