<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Attempt login with remember me support
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect based on role
            switch ($user->role) {
                case 'owner':
                    return redirect()->intended(route('owner.dashboard'));
                case 'admin':
                    return redirect()->intended(route('admin.dashboard'));
                case 'pm':
                    return redirect()->intended(route('pm.dashboard'));
                case 'employee':
                    return redirect()->intended(route('employee.dashboard'));
                default:
                    Auth::logout();
                    return back()->withErrors([
                        'username' => 'Your account role is not recognized.',
                    ]);
            }
        }

        return back()
            ->with('auth_error', 'Invalid username or password.')
            ->onlyInput(['username', 'remember']);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
