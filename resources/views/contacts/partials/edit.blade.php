<div class="p-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold">{{ $contact->full_name }}</h2>
    </div>

    <form id="edit-contact-form" data-id="{{ $contact->id }}">
        @csrf

        {{-- Email + Phone --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted">Email</label>
                <input type="email" name="email" class="form-control" value="{{ $contact->email }}">
            </div>
            <div class="col-md-6">
                <label class="text-muted">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ $contact->phone }}">
            </div>
        </div>

        {{-- Contact Type + Status --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted">Contact Type</label>
                <input type="text" name="contact_type" class="form-control" value="{{ $contact->contact_type }}">
            </div>
            <div class="col-md-6">
                <label class="text-muted">Status</label>
                <input type="text" name="status" class="form-control" value="{{ $contact->status }}">
            </div>
        </div>

        {{-- Address --}}
        <div class="mb-3">
            <label class="text-muted">Address Line 1</label>
            <input type="text" name="address_line1" class="form-control" value="{{ $contact->address_line1 }}">
        </div>

        <div class="mb-3">
            <label class="text-muted">Address Line 2</label>
            <input type="text" name="address_line2" class="form-control" value="{{ $contact->address_line2 }}">
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="text-muted">City</label>
                <input type="text" name="city" class="form-control" value="{{ $contact->city }}">
            </div>
            <div class="col-md-4">
                <label class="text-muted">State</label>
                <input type="text" name="state" class="form-control" value="{{ $contact->state }}">
            </div>
            <div class="col-md-4">
                <label class="text-muted">Postal Code</label>
                <input type="text" name="postal_code" class="form-control" value="{{ $contact->postal_code }}">
            </div>
        </div>

        {{-- Notes --}}
        <div class="mb-4">
            <label class="text-muted">Notes</label>
            <textarea name="notes" rows="4" class="form-control">{{ $contact->notes }}</textarea>
        </div>

        <button type="submit" class="btn fw-bold"
                style="background:#c9a227; color:#111827; border:none; border-radius:8px; padding:10px 20px;">
            Save Changes
        </button>
    </form>
</div>

@push('scripts')
<script>
$(document).on('submit', '#edit-contact-form', function(e) {
    e.preventDefault();

    let id = $(this).data('id');
    let formData = $(this).serialize();

    $.ajax({
        url: '/contacts/' + id,
        type: 'PUT',
        data: formData,
        success: function() {
            $.get('/contacts/' + id, function(html) {
                $('#contact-details-container').html(html);
            });
        },
        error: function() {
            alert("Error updating contact.");
        }
    });
});
</script>
@endpush
