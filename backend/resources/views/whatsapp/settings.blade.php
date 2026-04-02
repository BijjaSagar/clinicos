@extends('layouts.app')

@section('title', 'WhatsApp Settings')

@section('content')
<div class="max-w-5xl mx-auto space-y-8" x-data="whatsappSettings()">

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">WhatsApp Configuration</h1>
        <p class="mt-1 text-sm text-gray-500">Connect your Meta WhatsApp Business API, manage templates, and configure automation.</p>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         SECTION 1: Meta API Credentials
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Meta API Credentials
            </h2>
            <p class="text-sm text-gray-500 mt-1">Enter your WhatsApp Business API credentials from the Meta Developer Console.</p>
        </div>
        <form action="{{ route('whatsapp-settings.save-credentials') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Phone Number ID --}}
                <div>
                    <label for="phone_number_id" class="block text-sm font-medium text-gray-700 mb-1">Phone Number ID <span class="text-red-500">*</span></label>
                    <input type="text" name="phone_number_id" id="phone_number_id"
                           value="{{ old('phone_number_id', $whatsapp['phone_number_id'] ?? '') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-blue focus:ring-brand-blue text-sm"
                           placeholder="e.g. 123456789012345" required>
                    @error('phone_number_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- WABA ID --}}
                <div>
                    <label for="waba_id" class="block text-sm font-medium text-gray-700 mb-1">WABA ID</label>
                    <input type="text" name="waba_id" id="waba_id"
                           value="{{ old('waba_id', $whatsapp['waba_id'] ?? '') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-blue focus:ring-brand-blue text-sm"
                           placeholder="WhatsApp Business Account ID">
                </div>
            </div>

            {{-- Access Token --}}
            <div x-data="{ showToken: false }">
                <label for="access_token" class="block text-sm font-medium text-gray-700 mb-1">Permanent Access Token <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input :type="showToken ? 'text' : 'password'" name="access_token" id="access_token"
                           value="{{ old('access_token', $whatsapp['access_token'] ?? '') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-blue focus:ring-brand-blue text-sm pr-20"
                           placeholder="EAAxxxxxxx..." required>
                    <button type="button" @click="showToken = !showToken"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-sm text-gray-500 hover:text-gray-700">
                        <span x-text="showToken ? 'Hide' : 'Show'"></span>
                    </button>
                </div>
                @error('access_token') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- App Secret --}}
                <div>
                    <label for="app_secret" class="block text-sm font-medium text-gray-700 mb-1">App Secret</label>
                    <input type="text" name="app_secret" id="app_secret"
                           value="{{ old('app_secret', $whatsapp['app_secret'] ?? '') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-blue focus:ring-brand-blue text-sm"
                           placeholder="For webhook signature verification">
                    <p class="text-xs text-gray-400 mt-1">Used to verify webhook payload signatures.</p>
                </div>

                {{-- Verify Token --}}
                <div>
                    <label for="verify_token" class="block text-sm font-medium text-gray-700 mb-1">Verify Token</label>
                    <input type="text" name="verify_token" id="verify_token"
                           value="{{ old('verify_token', $whatsapp['verify_token'] ?? $verifyToken) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-blue focus:ring-brand-blue text-sm"
                           placeholder="clinicos_webhook_verify">
                    <p class="text-xs text-gray-400 mt-1">Must match the token you enter in Meta webhook config.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-blue text-white text-sm font-semibold rounded-lg hover:bg-brand-blue-dark transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Save Credentials
                </button>

                <button type="button" @click="testConnection()"
                        :disabled="testing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50">
                    <svg x-show="!testing" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <svg x-show="testing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="testing ? 'Testing...' : 'Test Connection'"></span>
                </button>

                {{-- Test result badge --}}
                <template x-if="testResult">
                    <span :class="testResult.success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                          x-text="testResult.message"></span>
                </template>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         SECTION 2: Webhook Configuration
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Webhook Configuration
            </h2>
            <p class="text-sm text-gray-500 mt-1">Use these values when configuring the webhook in Meta Developer Console.</p>
        </div>
        <div class="p-6 space-y-5">
            {{-- Webhook URL --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Callback URL</label>
                <div class="flex items-center gap-2" x-data="{ copied: false }">
                    <input type="text" readonly value="{{ $webhookUrl }}"
                           class="flex-1 rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-600 cursor-text">
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ $webhookUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="relative inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                </div>
            </div>

            {{-- Verify Token --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Verify Token</label>
                <div class="flex items-center gap-2" x-data="{ copied: false }">
                    <input type="text" readonly value="{{ $verifyToken }}"
                           class="flex-1 rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-600 cursor-text">
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ $verifyToken }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="relative inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                </div>
            </div>

            {{-- Setup Instructions --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">Setup Instructions</h3>
                <ol class="text-sm text-blue-800 space-y-1.5 list-decimal list-inside">
                    <li>Go to <a href="https://developers.facebook.com" target="_blank" class="underline font-medium">Meta Developer Console</a> &rarr; Your App &rarr; WhatsApp &rarr; Configuration</li>
                    <li>Click <strong>"Edit"</strong> under Webhook</li>
                    <li>Paste the <strong>Callback URL</strong>: <code class="bg-blue-100 px-1 rounded text-xs">{{ $webhookUrl }}</code></li>
                    <li>Paste the <strong>Verify Token</strong>: <code class="bg-blue-100 px-1 rounded text-xs">{{ $verifyToken }}</code></li>
                    <li>Click <strong>"Verify and Save"</strong></li>
                    <li>Subscribe to: <code class="bg-blue-100 px-1 rounded text-xs">messages</code>, <code class="bg-blue-100 px-1 rounded text-xs">messaging_postbacks</code>, <code class="bg-blue-100 px-1 rounded text-xs">message_deliveries</code>, <code class="bg-blue-100 px-1 rounded text-xs">message_reads</code></li>
                </ol>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         SECTION 3: Message Templates
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
                    </svg>
                    Message Templates
                </h2>
                <p class="text-sm text-gray-500 mt-1">Pre-built templates for common clinic messages.</p>
            </div>
            <form action="{{ route('whatsapp-settings.seed-templates') }}" method="POST">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Seed Default Templates
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            @if($templates->count() > 0)
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Content</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Variables</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($templates as $template)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 font-medium text-gray-900 whitespace-nowrap">
                            {{ str_replace('_', ' ', $template->name) }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                            @php
                                $typeColors = [
                                    'appointment_reminder' => 'bg-blue-100 text-blue-800',
                                    'prescription' => 'bg-green-100 text-green-800',
                                    'follow_up' => 'bg-yellow-100 text-yellow-800',
                                    'birthday' => 'bg-pink-100 text-pink-800',
                                    'custom' => 'bg-gray-100 text-gray-800',
                                ];
                                $color = $typeColors[$template->type] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                {{ str_replace('_', ' ', $template->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 max-w-xs">
                            <span class="line-clamp-2">{{ Str::limit($template->content, 80) }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach(json_decode($template->variables ?? '[]', true) as $var)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono bg-gray-100 text-gray-600">@{{ {{ $var }} }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if($template->is_active ?? false)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">On</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Off</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="px-6 py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                <p class="text-gray-500 text-sm">No templates yet. Click <strong>"Seed Default Templates"</strong> to get started.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         SECTION 4: Automation Toggles
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Automation Toggles
            </h2>
            <p class="text-sm text-gray-500 mt-1">Enable or disable automated WhatsApp messages for various events.</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @php
                    $automations = [
                        ['type' => 'appointment_before_1d', 'label' => '24h Appointment Reminder', 'desc' => 'Send reminder 24 hours before appointment', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['type' => 'appointment_before_1h', 'label' => '2h Appointment Reminder', 'desc' => 'Send reminder 2 hours before appointment', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['type' => 'follow_up', 'label' => 'Follow-up Reminder', 'desc' => 'Remind patients about follow-up visits', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                        ['type' => 'birthday', 'label' => 'Birthday Greeting', 'desc' => 'Auto-send birthday wishes to patients', 'icon' => 'M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A1.75 1.75 0 003 15.546'],
                        ['type' => 'payment_reminder', 'label' => 'Payment Reminder', 'desc' => 'Notify patients of pending payments', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['type' => 'lab_results', 'label' => 'Lab Results Ready', 'desc' => 'Notify when lab results are available', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['type' => 'discharge_summary', 'label' => 'Discharge Summary', 'desc' => 'Send summary after patient discharge', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ];
                @endphp

                @foreach($automations as $auto)
                <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
                     x-data="{ active: {{ ($reminders[$auto['type']]->is_active ?? false) ? 'true' : 'false' }}, saving: false }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $auto['icon'] }}"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $auto['label'] }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $auto['desc'] }}</p>
                            </div>
                        </div>
                        <button type="button"
                                @click="saving = true; toggleReminder('{{ $auto['type'] }}', !active).then(() => { active = !active; saving = false; }).catch(() => saving = false)"
                                :class="active ? 'bg-green-500' : 'bg-gray-300'"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                :disabled="saving"
                                role="switch"
                                :aria-checked="active">
                            <span :class="active ? 'translate-x-5' : 'translate-x-0'"
                                  class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-0.5 ml-0.5"></span>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         SECTION 5: Quick Test
    ═══════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Quick Test
            </h2>
            <p class="text-sm text-gray-500 mt-1">Send a test message to verify your setup is working.</p>
        </div>
        <div class="p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                <div class="w-full sm:w-auto flex-1">
                    <label for="test_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" id="test_phone" x-model="testPhone"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-blue focus:ring-brand-blue text-sm"
                           placeholder="+91 98765 43210">
                </div>
                <div class="w-full sm:w-auto flex-1">
                    <label for="test_template" class="block text-sm font-medium text-gray-700 mb-1">Template</label>
                    <select id="test_template" x-model="testTemplate"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-blue focus:ring-brand-blue text-sm">
                        <option value="">Select a template...</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->name }}">{{ str_replace('_', ' ', $template->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" @click="sendTest()"
                        :disabled="sendingTest || !testPhone || !testTemplate"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 text-white text-sm font-semibold rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50 whitespace-nowrap">
                    <svg x-show="!sendingTest" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <svg x-show="sendingTest" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="sendingTest ? 'Sending...' : 'Send Test'"></span>
                </button>
            </div>
            <template x-if="testSendResult">
                <div :class="testSendResult.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                     class="mt-4 p-3 rounded-lg border text-sm" x-text="testSendResult.message"></div>
            </template>
        </div>
    </div>

</div>

<script>
function whatsappSettings() {
    return {
        testing: false,
        testResult: null,
        testPhone: '',
        testTemplate: '',
        sendingTest: false,
        testSendResult: null,

        async testConnection() {
            this.testing = true;
            this.testResult = null;
            try {
                const res = await fetch('{{ route("whatsapp-settings.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                this.testResult = await res.json();
            } catch (e) {
                this.testResult = { success: false, message: 'Network error: ' + e.message };
            }
            this.testing = false;
        },

        async toggleReminder(type, isActive) {
            const res = await fetch('{{ route("whatsapp-settings.toggle-reminder") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ type, is_active: isActive }),
            });
            if (!res.ok) throw new Error('Failed to toggle');
            return res.json();
        },

        async sendTest() {
            this.sendingTest = true;
            this.testSendResult = null;
            try {
                const res = await fetch('{{ route("whatsapp.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        phone: this.testPhone,
                        template: this.testTemplate,
                        test: true,
                    }),
                });
                const data = await res.json();
                this.testSendResult = { success: res.ok, message: data.message || (res.ok ? 'Test message sent!' : 'Failed to send') };
            } catch (e) {
                this.testSendResult = { success: false, message: 'Network error: ' + e.message };
            }
            this.sendingTest = false;
        },
    };
}
</script>
@endsection
