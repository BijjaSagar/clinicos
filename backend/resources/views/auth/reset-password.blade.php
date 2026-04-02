@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
<div class="p-8">
    <h2 class="text-xl font-bold text-gray-900 mb-1">Reset your password</h2>
    <p class="text-gray-500 text-sm mb-6">Enter your new password below.</p>

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-2 text-red-700 text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-2 text-red-700 text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $errors->first() }}
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ request()->query('email', old('email')) }}">

        <div>
            <label for="email_display" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
            <input
                type="email"
                id="email_display"
                value="{{ request()->query('email', old('email')) }}"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-500"
                disabled
            >
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">New password</label>
            <input
                type="password"
                name="password"
                id="password"
                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                placeholder="Min. 8 characters"
                required
                autofocus
            >
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm password</label>
            <input
                type="password"
                name="password_confirmation"
                id="password_confirmation"
                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                placeholder="Re-enter your password"
                required
            >
        </div>

        <button
            type="submit"
            class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors flex items-center justify-center gap-2"
        >
            Reset Password
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">&larr; Back to login</a>
    </div>
</div>
@endsection
