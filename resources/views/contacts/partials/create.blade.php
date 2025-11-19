<div class="card shadow-sm border-0" style="border-radius:18px; padding:20px;">
    <div class="card-header bg-black text-gold fw-bold" style="border-radius:18px 18px 0 0;">
        <span style="font-size:18px;">Create Contact</span>
    </div>

    <div class="card-body">
        <form id="createContactForm" method="POST" action="{{ route('contacts.store') }}">
            @csrf

            <div class="mb-3">
                <label class="text-muted">First Name</label>
                <input type="text" name="first_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="text-muted">Last Name</label>
                <input type="text" name="last_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="text-muted">Email</label>
                <input type="email" name="email" class="form-control">
            </div>

            <div class="mb-3">
                <label class="text-muted">Phone</label>
                <input type="text" name="phone" class="form-control">
            </div>

            <div class="mb-3">
                <label class="text-muted">Contact Type</label>
                <input type="text" name="contact_type" class="form-control">
            </div>

            <div class="mb-3">
                <label class="text-muted">Status</label>
                <input type="text" name="status" class="form-control">
            </div>

            <button class="btn btn-primary" style="background:#c9a227; color:#111827; border:none;">
                Save Contact
            </button>
        </form>
    </div>
</div>
