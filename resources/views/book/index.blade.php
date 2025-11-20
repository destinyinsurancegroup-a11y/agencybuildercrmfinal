@extends('layouts.app')

@section('content')

<style>
    /* Match dashboard card styling */
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
        padding: 10px 16px;
        border-radius: 10px;
        border: none;
        background: #c9a227;
        color: #111827;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
        text-transform: uppercase;
    }

    .contacts-search-btn:hover {
        background: #b5901f;
    }

    .btn-gold {
        background: #c9a227;
        color: #111827;
        border: none;
        padding: 7px 14px;
        font-weight: 600;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
        text-transform: uppercase;
        font-size: 12px;
        cursor: pointer;
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

                <!-- Search -->
                <form method="GET" action="{{ route('book.index') }}">
                    <div class="contacts-search-wrapper">
                        <input 
                            type="text"
                            name="search"
                            class="contacts-search-input"
                            placeholder="Search clients..."
                            value="{{ request('search') }}"
                        >
                        <button class="contacts-search-btn">Search</button>
                    </div>
                </form>

                <!-- Add Client (AJAX) + Upload -->
                <div class="button-row">

                    <button 
                        id="add-client-btn"
                        class="btn-gold"
                        data-create-url="{{ route('book.create.panel') }}"
                    >
                        Add Client
                    </button>

                    <button 
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadModal"
                    >
                        Upload File
                    </button>
                </div>

                <!-- Client List -->
                <div>
                    @forelse ($clients as $client)
                        <div 
                            class="contact-list-item js-client-row"
                            data-client-url="{{ route('book.show', $client->id) }}"
                            data-client-id="{{ $client->id }}"
                        >
                            {{ $client->full_name ?? ($client->first_name . ' ' . $client->last_name) }}
                        </div>
                    @empty
                        <p class="text-muted">No clients found.</p>
                    @endforelse
                </div>

            </div>
        </div>


        <!-- RIGHT PANEL -->
        <div class="col-md-8 col-lg-9">
            <div id="client-details-container" style="width:100%; min-height:400px;">
                <div class="empty-right-panel"></div>
            </div>
        </div>

    </div>
</div>



<!-- UPLOAD FILE MODAL -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form 
            action="{{ route('book.import') }}" 
            method="POST" 
            enctype="multipart/form-data"
            class="modal-content"
        >
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Upload Clients File</h5>
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



@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const container = document.getElementById('client-details-container');

    function loadPanel(url) {

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
        .then(html => {
            container.innerHTML = html;
            container.style.display = "block";

            attachNotesHandlers(); 
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = `
                <div style="padding:40px; text-align:center; color:red;">
                    Failed to load.
                </div>
            `;
        });
    }


    /* ----- Load Client Details ----- */
    document.querySelectorAll('.js-client-row').forEach(row => {
        row.addEventListener('click', () => {

            document
                .querySelectorAll('.js-client-row')
                .forEach(r => r.classList.remove('active-contact-row'));

            row.classList.add('active-contact-row');

            loadPanel(row.dataset.clientUrl);
        });
    });


    /* ----- Load Create Form ----- */
    const addBtn = document.getElementById('add-client-btn');

    addBtn.addEventListener('click', () => {
        loadPanel(addBtn.dataset.createUrl);
    });


    /* AUTO-LOAD AFTER SAVE */
    const selectedId = "{{ $selected ?? '' }}";

    if (selectedId) {
        const target = document.querySelector(
            `.js-client-row[data-client-id='${selectedId}']`
        );

        if (target) {
            target.click();
        }
    }

});



/* ===== NOTES HANDLING ===== */
function attachNotesHandlers() {

    const form = document.getElementById("add-note-form");

    if (form) {
        form.addEventListener("submit", function(e) {
            e.preventDefault();

            const clientId = this.dataset.clientId;
            const url = this.action;
            const body = this.querySelector("textarea[name='body']").value;

            fetch(url, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ body })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    reloadClientFile(clientId);
                }
            });
        });
    }

    document.querySelectorAll(".edit-note-btn").forEach(btn => {
        btn.addEventListener("click", function() {

            const noteId = this.dataset.noteId;
            const bodyDiv = document.querySelector(
                `.note-body[data-note-id='${noteId}']`
            );

            const original = bodyDiv.textContent.trim();

            bodyDiv.innerHTML = `
                <textarea class="form-control edit-note-textarea" rows="3">${original}</textarea>
                <button class="btn btn-sm btn-success mt-1 save-note-btn" data-note-id="${noteId}">Save</button>
                <button class="btn btn-sm btn-secondary mt-1 cancel-note-btn" data-note-id="${noteId}">Cancel</button>
            `;

            attachNoteEditButtons(noteId);
        });
    });
}

function attachNoteEditButtons(noteId) {

    document.querySelector(`.save-note-btn[data-note-id='${noteId}']`)
        .addEventListener("click", function () {

            const body = document.querySelector(".edit-note-textarea").value;
            const clientId = document.getElementById("add-note-form").dataset.clientId;

            fetch(`/book-of-business/${clientId}/notes/${noteId}`, {
                method: "PUT",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ body })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    reloadClientFile(clientId);
                }
            });
        });

    document.querySelector(`.cancel-note-btn[data-note-id='${noteId}']`)
        .addEventListener("click", function () {

            const clientId = document.getElementById("add-note-form").dataset.clientId;
            reloadClientFile(clientId);
        });
}

function reloadClientFile(clientId) {

    fetch(`/book-of-business/${clientId}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(res => res.text())
    .then(html => {
        document.getElementById("client-details-container").innerHTML = html;
        attachNotesHandlers();
    });
}

</script>
@endpush
