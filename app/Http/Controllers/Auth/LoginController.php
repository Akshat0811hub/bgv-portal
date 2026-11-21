<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function showClientLogin() { return view('auth.login'); }
    public function showAdminLogin()  { return view('auth.admin-login'); }

    public function clientLogin(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!auth()->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput($request->only('email'));
        }

        $user = auth()->user();

        if (!$user->is_verified) {
            auth()->logout();
            return back()->withErrors(['email' => 'Your account is pending verification.'])->withInput($request->only('email'));
        }

        if (!$user->isClient()) {
            auth()->logout();
            return back()->withErrors(['email' => 'Please use the admin portal to log in.'])->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        if ($user->password_reset_required) {
            return redirect()->route('password.set');
        }

        return redirect()->route('client.dashboard');
    }

    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!auth()->attempt($credentials)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput($request->only('email'));
        }

        $user = auth()->user();

        if (!$user->isAdmin()) {
            auth()->logout();
            return back()->withErrors(['email' => 'Access denied. Not an admin account.'])->withInput($request->only('email'));
        }

        if (!$user->is_verified) {
            auth()->logout();
            return back()->withErrors(['email' => 'Your account is pending verification or has been deactivated.'])->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        if ($user->password_reset_required) {
            return redirect()->route('password.set');
        }

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $isAdmin = auth()->user()?->isAdmin();
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route($isAdmin ? 'admin.login' : 'login');
    }
}
