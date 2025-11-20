<div class="container-fluid py-4">

    <div class="card shadow-sm border-0"
         style="border-radius:18px; height: calc(100vh - 120px); overflow-y:auto;">

        <!-- HEADER -->
        <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between align-items-center"
             style="border-radius:18px 18px 0 0;">
            <span style="font-size:18px;">Edit Client</span>

            <!-- Cancel (returns to client details panel) -->
            <button 
                type="button" 
                class="btn btn-light"
                onclick="loadClientPanel({{ $client->id }})"
            >
                Cancel
            </button>
        </div>

        <div class="card-body">

            <form 
                id="edit-client-form"
                method="POST"
                action="{{ route('book.update', $client->id) }}"
            >
                @csrf
                @method('PUT')


                <!-- ============================================
                     BASIC INFORMATION
                ============================================= -->
                <h5 class="fw-bold mb-3">Basic Information</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted">First Name</label>
                        <input 
                            type="text" 
                            name="first_name" 
                            class="form-control"
                            value="{{ old('first_name', $client->first_name) }}"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="text-muted">Last Name</label>
                        <input 
                            type="text" 
                            name="last_name" 
                            class="form-control"
                            value="{{ old('last_name', $client->last_name) }}"
                            required
                        >
                    </div>
                </div>


                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted">Email</label>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control"
                            value="{{ old('email', $client->email) }}"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted">Phone</label>
                        <input 
                            type="text" 
                            name="phone" 
                            class="form-control"
                            value="{{ old('phone', $client->phone) }}"
                        >
                    </div>
                </div>

                <!-- DOB + Anniversary -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted">Date of Birth</label>
                        <input 
                            type="date" 
                            name="date_of_birth" 
                            class="form-control"
                            value="{{ old('date_of_birth', optional($client->date_of_birth)->format('Y-m-d')) }}"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted">Anniversary</label>
                        <input 
                            type="date" 
                            name="anniversary" 
                            class="form-control"
                            value="{{ old('anniversary', optional($client->anniversary)->format('Y-m-d')) }}"
                        >
                    </div>
                </div>



                <!-- ============================================
                     ADDRESS
                ============================================= -->
                <h5 class="fw-bold mt-4 mb-2">Address</h5>

                <div class="mb-3">
                    <label class="text-muted">Address Line 1</label>
                    <input 
                        type="text" 
                        name="address_line1" 
                        class="form-control"
                        value="{{ old('address_line1', $client->address_line1) }}"
                    >
                </div>

                <div class="mb-3">
                    <label class="text-muted">Address Line 2</label>
                    <input 
                        type="text" 
                        name="address_line2" 
                        class="form-control"
                        value="{{ old('address_line2', $client->address_line2) }}"
                    >
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="text-muted">City</label>
                        <input 
                            type="text" 
                            name="city" 
                            class="form-control"
                            value="{{ old('city', $client->city) }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="text-muted">State</label>
                        <input 
                            type="text" 
                            name="state" 
                            class="form-control"
                            value="{{ old('state', $client->state) }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="text-muted">Postal Code</label>
                        <input 
                            type="text" 
                            name="postal_code" 
                            class="form-control"
                            value="{{ old('postal_code', $client->postal_code) }}"
                        >
                    </div>
                </div>



                <!-- ============================================
                     POLICY DETAILS
                ============================================= -->
                <h5 class="fw-bold mt-4 mb-2">Policy Information</h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted">Policy Type</label>
                        <input 
                            type="text" 
                            name="policy_type" 
                            class="form-control"
                            value="{{ old('policy_type', $client->policy_type) }}"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted">Face Amount</label>
                        <input 
                            type="text" 
                            name="face_amount" 
                            class="form-control"
                            value="{{ old('face_amount', $client->face_amount) }}"
                        >
                    </div>
                </div>


                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted">Premium Amount</label>
                        <input 
                            type="text" 
                            name="premium_amount" 
                            class="form-control"
                            value="{{ old('premium_amount', $client->premium_amount) }}"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted">Recurring Due Date</label>
                        <input 
                            type="date" 
                            name="recurring_due_date" 
                            class="form-control"
                            value="{{ old('recurring_due_date', optional($client->recurring_due_date)->format('Y-m-d')) }}"
                        >
                    </div>
                </div>


                <div class="mb-3">
                    <label class="text-muted">Policy Issue Date</label>
                    <input
                        type="date"
                        name="policy_issue_date"
                        class="form-control"
                        value="{{ old('policy_issue_date', optional($client->policy_issue_date)->format('Y-m-d')) }}"
                    >
                </div>




                <!-- ============================================
                     NOTES (optional editable notes field)
                ============================================= -->
                <h5 class="fw-bold mt-4 mb-2">Notes</h5>

                <div class="mb-4">
                    <textarea 
                        name="notes" 
                        class="form-control" 
                        rows="4"
                    >{{ old('notes', $client->notes) }}</textarea>
                </div>




                <!-- ============================================
                     SAVE BUTTON
                ============================================= -->
                <button 
                    class="btn fw-bold"
                    style="background:#c9a227; color:#111827; border:none; border-radius:8px; padding:10px 20px;"
                >
                    Save Changes
                </button>

            </form>

        </div>
    </div>
</div>


<!-- Helper function to return to the client file panel -->
<script>
function loadClientPanel(id) {
    fetch(`/book/${id}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(res => res.text())
    .then(html => {
        document.querySelector('#client-details-container').innerHTML = html;
    });
}
</script>
