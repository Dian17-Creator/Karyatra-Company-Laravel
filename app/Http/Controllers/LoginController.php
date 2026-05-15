<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\muser;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login (auth/login.blade.php)
     */
    public function showLoginForm()
    {
        // kalau sudah login, langsung redirect ke backoffice
        if (Auth::check()) {
            return redirect('/backoffice');
        }

        return view('auth.login');
    }

    /**
     * Proses login
     */
    public function login(Request $request)
    {
        $request->validate([
            'cemail' => 'required|email',
            'cpassword' => 'required',
        ]);

        $user = \App\Models\muser::where('cemail', $request->cemail)->first();

        // jika user ditemukan dan password cocok (pakai bcrypt)
        if ($user && \Hash::check($request->cpassword, $user->cpassword)) {
            // login manual
            Auth::login($user);

            // cek role
            if ($user->fadmin == 1 || $user->fsuper == 1) {
                return redirect()->intended('/backoffice');
            }

            Auth::logout();
            return back()->withErrors(['cemail' => 'Akses hanya untuk Super Admin!']);
        }

        // kalau gagal
        return back()->withErrors(['cemail' => 'Email atau password salah.']);
    }


    /**
     * Logout user
     */
    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}
