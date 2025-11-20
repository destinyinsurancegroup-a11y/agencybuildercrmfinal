<div class="card shadow-sm border-0"
     style="border-radius:18px; height: calc(100vh - 120px); overflow-y:auto;">

    <!-- HEADER -->
    <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between align-items-center"
         style="border-radius:18px 18px 0 0;">

        <span style="font-size:18px;">Edit Client</span>

        <button class="btn btn-sm btn-light"
                onclick="window.history.back();"
                style="font-size:12px; border-radius:6px;">
            Cancel
        </button>
    </div>

    <div class="card-body">

        <form id="editClientForm"
              method="POST"
              action="{{ route('book.update', $client->id) }}">

            @csrf
            @method('PUT')

            <!-- ==========================
                 BASIC INFO
            =========================== -->
            <h5 class="fw-bold mb-3">Basic Information</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="text-muted">First Name</label>
                    <input type="text" name="first_name"
                           value="{{ $client->first_name }}"
                           class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Last Name</label>
                    <input type="text" name="last_name"
                           value="{{ $client->last_name }}"
                           class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="text-muted">Email</label>
                    <input type="email" name="email"
                           value="{{ $client->email }}"
                           class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="text-muted">Phone</label>
                    <input type="text" name="phone"
                           value="{{ $client->phone }}"
                           class="form-control">
                </div>
            </div>

            <!-- ==========================
                 CLIENT INFO
            =========================== -->
            <h5 class="fw-bold mt-4 mb-2">Client Information</h5>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="text-muted">Date of Birth</label>
                    <input type="date" name="date_of_birth"
                           value="{{ $client->date_of_birth }}"
                           class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Anniversary</label>
                    <input type="date" name="anniversary"
                           value="{{ $client->anniversary }}"
                           class="form-control">
                </div>
            </div>

            <!-- ==========================
                 ADDRESS
            =========================== -->
            <h5 class="fw-bold mt-4 mb-2">Address</h5>

            <textarea name="address" class="form-control mb-3" rows="2"
                      placeholder="Street, City, State, ZIP">{{ $client->address }}</textarea>


            <!-- ==========================
                 POLICY INFORMATION
            =========================== -->
            <h5 class="fw-bold mt-4 mb-3">Policy Information</h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="text-muted">Policy Type</label>
                    <input type="text" name="policy_type"
                           value="{{ $client->policy_type }}"
                           class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="text-muted">Face Amount</label>
                    <input type="text" name="face_amount"
                           value="{{ $client->face_amount }}"
                           class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="text-muted">Premium Amount</label>
                    <input type="text" name="premium_amount"
                           value="{{ $client->premium_amount }}"
                           class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="text-muted">Recurring Due Date</label>
                    <input type="date" name="recurring_due_date"
                           value="{{ $client->recurring_due_date }}"
                           class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="text-muted">Policy Issue Date</label>
                    <input type="date" name="policy_issue_date"
                           value="{{ $client->policy_issue_date }}"
                           class="form-control">
                </div>
            </div>

            <!-- SAVE BUTTON -->
            <div class="mt-4">
                <button class="btn fw-bold"
                        style="background:#c9a227; color:#111827;
                               border:none; border-radius:8px; padding:10px 20px;">
                    Save Changes
                </button>
            </div>

        </form>
    </div>
</div>
