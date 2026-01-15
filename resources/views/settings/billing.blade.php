@extends('settings.layout')

@section('settings_content')
    <h2 class="text-xl font-semibold text-gray-900">Billing</h2>
    <p class="text-gray-600 mt-1">
        Manage your subscription and invoices (placeholder).
    </p>

    <div class="mt-6 rounded-lg border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-semibold text-gray-900">Current Plan</p>
                <p class="text-sm text-gray-600">Tier 1</p>
            </div>

            <button class="px-4 py-2 rounded-md bg-[#C9A227] text-black hover:opacity-90" type="button">
                Manage Subscription
            </button>
        </div>
    </div>
@endsection
