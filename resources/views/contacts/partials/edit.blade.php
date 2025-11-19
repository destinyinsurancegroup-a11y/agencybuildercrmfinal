<div class="contact-edit-card px-8 py-6">

    <h2 class="text-2xl font-bold mb-6">Edit Contact</h2>

    <form id="edit-contact-form" data-contact-id="{{ $contact->id }}">
        @csrf

        {{-- BASIC INFO --}}
        <h3 class="font-semibold text-lg mb-2">Basic Information</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label>First Name</label>
                <input type="text" name="first_name" class="form-input"
                       value="{{ $contact->first_name }}">
            </div>

            <div>
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-input"
                       value="{{ $contact->last_name }}">
            </div>

            <div>
                <label>Email</label>
                <input type="email" name="email" class="form-input"
                       value="{{ $contact->email }}">
            </div>

            <div>
                <label>Phone</label>
                <input type="text" name="phone" class="form-input"
                       value="{{ $contact->phone }}">
            </div>

            <div>
                <label>Contact Type</label>
                <input type="text" name="contact_type" class="form-input"
                       value="{{ $contact->contact_type }}">
            </div>

            <div>
                <label>Status</label>
                <input type="text" name="status" class="form-input"
                       value="{{ $contact->status }}">
            </div>
        </div>

        {{-- ADDRESS --}}
        <h3 class="font-semibold text-lg mt-8 mb-2">Address</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label>Address Line 1</label>
                <input type="text" name="address_line1" class="form-input"
                       value="{{ $contact->address_line1 }}">
            </div>

            <div>
                <label>Address Line 2</label>
                <input type="text" name="address_line2" class="form-input"
                       value="{{ $contact->address_line2 }}">
            </div>

            <div>
                <label>City</label>
                <input type="text" name="city" class="form-input"
                       value="{{ $contact->city }}">
            </div>

            <div>
                <label>State</label>
                <input type="text" name="state" class="form-input"
                       value="{{ $contact->state }}">
            </div>

            <div>
                <label>Postal Code</label>
                <input type="text" name="postal_code" class="form-input"
                       value="{{ $contact->postal_code }}">
            </div>
        </div>

        {{-- NOTES --}}
        <h3 class="font-semibold text-lg mt-8 mb-2">Notes</h3>
        <textarea name="notes" class="form-input h-32">{{ $contact->notes }}</textarea>

        <button type="submit" class="bg-yellow-600 text-white px-6 py-2 rounded mt-6 hover:bg-yellow-700">
            Save Changes
        </button>

    </form>
</div>
