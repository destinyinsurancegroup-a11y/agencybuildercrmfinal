@php
    $tabClass = function (string $tab) use ($activeTab) {
        $isActive = $activeTab === $tab;

        return $isActive
            ? 'bg-[#C9A227] text-black'
            : 'bg-white text-gray-800 hover:bg-gray-50';
    };
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold text-gray-900">Settings</h1>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-6">
        <a href="{{ route('settings.profile') }}"
           class="px-4 py-2 rounded-md border border-gray-200 {{ $tabClass('profile') }}">
            Profile
        </a>

        <a href="{{ route('settings.messaging') }}"
           class="px-4 py-2 rounded-md border border-gray-200 {{ $tabClass('messaging') }}">
            Messaging
        </a>

        <a href="{{ route('settings.billing') }}"
           class="px-4 py-2 rounded-md border border-gray-200 {{ $tabClass('billing') }}">
            Billing
        </a>
    </div>

    {{-- Page body --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        @yield('settings_content')
    </div>
</div>
@endsection
