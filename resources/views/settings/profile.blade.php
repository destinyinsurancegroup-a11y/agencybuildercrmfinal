@extends('settings.layout')

@section('settings_content')
    <h2 class="text-xl font-semibold text-gray-900">Profile</h2>
    <p class="text-gray-600 mt-1">
        Update your account information.
    </p>

    <div class="mt-6 space-y-6">
        <div class="rounded-lg border border-gray-200 p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <div class="mt-1 p-2 border rounded-md bg-gray-50 text-gray-800">
                        {{ $user->name ?? '—' }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <div class="mt-1 p-2 border rounded-md bg-gray-50 text-gray-800">
                        {{ $user->email ?? '—' }}
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                {{-- Placeholder buttons; we wire these up in Step 3 --}}
                <button type="button"
                        class="px-4 py-2 rounded-md bg-[#C9A227] text-black hover:opacity-90">
                    Update Email
                </button>

                <button type="button"
                        class="px-4 py-2 rounded-md border border-gray-300 text-gray-800 hover:bg-gray-50">
                    Change Password
                </button>
            </div>

            <p class="text-sm text-gray-500 mt-4">
                Tier 1 note: single-user account.
            </p>
        </div>
    </div>
@endsection
