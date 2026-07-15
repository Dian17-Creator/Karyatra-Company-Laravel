<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\muser;
use App\Models\Mowner;
use App\Models\Mcompany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


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
            'ccompany'  => 'required|string|max:100',
            'cname'     => 'required|string|max:255',
            'cpassword' => 'required|string|min:6',
        ]);

        $domain = Str::of($request->ccompany)
            ->lower()
            ->replaceMatches('/^(pt|cv)\s+/i', '')
            ->replaceMatches('/[^a-z0-9]/', '');

        $username = Str::of($request->cname)
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '');

        $email = "{$username}@{$domain}";

        if (Mcompany::where('cname', $request->ccompany)->exists()) {
            return back()
                ->withErrors([
                    'ccompany' => 'Nama perusahaan sudah terdaftar.'
                ])
                ->withInput();
        }

        if (Mcompany::where('cemail', $domain)->exists()) {
            return back()
                ->withErrors([
                    'ccompany' => 'Domain @ perusahaan sudah digunakan.'
                ])
                ->withInput();
        }

        if (
            Mowner::where('cemail', $email)->exists() ||
            muser::where('cemail', $email)->exists()
        ) {
            return back()
                ->withErrors([
                    'cname' => 'Nama tersebut sudah digunakan pada perusahaan ini.'
                ])
                ->withInput();
        }

        DB::transaction(function () use ($request, $domain, $email) {

            Mcompany::create([
                'cname'  => $request->ccompany,
                'cemail' => $domain,
            ]);

            Mowner::create([
                'ccompany'  => $request->ccompany,
                'cname'     => $request->cname,
                'cemail'    => $email,
                'cpassword' => Hash::make($request->cpassword),
                'dcreated'  => now(),
            ]);

            muser::create([
                'ccompany'  => $request->ccompany,
                'cname'     => $request->cname,
                'cemail'    => $email,
                'cpassword' => Hash::make($request->cpassword),
                'dcreated'  => now(),
                'fadmin'    => 1,
                'fsuper'    => 1,
                'fhrd'      => 1,
                'factive'   => 1,
            ]);
        });

        return redirect('/login')
            ->with('success', 'Registrasi berhasil! Silakan login.');
    }
}
