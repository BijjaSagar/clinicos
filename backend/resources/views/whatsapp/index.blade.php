@extends('layouts.app')

@section('title', 'WhatsApp')
@section('breadcrumb', 'WhatsApp Automation')

@section('content')
<div class="p-6 space-y-6" x-data="whatsappDashboard()">
    @if(session('success'))
        <div class="rounded-xl bg-green-50 text-green-800 px-4 py-3 text-sm border border-green-100">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl bg-red-50 text-red-800 px-4 py-3 text-sm border border-red-100">{{ session('error') }}</div>
    @endif
    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Sent Today</p>
                    <p class="text-xl font-bold text-gray-900">{{ $stats['sent_today'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Received Today</p>
                    <p class="text-xl font-bold text-gray-900">{{ $stats['received_today'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Pending Replies</p>
                    <p class="text-xl font-bold text-amber-600">{{ $stats['pending_replies'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Scheduled</p>
                    <p class="text-xl font-bold text-purple-600">{{ $stats['reminders_scheduled'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Templates</p>
                    <p class="text-xl font-bold text-teal-600">{{ $stats['templates_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(isset($upcomingAppointments) && $upcomingAppointments->count() > 0)
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-900 mb-2">Video teleconsult link</h3>
        <p class="text-xs text-gray-500 mb-3">Sends a plain-text WhatsApp message with the meeting URL (24h session rules apply).</p>
        <form method="POST" action="{{ route('whatsapp.teleconsult') }}" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-500 mb-1">Appointment</label>
                <select name="appointment_id" required class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2">
                    @foreach($upcomingAppointments as $ap)
                        <option value="{{ $ap->id }}">{{ $ap->patient->name ?? 'Patient' }} — {{ $ap->scheduled_at->format('d M, h:i A') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs text-gray-500 mb-1">Meeting URL</label>
                <input type="url" name="meeting_url" required class="w-full rounded-lg border border-gray-300 text-sm px-3 py-2" placeholder="https://meet.google.com/...">
            </div>
            <button type="submit" class="px-4 py-2.5 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">Send via WhatsApp</button>
        </form>
    </div>
    @endif

    {{-- Tabs --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'messages'" :class="activeTab === 'messages' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors">
                    Messages
                </button>
                <button @click="activeTab = 'reminders'" :class="activeTab === 'reminders' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors">
                    Appointment Reminders
                </button>
                <button @click="activeTab = 'templates'" :class="activeTab === 'templates' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors">
                    Templates
                </button>
                <button @click="activeTab = 'automation'" :class="activeTab === 'automation' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors">
                    Automation Settings
                </button>
            </nav>
        </div>

        {{-- Messages Tab --}}
        <div x-show="activeTab === 'messages'" class="p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900">Recent Messages</h3>
                <button @click="showSendModal = true" class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    </svg>
                    New Message
                </button>
            </div>
            <div class="divide-y divide-gray-200 max-h-[500px] overflow-y-auto">
                @forelse($messages as $message)
                <div class="py-4 hover:bg-gray-50 transition-colors {{ $message->status === 'unread' ? 'bg-amber-50' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0" style="background: linear-gradient(135deg, #25D366, #128C7E);">
                            {{ strtoupper(substr($message->patient->name ?? 'P', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-gray-900">{{ $message->patient->name ?? 'Unknown' }}</p>
                                <span class="text-xs text-gray-400">{{ $message->created_at->format('d M h:i A') }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-0.5">{{ $message->content }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                @if($message->direction === 'inbound')
                                <span class="text-xs text-blue-600 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                    </svg>
                                    Received
                                </span>
                                @else
                                <span class="text-xs text-green-600 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                    </svg>
                                    Sent
                                </span>
                                @endif
                                @if($message->status === 'unread')
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="py-12 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    No messages yet. Start sending WhatsApp messages to patients!
                </div>
                @endforelse
            </div>
            @if($messages->hasPages())
            <div class="pt-4 border-t border-gray-200 mt-4">
                {{ $messages->links() }}
            </div>
            @endif
        </div>

        {{-- Reminders Tab --}}
        <div x-show="activeTab === 'reminders'" class="p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-gray-900">Upcoming Appointments</h3>
                    <p class="text-sm text-gray-500">Send reminders to patients with upcoming appointments</p>
                </div>
                <button @click="sendBulkReminders()" :disabled="selectedAppointments.length === 0" class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    </svg>
                    Send Selected (<span x-text="selectedAppointments.length">0</span>)
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input type="checkbox" @change="toggleAllAppointments($event)" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Appointment</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($upcomingAppointments ?? [] as $appointment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <input type="checkbox" value="{{ $appointment->id }}" x-model="selectedAppointments" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 text-xs font-bold">
                                        {{ strtoupper(substr($appointment->patient->name ?? 'P', 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $appointment->patient->name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $appointment->patient->phone ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $appointment->scheduled_at->format('d M Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $appointment->scheduled_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">Dr. {{ $appointment->doctor->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center">
                                <button @click="sendSingleReminder({{ $appointment->id }})" class="text-green-600 hover:text-green-800" title="Send Reminder">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                No upcoming appointments in the next 3 days.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Templates Tab --}}
        <div x-show="activeTab === 'templates'" class="p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900">Message Templates</h3>
                <button @click="showTemplateModal = true" class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors">
                    + New Template
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($templates ?? [] as $template)
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $template->name }}</h4>
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full mt-1 
                                {{ $template->type === 'appointment_reminder' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $template->type === 'prescription' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $template->type === 'follow_up' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $template->type === 'birthday' ? 'bg-pink-100 text-pink-700' : '' }}
                                {{ $template->type === 'custom' ? 'bg-gray-100 text-gray-700' : '' }}
                            ">
                                {{ ucfirst(str_replace('_', ' ', $template->type)) }}
                            </span>
                        </div>
                        <button @click="deleteTemplate({{ $template->id }})" class="text-gray-400 hover:text-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-600 mt-2 whitespace-pre-wrap">{{ Str::limit($template->content, 150) }}</p>
                </div>
                @empty
                <div class="col-span-2 py-12 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    No templates yet. Create your first template!
                </div>
                @endforelse
            </div>
        </div>

        {{-- Automation Tab --}}
        <div x-show="activeTab === 'automation'" class="p-5">
            <h3 class="font-bold text-gray-900 mb-4">Automation Settings</h3>
            <p class="text-sm text-gray-500 mb-6">Configure automatic WhatsApp reminders for your clinic.</p>
            
            <div class="space-y-4">
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900">Appointment Reminder (24 hours before)</h4>
                            <p class="text-sm text-gray-500">Automatically send reminder 24 hours before appointment</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="automationSettings.appointment_before_1d" @change="saveAutomationSetting('appointment_before_1d')" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900">Appointment Reminder (1 hour before)</h4>
                            <p class="text-sm text-gray-500">Automatically send reminder 1 hour before appointment</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="automationSettings.appointment_before_1h" @change="saveAutomationSetting('appointment_before_1h')" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900">Follow-up Reminder</h4>
                            <p class="text-sm text-gray-500">Remind patients about their follow-up appointments</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="automationSettings.follow_up" @change="saveAutomationSetting('follow_up')" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900">Birthday Wishes</h4>
                            <p class="text-sm text-gray-500">Send birthday wishes to patients</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="automationSettings.birthday" @change="saveAutomationSetting('birthday')" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold">WhatsApp Business Connected</h4>
                        <p class="text-sm text-green-100 mt-0.5">Your clinic is connected and ready to send automated messages</p>
                    </div>
                    <div class="ml-auto flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-green-300 animate-pulse"></div>
                        <span class="text-sm">Live</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Send Message Modal --}}
    <div x-show="showSendModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4" @click.outside="showSendModal = false">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Send WhatsApp Message</h3>
            </div>
            <form action="{{ route('whatsapp.send') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient</label>
                    <select name="patient_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Select patient...</option>
                        {{-- Patients will be loaded dynamically --}}
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="message" rows="5" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Type your message..."></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showSendModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700">Send</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Template Modal --}}
    <div x-show="showTemplateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4" @click.outside="showTemplateModal = false">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Create Template</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
                    <input type="text" x-model="newTemplate.name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select x-model="newTemplate.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="appointment_reminder">Appointment Reminder</option>
                        <option value="prescription">Prescription</option>
                        <option value="follow_up">Follow-up</option>
                        <option value="birthday">Birthday</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message Content</label>
                    <textarea x-model="newTemplate.content" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Use variables like {patient_name}, {appointment_date}, {doctor_name}..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Available: {patient_name}, {appointment_date}, {appointment_time}, {doctor_name}, {clinic_name}</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showTemplateModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50">Cancel</button>
                    <button @click="saveTemplate()" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700">Save Template</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
console.log('WhatsApp Automation page loaded');

function whatsappDashboard() {
    return {
        activeTab: 'messages',
        showSendModal: false,
        showTemplateModal: false,
        selectedAppointments: [],
        automationSettings: {
            appointment_before_1d: false,
            appointment_before_1h: false,
            follow_up: false,
            birthday: false
        },
        newTemplate: {
            name: '',
            type: 'appointment_reminder',
            content: ''
        },

        init() {
            console.log('WhatsApp dashboard initialized');
        },

        toggleAllAppointments(event) {
            if (event.target.checked) {
                this.selectedAppointments = @json(($upcomingAppointments ?? collect())->pluck('id'));
            } else {
                this.selectedAppointments = [];
            }
            console.log('Selected appointments:', this.selectedAppointments);
        },

        async sendSingleReminder(appointmentId) {
            console.log('Sending reminder for appointment:', appointmentId);
            try {
                const response = await fetch(`/whatsapp/appointment/${appointmentId}/reminder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                console.log('Reminder response:', data);
                if (data.success && data.whatsapp_url) {
                    window.open(data.whatsapp_url, '_blank');
                } else {
                    alert(data.error || 'Failed to generate reminder');
                }
            } catch (error) {
                console.error('Reminder error:', error);
                alert('Error sending reminder');
            }
        },

        async sendBulkReminders() {
            if (this.selectedAppointments.length === 0) return;
            console.log('Sending bulk reminders:', this.selectedAppointments);
            try {
                const response = await fetch('/whatsapp/appointments/bulk-reminders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ appointment_ids: this.selectedAppointments })
                });
                const data = await response.json();
                console.log('Bulk reminders response:', data);
                if (data.success && data.reminders) {
                    data.reminders.forEach((r, idx) => {
                        setTimeout(() => window.open(r.whatsapp_url, '_blank'), idx * 1000);
                    });
                    alert(`Generated ${data.count} reminders. Opening WhatsApp...`);
                }
            } catch (error) {
                console.error('Bulk reminders error:', error);
            }
        },

        async saveTemplate() {
            console.log('Saving template:', this.newTemplate);
            try {
                const response = await fetch('/whatsapp/templates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newTemplate)
                });
                const data = await response.json();
                console.log('Template save response:', data);
                if (data.success) {
                    this.showTemplateModal = false;
                    this.newTemplate = { name: '', type: 'appointment_reminder', content: '' };
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to save template');
                }
            } catch (error) {
                console.error('Template save error:', error);
            }
        },

        async deleteTemplate(templateId) {
            if (!confirm('Delete this template?')) return;
            console.log('Deleting template:', templateId);
            try {
                const response = await fetch(`/whatsapp/templates/${templateId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                console.log('Template delete response:', data);
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Template delete error:', error);
            }
        },

        async saveAutomationSetting(type) {
            console.log('Saving automation setting:', type, this.automationSettings[type]);
            try {
                await fetch('/whatsapp/reminders/schedule', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        type: type,
                        is_active: this.automationSettings[type]
                    })
                });
            } catch (error) {
                console.error('Automation setting error:', error);
            }
        }
    };
}
</script>
@endpush
