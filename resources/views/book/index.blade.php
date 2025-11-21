@extends('layouts.app')

@section('content')

<style>
    /* Same card/layout styling used on Leads/Contacts */
    .contacts-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 22px;
        height: calc(100vh - 120px);
        overflow-y: auto;
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.35),
            0 8px 16px -8px rgba(0,0,0,0.18);
        border: 1px solid #e5e7eb;
    }

    .contacts-card-wrapper {
        width: 320px !important;
        max-width: 320px !important;
    }

    .contacts-header {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 18px;
    }

    .contacts-search-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
    }

    .contacts-search-input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        background: #ffffff;
        font-size: 14px;
    }

    .contacts-search-btn {
        padding: 7px 10px;
        border-radius: 8px;
        border: none;
        background: #c9a227;
        color: #111827;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
    }

    .contacts-search-btn:hover {
        background: #b5901f;
    }

    .btn-gold {
        background: #c9a227;
        color: #111827;
        border: none;
        padding: 6px 10px;   /* smaller buttons */
        font-weight: 600;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
        font-size: 12px;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-gold:hover {
        background: #b5901f;
    }

    .button-row {
        margin-bottom: 20px;
        display: flex;
        gap: 8px;
    }

    .contact-list-item {
        padding: 10px 6px;
        font-size: 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }

    .contact-list-item:hover {
        background: #f9fafb;
    }

    .active-contact-row {
        background: #eae6d1 !important;
        font-weight: 600;
    }

    .empty-right-panel {
        height: 100%;
        background: transparent !important;
    }
</style>

<div class="dashboard-page">
    <div class="row g-4">

        <!-- LEFT COLUMN -->
        <div class="col-md-4 col-lg-3 contacts-card-wrapper">
            <div class="contacts-card">

                <div class="contacts-header">Book of Business</div>

                <!-- Search (client-side only) -->
                <div class="contacts-search-wrapper">
                    <input 
                        type="text"
                        id="book-search"
                        class="contacts-search-input"
                        placeholder="Search clients..."
                    >
                    <button class="contacts-search-btn" disabled>Go</button>
                </div>

                <!-- Add Client + Upload -->
                <div class="button-row">
                    <button 
                        id="add-book-client-btn"
                        class="btn-gold"
                        data-create-url="{{ route('book.create.panel') }}"
                    >
                        Add
                    </button>

                    <button 
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadBookModal"
                    >
                        Upload
                    </button>
                </div>

                <!-- Client List -->
                <div id="book-list">
                    @forelse ($clients as $client)
                        <div 
                            class="contact-list-item js-book-row {{ (isset($selected) && $selected == $client->id) ? 'active-contact-row' : '' }}"
                            data-id="{{ $client->id }}"
                            data-show-url="{{ route('book.show', $client->id) }}"
                        >
                            {{ $client->full_name }}

                            @if($client->policy_type)
                                <br><small class="text-muted">{{ $client->policy_type }}</small>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">No clients found.</p>
                    @endforelse
                </div>

            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="col-md-8 col-lg-9">
            <div id="book-details-container" style="width:100%; min-height:400px;">
                <div class="empty-right-panel"></div>
            </div>
        </div>

    </div>
</div>

<!-- UPLOAD BOOK MODAL -->
<div class="modal fade" id="uploadBookModal" tabindex="-1">
    <div class="modal-dialog">
        <form 
            action="{{ route('book.import') }}" 
            method="POST" 
            enctype="multipart/form-data"
            class="modal-content"
        >
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Upload Book of Business</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">Choose CSV or Excel file</label>
                <input 
                    type="file"
                    name="file"
                    class="form-control"
                    accept=".csv, .xlsx, .xls"
                    required
                >
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-gold">Upload</button>
            </div>

        </form>
    </div>
</div>

@endsection



{{-- ============================================================
     JAVASCRIPT — GLOBAL BEC HANDLERS
     ============================================================ --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const container = document.getElementById('book-details-container');

    // Loads right panel via AJAX
    window.loadBookPanel = function (url) {
        container.innerHTML = `
            <div style="padding:40px; text-align:center;">
                <div class="spinner-border text-warning" role="status"></div>
                <p class="mt-3 text-muted">Loading...</p>
            </div>
        `;

        fetch(url, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.text())
        .then(html => container.innerHTML = html)
        .catch(() => {
            container.innerHTML = `
                <div style="padding:40px; text-align:center; color:red;">
                    Failed to load.
                </div>
            `;
        });
    };

    /* CLICK A CLIENT */
    document.querySelectorAll('.js-book-row').forEach(row => {
        row.addEventListener('click', () => {

            document.querySelectorAll('.js-book-row')
                .forEach(r => r.classList.remove('active-contact-row'));

            row.classList.add('active-contact-row');

            loadBookPanel(row.dataset.showUrl);
        });
    });

    /* ADD CLIENT */
    const addBtn = document.getElementById('add-book-client-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            loadBookPanel(this.dataset.createUrl);
        });
    }

    /* CLIENT SIDE SEARCH */
    document.getElementById('book-search').addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#book-list .js-book-row')
            .forEach(row =>
                row.style.display = row.textContent.toLowerCase().includes(term)
                    ? 'block'
                    : 'none'
            );
    });

    // Auto-load selected client
    @if(!empty($selected))
        loadBookPanel("{{ route('book.show', $selected) }}");
    @endif
});


