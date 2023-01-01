<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AccountController extends Controller
{
    public function create_admin_account(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required'],
            'secret_key' => ['required']
        ]);



        if ($request->secret_key != '12345678') {
            return back()->withErrors(['secret_key' => 'Secret Key tidak valid'])->withInput();
        }
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->whatsapp_number = "";
        $user->type = 'superadmin';
        $user->save();

        return redirect()->route('login');
    }
}
