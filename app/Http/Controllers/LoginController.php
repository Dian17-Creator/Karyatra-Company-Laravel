<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\muser;
use App\Models\Mowner;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect('/backoffice');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'cemail' => 'required|email',
            'cpassword' => 'required',
        ]);

        $owner = Mowner::where('cemail', $request->cemail)->first();
        if ($owner && Hash::check($request->cpassword, $owner->cpassword)) {
            Auth::guard('owner')->login($owner);
            return redirect()->intended('/backoffice');
        }

        $user = muser::where('cemail', $request->cemail)->first();
        if ($user && Hash::check($request->cpassword, $user->cpassword)) {
            if ($user->fadmin == 1 || $user->fsuper == 1) {
                Auth::login($user);
                return redirect()->intended('/backoffice');
            }

            return back()->withErrors(['cemail' => 'Akses hanya untuk Super Admin!']);
        }

        return back()->withErrors(['cemail' => 'Email atau password salah.']);
    }


    public function logout()
    {
        Auth::guard('web')->logout();
        Auth::guard('owner')->logout();
        return redirect('/login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'ccompany' => 'required|string|max:100',
            'cname' => 'required|string|max:255',
            'cemail' => 'required|email|unique:mowner,cemail|unique:muser,cemail',
            'cpassword' => 'required|string',
        ]);

        \DB::transaction(function () use ($request) {
            Mowner::create([
                'ccompany' => $request->ccompany,
                'cname' => $request->cname,
                'cemail' => $request->cemail,
                'cpassword' => Hash::make($request->cpassword),
                'dcreated' => now(),
            ]);

            muser::create([
                'ccompany' => $request->ccompany,
                'cname' => $request->cname,
                'cemail' => $request->cemail,
                'cpassword' => Hash::make($request->cpassword),
                'dcreated' => now(),
                'fadmin' => 1,
                'fsuper' => 1,
                'fhrd' => 1,
                'factive' => 1,
            ]);
        });

        // Opsional: Langsung login setelah daftar
        // Auth::login($owner); 
        // Note: Mowner harus implement Authenticatable jika ingin pakai Auth::login($owner)

        return redirect('/login')->with('success', 'Registrasi berhasil! Silakan login.');
    }
}
