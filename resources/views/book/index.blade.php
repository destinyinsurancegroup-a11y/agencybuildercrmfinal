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
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
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


/* ---------- NOTES: ADD / EDIT / DELETE ---------- */
function saveNote(clientId) {
    const textarea = document.getElementById('new_note_body');
    if (!textarea) return;

    const body = textarea.value.trim();
    if (!body) {
        alert("Note cannot be empty.");
        return;
    }

    fetch(`/book/${clientId}/notes`, {
        method: 'POST',
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ body })
    })
    .then(response => {
        if (!response.ok) throw new Error('Failed to save note');
        // reload right panel so new note appears
        loadBookPanel(`/book/${clientId}`);
    })
    .catch(err => {
        console.error(err);
        alert("Error saving note.");
    });
}

function editNote(clientId, noteId) {
    const container = document.querySelector(`#note-${noteId} div:first-child`);
    if (!container) return;

    const existing = container.innerText;
    const updated = prompt("Edit note:", existing);
    if (updated === null) return;

    fetch(`/book/${clientId}/notes/${noteId}`, {
        method: 'PUT',
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ body: updated })
    })
    .then(response => {
        if (!response.ok) throw new Error('Failed to update note');
        loadBookPanel(`/book/${clientId}`);
    })
    .catch(err => {
        console.error(err);
        alert("Error updating note.");
    });
}

function deleteNote(clientId, noteId) {
    if (!confirm("Delete this note?")) return;

    fetch(`/book/${clientId}/notes/${noteId}`, {
        method: 'DELETE',
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Failed to delete note');
        loadBookPanel(`/book/${clientId}`);
    })
    .catch(err => {
        console.error(err);
        alert("Error deleting note.");
    });
}
</script>
@endpush
