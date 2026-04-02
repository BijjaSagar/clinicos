@extends('admin.layouts.app')

@section('title', 'WhatsApp Settings')
@section('subtitle', 'Platform-level WhatsApp Business API configuration')

@section('content')
<div class="space-y-6 max-w-4xl">

    {{-- Stats bar --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Total Clinics</p>
            <p class="text-2xl font-bold text-gray-900">{{ $clinicStats->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">WhatsApp Configured</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $configuredCount }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Not Configured</p>
            <p class="text-2xl font-bold text-amber-500">{{ $clinicStats->count() - $configuredCount }}</p>
        </div>
    </div>

    {{-- Platform Credentials --}}
    <form method="POST" action="{{ route('admin.whatsapp-settings.save') }}">
        @csrf
        <div class="bg-white rounded-xl border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Platform WhatsApp Credentials</h3>
                    <p class="text-xs text-gray-500">These defaults are used by clinics that haven't configured their own WABA credentials.</p>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number ID <span class="text-red-500">*</span></label>
                        <input type="text" name="wa_phone_number_id"
                            value="{{ old('wa_phone_number_id', $settings['wa_phone_number_id']) }}"
                            placeholder="e.g. 123456789012345"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">From Meta Business Manager → WhatsApp → API Setup</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">WABA ID</label>
                        <input type="text" name="wa_waba_id"
                            value="{{ old('wa_waba_id', $settings['wa_waba_id']) }}"
                            placeholder="e.g. 987654321098765"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">WhatsApp Business Account ID</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Access Token (Permanent)</label>
                    <input type="password" name="wa_access_token"
                        value="{{ old('wa_access_token', $settings['wa_access_token']) }}"
                        placeholder="EAAxxxxxxxxxxxxx..."
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                    <p class="text-xs text-gray-400 mt-1">System user permanent access token from Meta Business Manager</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">App Secret</label>
                        <input type="password" name="wa_app_secret"
                            value="{{ old('wa_app_secret', $settings['wa_app_secret']) }}"
                            placeholder="App secret for webhook verification"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Webhook Verify Token</label>
                        <input type="text" name="wa_verify_token"
                            value="{{ old('wa_verify_token', $settings['wa_verify_token']) }}"
                            placeholder="clinicos_webhook_verify"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                    </div>
                </div>

                {{-- Webhook URL --}}
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <p class="text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide">Webhook URL (configure in Meta)</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 text-sm text-indigo-700 font-mono bg-white px-3 py-2 rounded-lg border border-gray-200 truncate">{{ $webhookUrl }}</code>
                        <button type="button"
                            onclick="navigator.clipboard.writeText('{{ $webhookUrl }}').then(() => this.textContent = 'Copied!').catch(() => {}); setTimeout(() => this.textContent = 'Copy', 1500)"
                            class="px-3 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors whitespace-nowrap">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
            <div class="px-6 pb-6">
                <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Save WhatsApp Settings
                </button>
            </div>
        </div>
    </form>

    {{-- Per-clinic overview --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Per-Clinic WhatsApp Status</h3>
            <p class="text-xs text-gray-500 mt-0.5">Clinics that have configured their own WhatsApp credentials override the platform defaults.</p>
        </div>
        <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
            @forelse($clinicStats as $clinic)
            <div class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-700">
                        {{ strtoupper(substr($clinic->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $clinic->name }}</p>
                        @if($clinic->wa_configured)
                            <p class="text-xs text-gray-400 font-mono">ID: {{ $clinic->whatsapp_phone_number_id }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if(!$clinic->is_active)
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                    <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $clinic->wa_configured ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $clinic->wa_configured ? 'Configured' : 'Not configured' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="px-6 py-8 text-center text-sm text-gray-500">No clinics found.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
