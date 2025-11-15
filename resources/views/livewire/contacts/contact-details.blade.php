<div class="h-full">

    @if(!$contact)
        <div class="flex items-center justify-center h-full text-gray-400">
            Select a contact from the list.
        </div>
        @return
    @endif

    {{-- Contact Details Card --}}
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-2xl font-semibold">{{ $full_name }}</h2>
                <div class="h-[2px] w-28 bg-[#d4af37] mt-1"></div>
            </div>

            <div class="flex gap-2">
                @if($editing)
                    <button
                        wire:click="$set('editing', false)"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded">
                        Cancel
                    </button>
                    <button
                        wire:click="save"
                        class="px-4 py-2 bg-[#d4af37] text-black font-semibold rounded">
                        Save
                    </button>
                @else
                    <button
                        wire:click="$set('editing', true)"
                        class="px-4 py-2 bg-[#d4af37] text-black font-semibold rounded">
                        Edit
                    </button>
                @endif
            </div>
        </div>

        {{-- Fields Grid --}}
        <div class="grid grid-cols-3 gap-4">

            {{-- Name --}}
            <div class="bg-gray-100 rounded p-3">
                <div class="text-xs font-semibold text-gray-500 mb-1">Name</div>
                @if($editing)
                    <input type="text" wire:model.defer="full_name" class="w-full border px-2 py-1 rounded">
                @else
                    <div class="text-sm">{{ $full_name }}</div>
                @endif
            </div>

            {{-- Phone --}}
            <div class="bg-gray-100 rounded p-3">
                <div class="text-xs font-semibold text-gray-500 mb-1">Phone</div>
                @if($editing)
                    <input type="text" wire:model.defer="phone" class="w-full border px-2 py-1 rounded">
                @else
                    <div class="text-sm">{{ $phone ?? '—' }}</div>
                @endif
            </div>

            {{-- Email --}}
            <div class="bg-gray-100 rounded p-3">
                <div class="text-xs font-semibold text-gray-500 mb-1">Email</div>
                @if($editing)
                    <input type="text" wire:model.defer="email" class="w-full border px-2 py-1 rounded">
                @else
                    <div class="text-sm">{{ $email ?? '—' }}</div>
                @endif
            </div>

            {{-- Address --}}
            <div class="col-span-3 bg-gray-100 rounded p-3">
                <div class="text-xs font-semibold text-gray-500 mb-1">Address</div>
                @if($editing)
                    <input type="text" wire:model.defer="address_line1" placeholder="Address line 1" class="w-full border px-2 py-1 rounded mb-2">
                    <input type="text" wire:model.defer="address_line2" placeholder="Address line 2" class="w-full border px-2 py-1 rounded mb-2">
                    <div class="grid grid-cols-3 gap-2">
                        <input type="text" wire:model.defer="city" placeholder="City" class="border px-2 py-1 rounded">
                        <input type="text" wire:model.defer="state" placeholder="State" class="border px-2 py-1 rounded">
                        <input type="text" wire:model.defer="postal_code" placeholder="Postal Code" class="border px-2 py-1 rounded">
                    </div>
                @else
                    <div class="text-sm">
                        {{ $address_line1 }}<br>
                        {{ $address_line2 }}<br>
                        {{ $city }}, {{ $state }} {{ $postal_code }}
                    </div>
                @endif
            </div>

            {{-- Date of Birth --}}
            <div class="bg-gray-100 rounded p-3">
                <div class="text-xs font-semibold text-gray-500 mb-1">Date of Birth</div>
                @if($editing)
                    <input type="date" wire:model.defer="date_of_birth" class="w-full border px-2 py-1 rounded">
                @else
                    <div class="text-sm">{{ $date_of_birth ? $date_of_birth->format('m/d/Y') : '—' }}</div>
                @endif
            </div>

            {{-- Policy Type --}}
            <div class="bg-gray-100 rounded p-3">
                <div class="text-xs font-semibold text-gray-500 mb-1">Policy Type</div>
                @if($editing)
                    <input type="text" wire:model.defer="policy_type" class="w-full border px-2 py-1 rounded">
                @else
                    <div class="text-sm">{{ $policy_type ?? '—' }}</div>
                @endif
            </div>

            {{-- Face Amount --}}
            <div class="bg-gray-100 rounded p-3">
                <div class="text-xs font-semibold text-gray-500 mb-1">Face Amount</div>
                @if($editing)
                    <input type="number" step="0.01" wire:model.defer="face_amount" class="w-full border px-2 py-1 rounded">
                @else
                    <div class="text-sm">
                        {{ $face_amount ? '$'.number_format($face_amount, 2) : '—' }}
                    </div>
                @endif
            </div>

            {{-- Premium Amount --}}
            <div class="bg-gray-100 rounded p-3">
                <div class="text-xs font-semibold text-gray-500 mb-1">Premium Amount</div>
                @if($editing)
                    <input type="number" step="0.01" wire:model.defer="premium_amount" class="w-full border px-2 py-1 rounded">
                @else
                    <div class="text-sm">
                        {{ $premium_amount ? '$'.number_format($premium_amount, 2) : '—' }}
                    </div>
                @endif
            </div>

            {{-- Premium Due Date --}}
            <div class="bg-gray-100 rounded p-3">
                <div class="text-xs font-semibold text-gray-500 mb-1">Premium Due Date</div>
                @if($editing)
                    <input type="date" wire:model.defer="premium_due_date" class="w-full border px-2 py-1 rounded">
                @else
                    <div class="text-sm">
                        {{ $premium_due_date ? $premium_due_date->format('m/d/Y') : '—' }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Notes --}}
    <div class="bg-white rounded-xl shadow-md p-6">

        <h3 class="text-lg font-semibold mb-3">Notes</h3>

        {{-- Add Note --}}
        <textarea
            wire:model.defer="newNote"
            class="w-full border rounded px-3 py-2 mb-2 text-sm"
            placeholder="Add a note..."
        ></textarea>

        <button
            wire:click="addNote"
            class="px-4 py-2 bg-[#d4af37] text-black font-semibold rounded">
            Save Note
        </button>

        {{-- Notes List --}}
        <div class="mt-6 space-y-3">
            @forelse ($notes as $note)
                <div class="border rounded p-3">
                    <div class="flex justify-between text-xs text-gray-500 mb-2">
                        <span>{{ $note->author->name ?? 'Unknown' }}</span>
                        <span>{{ $note->created_at->format('m/d/Y g:i A') }}</span>
                    </div>
                    <div class="text-sm text-gray-700 whitespace-pre-line">
                        {{ $note->body }}
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm">No notes yet.</p>
            @endforelse
        </div>

    </div>

</div>
