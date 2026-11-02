<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function showForgot()  { return view('auth.forgot-password'); }
    public function showReset(string $token) { return view('auth.reset-password', compact('token')); }
    public function showSetPassword() { return view('auth.set-password'); }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        \Illuminate\Support\Facades\Password::sendResetLink($request->only('email'));
        return back()->with('status', 'If that email exists, a reset link has been sent.');
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'min:8', 'confirmed', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
        ]);

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        return $status === \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Password reset successfully. Please log in.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function setPassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'min:8', 'confirmed', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
        ]);
        $user = auth()->user();
        $user->forceFill(['password' => $request->password, 'password_reset_required' => false])->save();

        $home = $user->isAdmin() ? 'admin.dashboard' : 'client.dashboard';
        return redirect()->route($home)->with('success', 'Password updated successfully.');
    }
}
