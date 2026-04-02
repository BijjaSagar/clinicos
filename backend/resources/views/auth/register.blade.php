@extends('layouts.guest')

@section('title', 'Register')

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

        {{-- Plan Cards --}}
        <div class="mb-6" x-data="{ plan: '{{ old('plan', 'professional') }}' }">
            <p class="text-center text-gray-300 text-sm font-medium mb-3">Choose your plan — all start with a <span class="text-cyan-400 font-bold">14-day free trial</span></p>
            <div class="grid grid-cols-3 gap-3">
                @php
                $plans = [
                    ['key'=>'starter',     'name'=>'Starter',     'price'=>'₹2,999', 'features'=>['EMR, Billing, WhatsApp']],
                    ['key'=>'professional','name'=>'Pro',         'price'=>'₹5,999', 'features'=>['+ Analytics, ABDM']],
                    ['key'=>'hospital',    'name'=>'Hospital',    'price'=>'₹14,999','features'=>['+ IPD, Pharmacy, Lab']],
                ];
                @endphp
                @foreach($plans as $p)
                <label class="cursor-pointer" x-on:click="plan = '{{ $p['key'] }}'">
                    <input type="radio" name="plan" value="{{ $p['key'] }}" class="sr-only"
                        {{ old('plan', 'professional') === $p['key'] ? 'checked' : '' }}>
                    <div class="rounded-xl border-2 p-3 text-center transition-all"
                         :class="plan === '{{ $p['key'] }}' ? 'border-cyan-400 bg-cyan-900/30' : 'border-gray-600 bg-gray-800/50 hover:border-gray-500'">
                        <div class="text-xs font-bold text-white">{{ $p['name'] }}</div>
                        <div class="text-lg font-extrabold text-cyan-400 leading-tight">{{ $p['price'] }}</div>
                        <div class="text-xs text-gray-400 leading-tight">/month</div>
                        <div class="text-xs text-gray-400 mt-1">{{ $p['features'][0] }}</div>
                    </div>
                </label>
                @endforeach
            </div>
            <p class="text-center text-gray-500 text-xs mt-2">Cancel anytime. No credit card required for trial.</p>
        </div>

        {{-- Register Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-1">Create your account</h2>
            <p class="text-gray-500 text-sm mb-6">14-day free trial, then your selected plan</p>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <ul class="text-red-700 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $error }}
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full name</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name"
                            value="{{ old('name') }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Dr. Sharma"
                            required
                        >
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                        <input 
                            type="tel" 
                            name="phone" 
                            id="phone"
                            value="{{ old('phone') }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="+91 98765 43210"
                            required
                        >
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email"
                        value="{{ old('email') }}"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="doctor@clinic.com"
                        required
                    >
                </div>

                <div>
                    <label for="clinic_name" class="block text-sm font-medium text-gray-700 mb-1.5">Clinic name</label>
                    <input 
                        type="text" 
                        name="clinic_name" 
                        id="clinic_name"
                        value="{{ old('clinic_name') }}"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Sharma Skin Clinic"
                        required
                    >
                </div>

                <div>
                    <label for="specialty" class="block text-sm font-medium text-gray-700 mb-1.5">Specialty</label>
                    <select 
                        name="specialty" 
                        id="specialty"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required
                    >
                        <option value="">Select specialty</option>
                        <option value="dermatology">Dermatology</option>
                        <option value="physiotherapy">Physiotherapy</option>
                        <option value="dental">Dental</option>
                        <option value="ophthalmology">Ophthalmology</option>
                        <option value="orthopedics">Orthopedics</option>
                        <option value="ent">ENT</option>
                        <option value="gynecology">Gynecology</option>
                        <option value="general">General Practice</option>
                    </select>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Minimum 8 characters"
                        required
                    >
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm password</label>
                    <input 
                        type="password" 
                        name="password_confirmation" 
                        id="password_confirmation"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Re-enter password"
                        required
                    >
                </div>

                <button 
                    type="submit"
                    class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors flex items-center justify-center gap-2"
                >
                    Create account
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-medium">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
