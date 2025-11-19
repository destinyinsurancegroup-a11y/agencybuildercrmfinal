<div class="card shadow-sm border-0"
     style="border-radius:18px; height: calc(100vh - 120px); overflow-y:auto;">

    {{-- CONTACT NAME HEADER --}}
    <div class="card-header bg-white d-flex justify-content-between align-items-center"
         style="border-radius:18px 18px 0 0; padding:25px 30px; border-bottom:1px solid #eee;">

        {{-- BIG CONTACT NAME --}}
        <span style="font-size:48px; font-weight:800; color:#111827;">
            {{ $contact->full_name }}
        </span>

        {{-- EDIT BUTTON --}}
        <a href="{{ route('contacts.edit', $contact->id) }}" 
           class="btn btn-sm"
           style="background:#c9a227; color:#111827; font-weight:600; border-radius:8px;">
            Edit
        </a>
    </div>

    <div class="card-body" style="padding:30px;">

        {{-- PRIMARY DETAILS --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted">Email</label>
                <div class="fw-semibold">{{ $contact->email ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <label class="text-muted">Phone</label>
                <div class="fw-semibold">{{ $contact->phone ?: '—' }}</div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted">Contact Type</label>
                <div class="fw-semibold">{{ $contact->contact_type ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <label class="text-muted">Status</label>
                <span class="badge bg-secondary" style="font-size:12px; padding:6px 10px;">
                    {{ $contact->status ?: '—' }}
                </span>
            </div>
        </div>

        {{-- ADDRESS --}}
        <div class="mb-4">
            <label class="text-muted">Address</label>
            <div class="fw-semibold">
                @if($contact->address_line1)
                    {{ $contact->address_line1 }}<br>
                    {{ $contact->city }} {{ $contact->state }} {{ $contact->postal_code }}
                @else
                    —
                @endif
            </div>
        </div>

        <hr class="my-4">

        {{-- ADD NEW NOTE --}}
        <h4 class="fw-bold mb-3">Add a Note</h4>

        <form id="add-note-form" data-contact-id="{{ $contact->id }}">
            @csrf
            <textarea 
                name="note" 
                class="form-control" 
                rows="5"
                placeholder="Type your note here..."
                required
            ></textarea>

            <button type="submit" 
                    class="btn mt-3"
                    style="background:#c9a227; color:#111827; font-weight:600; border-radius:8px;">
                Save Note
            </button>
        </form>

        <hr class="my-4">

        {{-- NOTES HISTORY --}}
        <h4 class="fw-bold mb-3">Notes History</h4>

        <div id="notes-list">
            @include('contacts.partials._notes_list', ['contact' => $contact])
        </div>

    </div>
</div>

@push('scripts')
<script>

/*
|--------------------------------------------------------------------------
| SAVE NOTE (AJAX)
|--------------------------------------------------------------------------
*/
$(document).on('submit', '#add-note-form', function(e) {
    e.preventDefault();

    let form = $(this);
    let contactId = form.data('contact-id');

    $.ajax({
        url: `/contacts/${contactId}/notes`,
        type: "POST",
        data: form.serialize(),
        success: function(response) {
            // Update notes history
            $('#notes-list').html(response.html);

            // Clear input
            form[0].reset();
        },
        error: function(xhr) {
            alert("There was an error saving the note.");
            console.error(xhr.responseText);
        }
    });
});

</script>
@endpush
