<div class="p-4">
    <h3 class="fw-bold text-gold mb-3">New Book Client</h3>

    <form method="POST" action="{{ route('book.store') }}">
        @csrf

        <div class="mb-3">
            <label>First Name</label>
            <input name="first_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Last Name</label>
            <input name="last_name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input name="email" class="form-control">
        </div>

        <div class="mb-3">
            <label>Phone</label>
            <input name="phone" class="form-control">
        </div>

        <button type="submit" class="btn-gold">Save Client</button>
    </form>
</div>
