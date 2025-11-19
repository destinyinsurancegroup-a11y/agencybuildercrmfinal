<div class="bg-white shadow rounded-lg p-6">

    {{-- Title --}}
    <h2 class="text-2xl font-bold mb-6">Edit Contact</h2>

    <form id="edit-contact-form" data-id="{{ $contact->id }}" class="space-y-10">

        @csrf

        {{-- BASIC INFORMATION --}}
        <div>
            <h3 class="font-semibold text-xl mb-3">Basic Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label class="block text-sm mb-1">First Name</label>
                    <input type="text" name="first_name" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->first_name }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">Last Name</label>
                    <input type="text" name="last_name" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->last_name }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">Email</label>
                    <input type="email" name="email" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->email }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">Phone</label>
                    <input type="text" name="phone" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->phone }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">Contact Type</label>
                    <input type="text" name="contact_type" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->contact_type }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">Status</label>
                    <input type="text" name="status" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->status }}">
                </div>
            </div>
        </div>

        {{-- ADDRESS --}}
        <div>
            <h3 class="font-semibold text-xl mb-3">Address</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Address Line 1</label>
                    <input type="text" name="address_line1" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->address_line1 }}">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Address Line 2</label>
                    <input type="text" name="address_line2" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->address_line2 }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">City</label>
                    <input type="text" name="city" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->city }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">State</label>
                    <input type="text" name="state" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->state }}">
                </div>

                <div>
                    <label class="block text-sm mb-1">Postal Code</label>
                    <input type="text" name="postal_code" class="w-full border rounded px-3 py-2"
                           value="{{ $contact->postal_code }}">
                </div>
            </div>
        </div>


        {{-- NOTES --}}
        <div>
            <h3 class="font-semibold text-xl mb-3">Notes</h3>

            <textarea
                name="notes"
                class="w-full border rounded px-3 py-2 h-32"
            >{{ $contact->notes }}</textarea>
        </div>


        {{-- SUBMIT BUTTON --}}
        <button type="submit"
            class="bg-yellow-600 hover:bg-yellow-700 text-white font-semibold px-6 py-2 rounded">
            Save Changes
        </button>

    </form>
</div>


{{-- AJAX UPDATE SCRIPT --}}
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
            // Reload updated details panel
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
