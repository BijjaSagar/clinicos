<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new clinic and owner
     */
    public function register(Request $request): JsonResponse
    {
        Log::info('Registration attempt', ['email' => $request->email]);

        $validated = $request->validate([
            'clinic_name' => 'required|string|max:200',
            'clinic_slug' => 'required|string|max:100|unique:clinics,slug',
            'specialties' => 'required|array|min:1',
            'name' => 'required|string|max:200',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'password' => 'required|string|min:8|confirmed',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
        ]);

        Log::info('Registration validation passed', ['email' => $validated['email']]);

        // Create clinic
        $clinic = Clinic::create([
            'name' => $validated['clinic_name'],
            'slug' => $validated['clinic_slug'],
            'specialties' => $validated['specialties'],
            'city' => $validated['city'] ?? 'Pune',
            'state' => $validated['state'] ?? 'Maharashtra',
            'plan' => 'solo',
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
        ]);

        Log::info('Clinic created', ['clinic_id' => $clinic->id]);

        // Create owner user
        $user = User::create([
            'clinic_id' => $clinic->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'owner',
            'is_active' => true,
        ]);

        Log::info('User created', ['user_id' => $user->id, 'role' => 'owner']);

        // Update clinic owner
        $clinic->update(['owner_user_id' => $user->id]);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Registration successful', ['user_id' => $user->id, 'clinic_id' => $clinic->id]);

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user->load('clinic'),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login
     */
    public function login(Request $request): JsonResponse
    {
        Log::info('Login attempt', ['email' => $request->email]);

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            Log::warning('Login failed - user not found', ['email' => $validated['email']]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            Log::warning('Login failed - wrong password', ['email' => $validated['email']]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            Log::warning('Login failed - user inactive', ['user_id' => $user->id]);
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Login successful', ['user_id' => $user->id, 'clinic_id' => $user->clinic_id]);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user->load('clinic'),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        Log::info('Token refresh', ['user_id' => $request->user()->id]);

        $user = $request->user();
        
        // Revoke current token
        $request->user()->currentAccessToken()->delete();
        
        // Generate new token
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Token refreshed', ['user_id' => $user->id]);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        Log::info('Logout', ['user_id' => $request->user()->id]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get current user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('clinic');
        
        Log::info('Get current user', ['user_id' => $user->id]);

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Forgot password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        Log::info('Forgot password request', ['email' => $request->email]);

        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Password reset link sent', ['email' => $request->email]);
            return response()->json([
                'message' => 'Password reset link sent to your email.',
            ]);
        }

        Log::warning('Failed to send password reset link', ['email' => $request->email, 'status' => $status]);
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        Log::info('Password reset attempt', ['email' => $request->email]);

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                Log::info('Password reset successful', ['user_id' => $user->id]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully.',
            ]);
        }

        Log::warning('Password reset failed', ['email' => $request->email, 'status' => $status]);
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
