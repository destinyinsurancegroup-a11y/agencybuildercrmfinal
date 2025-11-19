@if ($contact->notes->count() === 0)
    <p class="text-muted fst-italic">No notes have been added yet.</p>
@else
    @foreach ($contact->notes as $note)
        <div class="border rounded p-3 mb-3 shadow-sm" 
             style="background: #fafafa; border-left: 4px solid #000;">

            {{-- TIMESTAMP --}}
            <div class="small text-secondary fw-bold mb-1">
                {{ $note->created_at->format('M d, Y • h:i A') }}
            </div>

            {{-- NOTE TEXT --}}
            <div style="white-space: pre-wrap;">
                {{ $note->note }}
            </div>

            {{-- OPTIONAL: SHOW USER WHO ADDED NOTE --}}
            @if($note->user)
                <div class="small text-muted mt-2">
                    — added by {{ $note->user->name }}
                </div>
            @endif

        </div>
    @endforeach
@endif
