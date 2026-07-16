<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Backoffice\Traits\ResolveCompany;
use App\Models\Mrekening;
use Illuminate\Http\Request;

class BankController extends Controller
{
    use ResolveCompany;

    public function apiBankList(Request $request)
    {
        $ccompany = $this->resolveCcompany($request);
        $query    = Mrekening::select('bank');
        if ($ccompany) {
            $query->where('ccompany', $ccompany);
        }
        $banks = $query->distinct()->pluck('bank');

        return response()->json([
            'success' => true,
            'data'    => $banks
        ]);
    }

    public function apiMandiriRekening(Request $request)
    {
        $ccompany = $this->resolveCcompany($request);
        $query    = Mrekening::where('bank', 'Mandiri')->orderBy('nomor_rekening');
        if ($ccompany) {
            $query->where('ccompany', $ccompany);
        }
        $data = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }
}