/* ------------------------------------------------------
   BEC SECTION — MOVED HERE SO AJAX PARTIALS CAN USE IT
   ------------------------------------------------------ */

/* ---------- ADD BENEFICIARY ---------- */
function openAddBeneficiary(clientId) {
    document.getElementById('beneficiaryModalTitle').innerText = "Add Beneficiary";
    document.getElementById('beneficiary_id').value = "";
    document.getElementById('beneficiary_client_id').value = clientId;

    document.getElementById('beneficiary_name').value = "";
    document.getElementById('beneficiary_relationship').value = "";
    document.getElementById('beneficiary_phone').value = "";
    document.getElementById('beneficiary_contacted').value = "0";

    new bootstrap.Modal(document.getElementById('beneficiaryModal')).show();
}

/* ---------- EDIT BENEFICIARY ---------- */
function editBeneficiary(id) {
    fetch(`/api/beneficiaries/${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('beneficiaryModalTitle').innerText = "Edit Beneficiary";

            document.getElementById('beneficiary_id').value = data.id;
            document.getElementById('beneficiary_client_id').value = data.contact_id;

            document.getElementById('beneficiary_name').value = data.name;
            document.getElementById('beneficiary_relationship').value = data.relationship ?? "";
            document.getElementById('beneficiary_phone').value = data.phone ?? "";
            document.getElementById('beneficiary_contacted').value = data.contacted ? "1" : "0";

            new bootstrap.Modal(document.getElementById('beneficiaryModal')).show();
        });
}

/* ---------- SAVE BENEFICIARY ---------- */
document.addEventListener("submit", function (e) {
    if (e.target.id !== "beneficiaryForm") return;
    e.preventDefault();

    let id = document.getElementById('beneficiary_id').value;
    let clientId = document.getElementById('beneficiary_client_id').value;

    let url = id
        ? `/book/${clientId}/beneficiaries/${id}`
        : `/book/${clientId}/beneficiaries`;

    let method = id ? "PUT" : "POST";

    fetch(url, {
        method: method,
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            name: document.getElementById('beneficiary_name').value,
            relationship: document.getElementById('beneficiary_relationship').value,
            phone: document.getElementById('beneficiary_phone').value,
            contacted: document.getElementById('beneficiary_contacted').value
        })
    })
    .then(r => r.json())
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('beneficiaryModal')).hide();
        loadBookPanel(`/book/${clientId}`);
    });
});

/* ---------- DELETE BENEFICIARY ---------- */
function deleteBeneficiary(clientId, id) {
    if (!confirm("Delete beneficiary?")) return;

    fetch(`/book/${clientId}/beneficiaries/${id}`, {
        method: "DELETE",
        headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" }
    })
    .then(r => r.json())
    .then(() => loadBookPanel(`/book/${clientId}`));
}



/* ---------- ADD EMERGENCY CONTACT ---------- */
function openAddEmergency(clientId) {
    document.getElementById('emergencyModalTitle').innerText = "Add Emergency Contact";
    document.getElementById('emergency_id').value = "";
    document.getElementById('emergency_client_id').value = clientId;

    document.getElementById('emergency_name').value = "";
    document.getElementById('emergency_relationship').value = "";
    document.getElementById('emergency_phone').value = "";
    document.getElementById('emergency_contacted').value = "0";

    new bootstrap.Modal(document.getElementById('emergencyModal')).show();
}

/* ---------- EDIT EMERGENCY ---------- */
function editEmergency(id) {
    fetch(`/api/emergency/${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('emergencyModalTitle').innerText = "Edit Emergency Contact";

            document.getElementById('emergency_id').value = data.id;
            document.getElementById('emergency_client_id').value = data.contact_id;

            document.getElementById('emergency_name').value = data.name;
            document.getElementById('emergency_relationship').value = data.relationship ?? "";
            document.getElementById('emergency_phone').value = data.phone ?? "";
            document.getElementById('emergency_contacted').value = data.contacted ? "1" : "0";

            new bootstrap.Modal(document.getElementById('emergencyModal')).show();
        });
}

/* ---------- SAVE EMERGENCY ---------- */
document.addEventListener("submit", function (e) {
    if (e.target.id !== "emergencyForm") return;
    e.preventDefault();

    let id = document.getElementById('emergency_id').value;
    let clientId = document.getElementById('emergency_client_id').value;

    let url = id
        ? `/book/${clientId}/emergency/${id}`
        : `/book/${clientId}/emergency`;

    let method = id ? "PUT" : "POST";

    fetch(url, {
        method: method,
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            name: document.getElementById('emergency_name').value,
            relationship: document.getElementById('emergency_relationship').value,
            phone: document.getElementById('emergency_phone').value,
            contacted: document.getElementById('emergency_contacted').value
        })
    })
    .then(r => r.json())
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('emergencyModal')).hide();
        loadBookPanel(`/book/${clientId}`);
    });
});

/* ---------- DELETE EMERGENCY ---------- */
function deleteEmergency(clientId, id) {
    if (!confirm("Delete emergency contact?")) return;

    fetch(`/book/${clientId}/emergency/${id}`, {
        method: "DELETE",
        headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" }
    })
    .then(r => r.json())
    .then(() => loadBookPanel(`/book/${clientId}`));
}

</script>
@endpush
