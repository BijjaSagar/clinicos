@extends('admin.layouts.app')

@section('title', 'Settings')
@section('subtitle', 'Platform configuration')

@section('content')
<div class="space-y-6 max-w-3xl">
    {{-- Platform Settings --}}
    <div class="bg-white rounded-xl border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Platform Settings</h3>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Platform Name</label>
                <input type="text" value="ClinicOS" 
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Support Email</label>
                    <input type="email" value="support@clinicos.com" 
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Support Phone</label>
                    <input type="text" value="+91 98765 43210" 
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
        </div>
    </div>

    {{-- Trial Settings --}}
    <div class="bg-white rounded-xl border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Trial Settings</h3>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Default Trial Days</label>
                    <input type="number" value="30" min="1" max="365"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Trial Extensions</label>
                    <input type="number" value="2" min="0" max="10"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" checked class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">Send trial expiry reminders (7, 3, 1 days before)</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Plan Pricing --}}
    <div class="bg-white rounded-xl border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Plan Pricing</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between py-3 border-b border-gray-100">
                    <div>
                        <p class="font-medium text-gray-900">Solo</p>
                        <p class="text-sm text-gray-500">Single doctor practice</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="text-xs text-gray-500">Monthly</label>
                            <input type="number" value="999" class="w-24 px-3 py-2 rounded-lg border border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Yearly</label>
                            <input type="number" value="9999" class="w-24 px-3 py-2 rounded-lg border border-gray-300 text-sm">
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between py-3 border-b border-gray-100">
                    <div>
                        <p class="font-medium text-gray-900">Small</p>
                        <p class="text-sm text-gray-500">Up to 3 doctors</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="text-xs text-gray-500">Monthly</label>
                            <input type="number" value="2499" class="w-24 px-3 py-2 rounded-lg border border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Yearly</label>
                            <input type="number" value="24999" class="w-24 px-3 py-2 rounded-lg border border-gray-300 text-sm">
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between py-3 border-b border-gray-100">
                    <div>
                        <p class="font-medium text-gray-900">Group</p>
                        <p class="text-sm text-gray-500">Up to 10 doctors</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="text-xs text-gray-500">Monthly</label>
                            <input type="number" value="4999" class="w-24 px-3 py-2 rounded-lg border border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Yearly</label>
                            <input type="number" value="49999" class="w-24 px-3 py-2 rounded-lg border border-gray-300 text-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Notifications --}}
    <div class="bg-white rounded-xl border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Admin Notifications</h3>
        </div>
        <div class="p-6 space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" checked class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Email me when a new clinic signs up</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" checked class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Email me when a trial expires</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" checked class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Email me when a subscription payment fails</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Weekly summary report</span>
            </label>
        </div>
    </div>

    {{-- Save Button --}}
    <div class="flex justify-end">
        <button type="button" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors">
            Save Settings
        </button>
    </div>

    {{-- Info Notice --}}
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
        <p class="text-sm text-gray-600">
            <strong>Note:</strong> Settings persistence is not yet implemented. These are placeholder fields for future functionality. 
            Currently, all configuration is managed through environment variables and database.
        </p>
    </div>
</div>
@endsection
