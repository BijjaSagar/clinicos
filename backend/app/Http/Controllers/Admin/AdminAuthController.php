<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        // If already logged in as super_admin, redirect to dashboard
        if (auth()->check() && auth()->user()->role === 'super_admin') {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        Log::info('Admin login attempt', ['email' => $credentials['email']]);

        // Check if user exists and is super_admin
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user || $user->role !== 'super_admin') {
            Log::warning('Admin login failed - not a super admin', ['email' => $credentials['email']]);
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Invalid credentials or insufficient privileges.');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            Log::info('Admin login successful', ['user_id' => auth()->id()]);
            
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Invalid credentials.');
    }

    public function logout(Request $request)
    {
        $userId = auth()->id();
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Admin logged out', ['user_id' => $userId]);

        return redirect()->route('admin.login');
    }

    public function stopImpersonating()
    {
        $adminId = session('impersonating_from');
        
        if (!$adminId) {
            return redirect()->route('dashboard');
        }

        $admin = User::find($adminId);
        
        if (!$admin || $admin->role !== 'super_admin') {
            session()->forget('impersonating_from');
            return redirect()->route('login');
        }

        Log::info('Stopping impersonation', ['admin_id' => $adminId, 'was_impersonating' => auth()->id()]);

        session()->forget('impersonating_from');
        auth()->login($admin);

        return redirect()->route('admin.dashboard')->with('success', 'Returned to admin panel.');
    }
}
