<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    // ─── Login ───────────────────────────────────────────────────────────────

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            // First check if user exists and is active
            $user = User::where('email', $credentials['email'])->first();
            
            if ($user && !$user->is_active) {
                Log::warning('Inactive user tried to login', ['user_id' => $user->id]);
                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->with('error', 'Your account has been deactivated. Please contact support.');
            }
            
            // Super admins should use /admin/login
            if ($user && $user->role === 'super_admin') {
                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->with('error', 'Please use the admin login page.');
            }
            
            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                $request->session()->regenerate();

                Log::info('User logged in via web', ['user_id' => Auth::id()]);

                // Role-aware redirect after login
                $authUser = Auth::user();
                $redirectRoute = match($authUser->role) {
                    'lab_technician' => 'lab.technician.dashboard',
                    'pharmacist'     => 'pharmacy.index',
                    'receptionist'   => 'opd.queue',
                    'nurse'          => 'ipd.index',
                    default          => 'dashboard', // owner, doctor, staff
                };

                $target = \Illuminate\Support\Facades\Route::has($redirectRoute)
                    ? route($redirectRoute)
                    : route('dashboard');

                return redirect()->intended($target);
            }

            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'These credentials do not match our records.');
        } catch (\Throwable $e) {
            Log::error('Login error', ['error' => $e->getMessage()]);

            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'An unexpected error occurred. Please try again.');
        }
    }

    // ─── Register ─────────────────────────────────────────────────────────────

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clinic_name' => ['required', 'string', 'max:255'],
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'unique:users,email'],
            'phone'       => ['required', 'string', 'max:15'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            // Create the clinic first
            $clinic = Clinic::create([
                'name'    => $validated['clinic_name'],
                'slug'    => Str::slug($validated['clinic_name']) . '-' . Str::random(6),
                'plan'    => 'trial',
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
            ]);

            // Create admin user tied to the clinic
            $user = User::create([
                'clinic_id' => $clinic->id,
                'name'      => $validated['name'],
                'email'     => $validated['email'],
                'phone'     => $validated['phone'],
                'password'  => Hash::make($validated['password']),
                'role'      => 'admin',
                'is_active' => true,
            ]);

            // Update clinic's owner reference
            $clinic->update(['owner_user_id' => $user->id]);

            Auth::login($user);
            $request->session()->regenerate();

            Log::info('New clinic registered via web', [
                'clinic_id' => $clinic->id,
                'user_id'   => $user->id,
            ]);

            return redirect()->route('dashboard')->with('success', 'Welcome to ClinicOS! Your 30-day trial has started.');
        } catch (\Throwable $e) {
            Log::error('Registration error', ['error' => $e->getMessage()]);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'Registration failed. Please try again.');
        }
    }

    // ─── Forgot Password ──────────────────────────────────────────────────────

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('status', __($status));
            }

            return back()
                ->withInput($request->only('email'))
                ->with('error', __($status));
        } catch (\Throwable $e) {
            Log::error('Forgot password error', ['error' => $e->getMessage()]);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Could not send reset link. Please try again.');
        }
    }

    // ─── Reset Password ───────────────────────────────────────────────────────

    public function showResetPassword(string $token): View
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password'       => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return redirect()->route('login')->with('status', __($status));
            }

            return back()
                ->withInput($request->only('email'))
                ->with('error', __($status));
        } catch (\Throwable $e) {
            Log::error('Reset password error', ['error' => $e->getMessage()]);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Password reset failed. Please try again.');
        }
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('User logged out via web', ['user_id' => $userId]);

        return redirect()->route('login');
    }
}
