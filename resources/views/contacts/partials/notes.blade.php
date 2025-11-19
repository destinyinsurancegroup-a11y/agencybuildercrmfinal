<div class="p-4">

    {{-- ADD NEW NOTE FORM --}}
    <form id="add-note-form" data-contact-id="{{ $contact->id }}">
        @csrf

        <label class="form-label fw-bold">Add a Note</label>
        <textarea 
            name="note" 
            class="form-control" 
            rows="3" 
            placeholder="Write a note..."
            required
        ></textarea>

        <button type="submit" class="btn btn-primary mt-2">
            Save Note
        </button>
    </form>

    {{-- NOTES LIST --}}
    <div class="mt-4" id="notes-list">
        @include('contacts.partials._notes_list', ['contact' => $contact])
    </div>

</div>

@push('scripts')
<script>
/*
|--------------------------------------------------------------------------
| SAVE NOTE (AJAX)
|--------------------------------------------------------------------------
*/
$(document).off('submit', '#add-note-form'); // prevent duplicate binding
$(document).on('submit', '#add-note-form', function(e) {
    e.preventDefault();

    let form = $(this);
    let contactId = form.data('contact-id');

    $.ajax({
        url: `/contacts/${contactId}/notes`,
        type: "POST",
        data: form.serialize(),
        success: function(response) {
            // Update notes list
            $('#notes-list').html(response.html);

            // Clear textarea
            form[0].reset();
        },
        error: function(xhr) {
            alert("Error saving note.");
            console.error(xhr.responseText);
        }
    });
});


/*
|--------------------------------------------------------------------------
| LOAD NOTES TAB (if needed for dynamic tab switching)
|--------------------------------------------------------------------------
*/
$(document).on('click', '[data-tab="notes"]', function(e){
    e.preventDefault();

    let contactId = $(this).data('contact-id');

    $("#contact-details-container").load(`/contacts/${contactId}/notes`);
});
</script>
@endpush
