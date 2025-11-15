<div class="h-full flex flex-col bg-[#121212] rounded-xl shadow-md overflow-hidden border border-[#3b3b3b]">

    {{-- Header --}}
    <div class="px-4 pt-4 pb-3 border-b border-[#4a4a4a]">
        <h1 class="text-lg font-semibold text-[#d4af37] mb-1">All Contacts</h1>

        @if($contacts->count())
            <div class="text-xs text-gray-300 mb-3">
                Showing {{ $contacts->firstItem() }}–{{ $contacts->lastItem() }} of {{ $contacts->total() }}
            </div>
        @endif

        {{-- Search + page controls --}}
        <div class="flex items-center gap-2">

            {{-- Search input --}}
            <div class="flex-1">
                <input
                    type="text"
                    wire:model.debounce.300ms="search"
                    placeholder="Search by name, email, or phone..."
                    class="w-full bg-black border border-[#d4af37] text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#d4af37]"
                >
            </div>

            {{-- Previous Page --}}
            <button
                wire:click="previousPage"
                wire:loading.attr="disabled"
                class="w-8 h-8 flex items-center justify-center rounded-full
                       {{ $contacts->onFirstPage() ? 'bg-gray-700 text-gray-500 cursor-not-allowed' : 'bg-[#d4af37] text-black' }}">
                ‹
            </button>

            {{-- Next Page --}}
            <button
                wire:click="nextPage"
                wire:loading.attr="disabled"
                class="w-8 h-8 flex items-center justify-center rounded-full
                       {{ $contacts->hasMorePages() ? 'bg-[#d4af37] text-black' : 'bg-gray-700 text-gray-500 cursor-not-allowed' }}">
                ›
            </button>
        </div>
    </div>

    {{-- Contact List --}}
    <div class="flex-1 overflow-y-auto">
        @forelse ($contacts as $contact)
            <button
                wire:click="selectContact({{ $contact->id }})"
                class="w-full flex items-center justify-between text-left px-4 py-3 border-b border-[#2c2c2c]
                       hover:bg-[#1d1d1d] transition
                       {{ $selectedContactId === $contact->id ? 'bg-[#1d1d1d] border-l-4 border-[#d4af37]' : '' }}"
            >
                <div>
                    <div class="text-sm font-semibold text-gray-100">
                        {{ $contact->full_name }}
                    </div>
                    <div class="text-xs text-gray-400">
                        @if($contact->phone)
                            {{ $contact->phone }}
                        @elseif($contact->email)
                            {{ $contact->email }}
                        @else
                            —
                        @endif
                    </div>
                </div>
            </button>
        @empty
            <div class="p-4 text-gray-400 text-sm">
                No contacts found.
            </div>
        @endforelse
    </div>

</div>
