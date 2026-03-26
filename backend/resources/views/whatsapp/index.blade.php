@extends('layouts.app')

@section('title', 'WhatsApp')
@section('breadcrumb', 'WhatsApp Messages')

@section('content')
<div class="p-6 space-y-6">
    {{-- Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Message List --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-bold text-gray-900">Recent Messages</h3>
                <button class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    New Message
                </button>
            </div>

            <div class="divide-y divide-gray-200">
                @forelse($messages as $message)
                <div class="px-5 py-4 hover:bg-gray-50 transition-colors cursor-pointer {{ $message->status === 'unread' ? 'bg-amber-50' : '' }}">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                             style="background: linear-gradient(135deg, #25D366, #128C7E);">
                            {{ strtoupper(substr($message->patient->name ?? 'P', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-gray-900">{{ $message->patient->name ?? 'Unknown' }}</p>
                                <span class="text-xs text-gray-400">{{ $message->created_at->format('h:i A') }}</span>
                            </div>
                            <p class="text-sm text-gray-600 truncate mt-0.5">{{ Str::limit($message->content, 60) }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                @if($message->direction === 'inbound')
                                <span class="text-xs text-blue-600">↓ Received</span>
                                @else
                                <span class="text-xs text-green-600">↑ Sent</span>
                                @endif
                                @if($message->status === 'unread')
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-5 py-12 text-center text-gray-500">
                    No messages yet.
                </div>
                @endforelse
            </div>

            @if($messages->hasPages())
            <div class="px-5 py-4 border-t border-gray-200">
                {{ $messages->links() }}
            </div>
            @endif
        </div>

        {{-- Quick Actions & Templates --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="font-bold text-gray-900">Quick Templates</h3>
                </div>
                <div class="p-4 space-y-2">
                    <button class="w-full text-left px-4 py-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <p class="font-medium text-gray-900 text-sm">Appointment Reminder</p>
                        <p class="text-xs text-gray-500 mt-0.5">Send 24hr reminder</p>
                    </button>
                    <button class="w-full text-left px-4 py-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <p class="font-medium text-gray-900 text-sm">Invoice Sent</p>
                        <p class="text-xs text-gray-500 mt-0.5">With PDF attachment</p>
                    </button>
                    <button class="w-full text-left px-4 py-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <p class="font-medium text-gray-900 text-sm">Lab Results Ready</p>
                        <p class="text-xs text-gray-500 mt-0.5">Notify patient</p>
                    </button>
                    <button class="w-full text-left px-4 py-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <p class="font-medium text-gray-900 text-sm">Follow-up Request</p>
                        <p class="text-xs text-gray-500 mt-0.5">Book next visit</p>
                    </button>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white">
                <h4 class="font-bold">WhatsApp Business</h4>
                <p class="text-sm text-green-100 mt-1">Connected and active</p>
                <div class="flex items-center gap-2 mt-3">
                    <div class="w-2 h-2 rounded-full bg-green-300 animate-pulse"></div>
                    <span class="text-xs">Live connection</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
