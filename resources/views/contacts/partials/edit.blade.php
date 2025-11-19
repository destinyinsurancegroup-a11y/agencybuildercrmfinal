<div class="px-8 py-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">
            Edit Contact
        </h2>
    </div>

    {{-- EDIT FORM --}}
    <form id="edit-contact-form" data-id="{{ $contact->id }}" class="space-y-8">

        @csrf

        {{-- BASIC INFORMATION --}}
        <div>
            <h3 class="font-semibold text-lg mb-3">Basic Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1">First Name</label>
                    <input type="text" name="first_name" class="form-input w-full"
                           value="{{ $contact->first_name }}">
                </div>

                <div>
                    <label class="block mb-1">Last Name</label>
                    <input type="text" name="last_name" class="form-input w-full"
                           value="{{ $contact->last_name }}">
                </div>

                <div>
                    <label class="block mb-1">Email</label>
                    <input type="email" name="email" class="form-input w-full"
                           value="{{ $contact->email }}">
                </div>

                <div>
                    <label class="block mb-1">Phone</label>
                    <input type="text" name="phone" class="form-input w-full"
                           value="{{ $contact->phone }}">
                </div>

                <div>
                    <label class="block mb-1">Contact Type</label>
                    <input type="text" name="contact_type" class="form-input w-full"
                           value="{{ $contact->contact_type }}">
                </div>

                <div>
                    <label class="block mb-1">Status</label>
                    <input type="text" name="status" class="form-input w-full"
                           value="{{ $contact->status }}">
                </div>
            </div>
        </div>

        {{-- ADDRESS --}}
        <div>
            <h3 class="font-semibold text-lg mb-3">Address</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block mb-1">Address Line 1</label>
                    <input type="text" name="address_line1" class="form-input w-full"
                           value="{{ $contact->address_line1 }}">
                </div>

                <div class="md:col-span-2">
                    <label class="block mb-1">Address Line 2</label>
                    <input type="text" name="address_line2" class="form-input w-full"
                           value="{{ $contact->address_line2 }}">
                </div>

                <div>
                    <label class="block mb-1">City</label>
                    <input type="text" name="city" class="form-input w-full"
                           value="{{ $contact->city }}">
                </div>

                <div>
                    <label class="block mb-1">State</label>
                    <input type="text" name="state" class="form-input w-full"
                           value="{{ $contact->state }}">
                </div>

                <div>
                    <label class="block mb-1">Postal Code</label>
                    <input type="text" name="postal_code" class="form-input w-full"
                           value="{{ $contact->postal_code }}">
                </div>
            </div>
        </div>

        {{-- NOTES --}}
        <div>
            <h3 class="font-semibold text-lg mb-3">Notes</h3>

            <textarea name="notes" class="form-input w-full h-32">{{ $contact->notes }}</textarea>
        </div>

        {{-- SUBMIT BUTTON --}}
        <button type="submit"
                class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold px-6 py-2 rounded">
            Save Changes
        </button>

    </form>
</div>

{{-- AJAX SCRIPT --}}
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
        success: function (response) {
            // Reload updated details panel
            $.get('/contacts/' + id, function (html) {
                $('#contact-details-container').html(html);
            });
        },
        error: function () {
            alert('An error occurred while saving.');
        }
    });
});
</script>
@endpush
