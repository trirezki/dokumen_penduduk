<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class AuthenticationController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember_me = $request->has('remember_me') ? true : false;


        if (auth()->attempt($credentials, $remember_me)) {
            $request->session()->regenerate();

            return redirect()->route('root', ['']);
        }

        return back()->withErrors([
            'email' => 'Email dan password tidak sesuai.',
        ]);
    }

    public function me() {
        return response()->json(User::with('head_of_institution')->find(auth()->id()), 200);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json("Aplikasi berhasil di logout", 200);
    }
}
