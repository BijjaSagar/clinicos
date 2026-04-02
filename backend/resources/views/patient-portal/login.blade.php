<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal — Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center p-4">

<div class="w-full max-w-sm" x-data="{ step: '{{ session('otp_sent') ? 'otp' : 'phone' }}' }">

    {{-- Logo / Header --}}
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-blue-600 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Patient Portal</h1>
        <p class="text-sm text-gray-500 mt-1">Access your health records securely</p>
    </div>

    @if(session('error'))
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
        {{ session('error') }}
    </div>
    @endif

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

        {{-- Step 1: Phone --}}
        <div x-show="step === 'phone'">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Enter your mobile number</h2>
            <p class="text-sm text-gray-500 mb-5">We'll send a 6-digit OTP to verify your identity.</p>

            <form action="{{ route('patient-portal.send-otp') }}" method="POST" @submit="step='otp'">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Mobile Number</label>
                    <input type="tel" name="phone" required autofocus
                           placeholder="e.g. 9876543210"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors text-sm">
                    Send OTP
                </button>
            </form>
        </div>

        {{-- Step 2: OTP --}}
        <div x-show="step === 'otp'" x-cloak>
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Verify OTP</h2>
            @if(session('patient_name'))
            <p class="text-sm text-gray-500 mb-5">OTP sent to <strong>{{ session('patient_name') }}</strong>'s registered number.</p>
            @else
            <p class="text-sm text-gray-500 mb-5">Enter the 6-digit OTP sent to your registered number.</p>
            @endif

            <form action="{{ route('patient-portal.verify-otp') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">6-Digit OTP</label>
                    <input type="text" name="otp" required maxlength="6" autofocus
                           placeholder="000000"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-center text-2xl tracking-[0.5em] font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    @error('otp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-colors text-sm">
                    Verify &amp; Login
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-4">
                Didn't receive it?
                <button @click="step='phone'" class="text-blue-600 hover:underline font-medium">Resend OTP</button>
            </p>
        </div>

    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        For medical emergencies, call your clinic directly.
    </p>

</div>
</body>
</html>
