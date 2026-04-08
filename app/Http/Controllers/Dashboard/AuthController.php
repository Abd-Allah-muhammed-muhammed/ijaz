<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Auth\DashboardLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function loginForm()
    {
        return inertia('Dashboard/Auth/LoginPage');
    }

    public function login(DashboardLoginRequest $request)
    {

        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.home', absolute: false));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
