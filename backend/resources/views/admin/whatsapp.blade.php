@extends('admin.layouts.app')
@section('title', 'Global WhatsApp Settings')
@section('content')
<div class="p-6 max-w-4xl mx-auto space-y-6">

    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-500 flex items-center justify-center">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Global WhatsApp Settings</h1>
            <p class="text-gray-500 text-sm">These credentials are used by all {{ $totalClinics }} active clinics on the platform</p>
        </div>
        @if($config['is_configured'])
        <span class="ml-auto px-3 py-1 text-sm font-semibold bg-green-100 text-green-700 rounded-full flex items-center gap-1">
            <span class="w-2 h-2 bg-green-500 rounded-full"></span> Connected
        </span>
        @else
        <span class="ml-auto px-3 py-1 text-sm font-semibold bg-amber-100 text-amber-700 rounded-full">Not Configured</span>
        @endif
    </div>

    @if(session('success'))
    <div class="px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Credentials Form --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h2 class="font-semibold text-gray-900 mb-1">Meta WhatsApp Business API Credentials</h2>
        <p class="text-sm text-gray-500 mb-5">Get these from your <a href="https://developers.facebook.com" target="_blank" class="text-blue-600 underline">Meta Developer Console</a> &rarr; WhatsApp &rarr; API Setup</p>

        <form method="POST" action="{{ route('admin.whatsapp.save') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number ID <span class="text-red-500">*</span></label>
                    <input type="text" name="phone_number_id" value="{{ old('phone_number_id', $config['phone_number_id']) }}"
                        placeholder="1234567890123"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">WABA ID</label>
                    <input type="text" name="waba_id" value="{{ old('waba_id', $config['waba_id']) }}"
                        placeholder="WhatsApp Business Account ID"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Access Token <span class="text-red-500">*</span></label>
                    <input type="password" name="access_token" value="{{ old('access_token', $config['access_token']) }}"
                        placeholder="EAAxxxxxxxx..."
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">App Secret</label>
                    <input type="password" name="app_secret" value="{{ old('app_secret', $config['app_secret']) }}"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Verify Token</label>
                    <input type="text" name="verify_token" value="{{ old('verify_token', $config['verify_token']) }}"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-3 text-sm">
                <p class="font-medium text-gray-700 mb-1">Webhook URL (set this in Meta Developer Console):</p>
                <code class="text-xs text-blue-700 break-all">{{ $webhookUrl }}</code>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl"
                    style="background:linear-gradient(135deg,#1447E6,#0891B2);">Save Credentials</button>
                <button type="button" onclick="testWhatsApp()" class="px-5 py-2.5 text-sm font-semibold text-gray-700 border border-gray-300 rounded-xl hover:bg-gray-50">Test Connection</button>
            </div>
        </form>
    </div>

    {{-- Info box --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
        <p class="font-semibold mb-1">How it works:</p>
        <ul class="list-disc list-inside space-y-1 text-blue-700">
            <li>These credentials are shared across all clinics on ClinicOS</li>
            <li>Appointment reminders, lab results, prescriptions are sent automatically</li>
            <li>Each clinic's messages show their own clinic name in the message body</li>
            <li>Individual clinics cannot override these credentials</li>
        </ul>
    </div>
</div>

<script>
async function testWhatsApp() {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Testing...';
    try {
        const res = await fetch('{{ route("admin.whatsapp.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        alert(data.message);
    } catch(e) {
        alert('Request failed');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Test Connection';
    }
}
</script>
@endsection
