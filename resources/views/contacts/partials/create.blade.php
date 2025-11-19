<div class="card shadow-sm border-0"
     style="border-radius:18px; height: calc(100vh - 120px); overflow-y:auto;">

    <!-- HEADER -->
    <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between align-items-center"
         style="border-radius:18px 18px 0 0;">
        <span style="font-size:18px;">Edit Contact</span>
    </div>

    <div class="card-body">

        <form id="edit-contact-form" data-id="{{ $contact->id }}">

            @csrf

            <!-- BASIC INFORMATION -->
            <h5 class="fw-bold mb-3">Basic Information</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="text-muted">First Name</label>
                    <input type="text" name="first_name" class="form-control"
                           value="{{ $contact->first_name }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Last Name</label>
                    <input type="text" name="last_name" class="form-control"
                           value="{{ $contact->last_name }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="text-muted">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ $contact->email }}">
                </div>

                <div class="col-md-6">
                    <label class="text-muted">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="{{ $contact->phone }}">
                </div>
            </div>

            <!-- CONTACT TYPE + STATUS -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="text-muted">Contact Type</label>
                    <input type="text" name="contact_type" class="form-control"
                           value="{{ $contact->contact_type }}">
                </div>

                <div class="col-md-6">
                    <label class="text-muted">Status</label>
                    <input type="text" name="status" class="form-control"
                           value="{{ $contact->status }}">
                </div>
            </div>

            <!-- ADDRESS -->
            <h5 class="fw-bold mt-4 mb-2">Address</h5>

            <div class="mb-3">
                <label class="text-muted">Address Line 1</label>
                <input type="text" name="address_line1" class="form-control"
                       value="{{ $contact->address_line1 }}">
            </div>

            <div class="mb-3">
                <label class="text-muted">Address Line 2</label>
                <input type="text" name="address_line2" class="form-control"
                       value="{{ $contact->address_line2 }}">
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="text-muted">City</label>
                    <input type="text" name="city" class="form-control"
                           value="{{ $contact->city }}">
                </div>

                <div class="col-md-4">
                    <label class="text-muted">State</label>
                    <input type="text" name="state" class="form-control"
                           value="{{ $contact->state }}">
                </div>

                <div class="col-md-4">
                    <label class="text-muted">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control"
                           value="{{ $contact->postal_code }}">
                </div>
            </div>

            <!-- NOTES -->
            <h5 class="fw-bold mt-4 mb-2">Notes</h5>

            <div class="mb-4">
                <textarea name="notes" class="form-control" rows="4">{{ $contact->notes }}</textarea>
            </div>

            <!-- SAVE BUTTON -->
            <button type="submit" class="btn fw-bold"
                    style="background:#c9a227; color:#111827; border:none; border-radius:8px; padding:10px 20px;">
                Save Changes
            </button>

        </form>
    </div>
</div>

{{-- AJAX UPDATE HANDLER --}}
@push('scripts')
<script>
$(document).on('submit', '#edit-contact-form', function (e) {
    e.preventDefault();

    let id = $(this).data('id');
    let formData = $(this).serialize();

    $.ajax({
        url: '/contacts/' + id,
        type: 'PUT',
        data: formData,
        success: function () {
            // reload the updated detail view
            $.get('/contacts/' + id, function (html) {
                $('#contact-details-container').html(html);
            });
        },
        error: function () {
            alert('Error updating contact.');
        }
    });
});
</script>
@endpush
