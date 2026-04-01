@extends('layouts.app')

@section('title', 'NABH checklist')
@section('breadcrumb', 'Compliance')

@section('content')
<div class="p-6 max-w-3xl mx-auto space-y-6">
    <h1 class="text-xl font-bold text-gray-900">NABH-oriented checklist</h1>
    <p class="text-sm text-gray-500">Internal documentation aid for small clinics. This is not a certification.</p>

    @foreach($sections as $title => $items)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-900 mb-3">{{ $title }}</h2>
            <ul class="list-disc list-inside space-y-2 text-sm text-gray-700">
                @foreach($items as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
@endsection
