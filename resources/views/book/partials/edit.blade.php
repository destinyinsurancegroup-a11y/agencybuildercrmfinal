<div class="p-4">
    <h3 class="fw-bold text-gold mb-3">Edit Client</h3>

    <form method="POST" action="{{ route('book.update', $client->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>First Name</label>
            <input name="first_name" class="form-control" value="{{ $client->first_name }}" required>
        </div>

        <div class="mb-3">
            <label>Last Name</label>
            <input name="last_name" class="form-control" value="{{ $client->last_name }}" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input name="email" class="form-control" value="{{ $client->email }}">
        </div>

        <div class="mb-3">
            <label>Phone</label>
            <input name="phone" class="form-control" value="{{ $client->phone }}">
        </div>

        <button class="btn-gold">Update Client</button>
    </form>
</div>
