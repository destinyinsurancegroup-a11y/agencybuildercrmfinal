<div class="p-4" style="margin-top:-28px;">

    <div class="card shadow-sm border-0">

        <form method="POST" action="{{ route('service.store') }}" class="p-4">
            @csrf

            <!-- BASIC INFORMATION -->
            <h5 class="mb-3 text-gold fw-bold">Basic Information</h5>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">First Name</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Age (auto)</label>
                    <input type="number" class="form-control" disabled placeholder="Auto">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Anniversary</label>
                    <input type="date" name="anniversary" class="form-control">
                </div>
            </div>

            <h6 class="fw-bold mt-3 mb-2 text-gold">Address / Contact</h6>

            <div class="mb-2">
                <label class="form-label fw-semibold">Address Line 1</label>
                <input type="text" name="address_line1" class="form-control">
            </div>

            <div class="mb-2">
                <label class="form-label fw-semibold">Address Line 2</label>
                <input type="text" name="address_line2" class="form-control">
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">City</label>
                    <input type="text" name="city" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">State</label>
                    <input type="text" name="state" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control">
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
            </div>

            <hr>

            <!-- POLICY / SERVICE INFO -->
            <h5 class="mb-3 text-gold fw-bold">Service Policy Info</h5>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Carrier</label>
                    <input type="text" name="carrier" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Policy Type</label>
                    <input type="text" name="policy_type" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Face Amount</label>
                    <input type="number" step="0.01" name="face_amount" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Monthly Premium</label>
                    <input type="number" step="0.01" name="premium_amount" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Service Status</label>
                    <select name="status" class="form-select">
                        <option value="">Select...</option>
                        <option value="Active">Active</option>
                        <option value="Lapsed">Lapsed</option>
                        <option value="Pending Reinstatement">Pending Reinstatement</option>
                        <option value="Deceased">Deceased</option>
                        <option value="Service Issue">Service Issue</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date Issued</label>
                    {{-- reuse policy_issue_date --}}
                    <input type="date" name="policy_issue_date" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date Lapsed</label>
                    {{-- reuse premium_due_date as "lapsed" for service tab --}}
                    <input type="date" name="premium_due_date" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Monthly Due Text (optional)</label>
                    <input type="text" name="premium_due_text" class="form-control" placeholder="Ex: 15th of every month">
                </div>
            </div>

            <hr>

            <!-- SERVICE EVENTS -->
            <h5 class="mb-3 text-gold fw-bold">Service Events</h5>

            <div id="service-events-wrapper">

                <div class="service-event-row border rounded p-3 mb-3">
                    <div class="row g-2 align-items-start">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date</label>
                            <input type="date" name="events[0][event_date]" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Type</label>
                            <select name="events[0][event_type]" class="form-select">
                                <option value="">Select...</option>
                                <option value="Lapsed">Lapsed</option>
                                <option value="Payment Late">Payment Late</option>
                                <option value="Payment Received">Payment Received</option>
                                <option value="Reinstatement">Reinstatement</option>
                                <option value="Client Deceased">Client Deceased</option>
                                <option value="Carrier Call">Carrier Call</option>
                                <option value="Client Call">Client Call</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="events[0][notes]" class="form-control" rows="2"
                                      placeholder="Describe what happened..."></textarea>
                        </div>
                    </div>
                </div>

            </div>

            <button type="button" class="btn btn-sm btn-gold mb-3" id="add-service-event-btn">
                + Add Service Event
            </button>

            <hr>

            <!-- NOTES -->
            <h5 class="mb-3 text-gold fw-bold">Internal Notes</h5>
            <div class="mb-4">
                <textarea name="notes" class="form-control" rows="4"
                          placeholder="Internal notes about this service case..."></textarea>
            </div>

            <div class="text-start">
                <button class="btn btn-gold btn-lg">Save Service Client</button>
            </div>

        </form>

    </div>
</div>

<script>
let serviceEventIndex = 1;

document.getElementById('add-service-event-btn').addEventListener('click', () => {
    const wrapper = document.getElementById('service-events-wrapper');

    wrapper.insertAdjacentHTML('beforeend', `
        <div class="service-event-row border rounded p-3 mb-3">
            <div class="row g-2 align-items-start">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date</label>
                    <input type="date" name="events[${serviceEventIndex}][event_date]" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Type</label>
                    <select name="events[${serviceEventIndex}][event_type]" class="form-select">
                        <option value="">Select...</option>
                        <option value="Lapsed">Lapsed</option>
                        <option value="Payment Late">Payment Late</option>
                        <option value="Payment Received">Payment Received</option>
                        <option value="Reinstatement">Reinstatement</option>
                        <option value="Client Deceased">Client Deceased</option>
                        <option value="Carrier Call">Carrier Call</option>
                        <option value="Client Call">Client Call</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="events[${serviceEventIndex}][notes]" class="form-control" rows="2"
                              placeholder="Describe what happened..."></textarea>
                </div>
            </div>
        </div>
    `);

    serviceEventIndex++;
});
</script>
