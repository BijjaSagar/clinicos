@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-4">
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-white text-xl font-display"
                     style="background: linear-gradient(135deg, #1447E6 0%, #0891B2 100%);">
                    C
                </div>
                <div class="text-left">
                    <h1 class="text-2xl font-bold text-white font-display">ClinicOS</h1>
                    <p class="text-gray-400 text-sm">क्लिनिक ओएस</p>
                </div>
            </div>
        </div>

        {{-- Login Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-1">Welcome back</h2>
            <p class="text-gray-500 text-sm mb-6">Sign in to access your clinic dashboard</p>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <div class="flex items-center gap-2 text-red-700 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email"
                        value="{{ old('email') }}"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        placeholder="doctor@clinic.com"
                        required
                        autofocus
                    >
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700">Forgot password?</a>
                    </div>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        name="remember" 
                        id="remember"
                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    >
                    <label for="remember" class="ml-2 text-sm text-gray-600">Remember me for 30 days</label>
                </div>

                <button 
                    type="submit"
                    class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors flex items-center justify-center gap-2"
                >
                    Sign in
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    Don't have an account? 
                    <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-medium">Create one</a>
                </p>
            </div>
        </div>

        {{-- Demo credentials --}}
        <div class="mt-6 p-4 bg-white/10 backdrop-blur rounded-xl">
            <p class="text-sm text-gray-300 text-center mb-2">Demo Credentials</p>
            <div class="text-xs text-gray-400 text-center space-y-1">
                <p>Email: <code class="bg-white/20 px-1.5 py-0.5 rounded">demo@clinicos.com</code></p>
                <p>Password: <code class="bg-white/20 px-1.5 py-0.5 rounded">password</code></p>
            </div>
        </div>
    </div>
</div>
@endsection
