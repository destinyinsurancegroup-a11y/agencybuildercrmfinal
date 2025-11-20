<div class="card shadow-sm border-0"
     style="border-radius:18px; height: calc(100vh - 120px); overflow-y:auto;">

    <!-- HEADER -->
    <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between align-items-center"
         style="border-radius:18px 18px 0 0;">

        <span style="font-size:18px;">Edit Client</span>
    </div>

    <div class="card-body">

        <form id="editClientForm"
              method="POST"
              action="{{ route('book.update', $client->id) }}">

            @csrf
            @method('PUT')


            <!-- BASIC INFO -->
            <h5 class="fw-bold mb-3">Basic Information</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="text-muted">First Name</label>
                    <input type="text"
                           name="first_name"
                           class="form-control"
                           value="{{ $client->first_name }}"
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Last Name</label>
                    <input type="text"
                           name="last_name"
                           class="form-control"
                           value="{{ $client->last_name }}"
                           required>
                </div>
            </div>


            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="text-muted">Email</label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           value="{{ $client->email }}">
                </div>

                <div class="col-md-6">
                    <label class="text-muted">Phone</label>
                    <input type="text"
                           name="phone"
                           class="form-control"
                           value="{{ $client->phone }}">
                </div>
            </div>


            <!-- ADDRESS -->
            <h5 class="fw-bold mt-4 mb-2">Address</h5>

            <div class="mb-3">
                <label class="text-muted">Full Address</label>
                <textarea name="address"
                          class="form-control"
                          rows="2"
                          placeholder="Street, City, State, ZIP">{{ $client->address }}</textarea>
            </div>


            <!-- NOTES (Client Notes, NOT Activity Notes) -->
            <h5 class="fw-bold mt-4 mb-2">Notes</h5>

            <div class="mb-4">
                <textarea name="notes"
                          class="form-control"
                          rows="4"
                          placeholder="Internal notes...">{{ $client->notes }}</textarea>
            </div>


            <!-- SAVE BUTTON -->
            <button class="btn fw-bold"
                    style="background:#c9a227; color:#111827; border:none; border-radius:8px; padding:10px 20px;">
                Save Changes
            </button>

        </form>

    </div>
</div>
