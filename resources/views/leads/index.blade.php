@extends('layouts.app')

@section('content')

<style>
    /* EXACT COPY OF CONTACTS CSS (unaltered) */
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
        white-space: nowrap;
    }

    .btn-gold:hover {
        background: #b5901f;
    }

    .button-row {
        margin-bottom: 20px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .contact-list-item {
        padding: 10px 6px;
        font-size: 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 6px;
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

    .flash-wrap {
        margin-bottom: 14px;
    }
</style>

<div class="dashboard-page">
    <div class="row g-4">

        <!-- LEFT COLUMN -->
        <div class="col-md-4 col-lg-3 contacts-card-wrapper">
            <div class="contacts-card">

                {{-- Header + Active/Archived toggle --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="contacts-header mb-0">
                        @if(!empty($showingArchived) && $showingArchived)
                            Archived Leads
                        @else
                            Leads
                        @endif
                    </div>

                    <div>
                        @if(!empty($showingArchived) && $showingArchived)
                            <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-secondary">
                                Back to Active
                            </a>
                        @else
                            <a href="{{ route('leads.archived') }}" class="btn btn-sm btn-outline-secondary">
                                View Archived
                            </a>
                        @endif
                    </div>
                </div>

                {{-- ✅ FLASH MESSAGES (match Book of Business behavior) --}}
                <div class="flash-wrap">
                    @if (session('import_success'))
                        <div class="alert alert-success py-2 mb-2">
                            {{ session('import_success') }}
                        </div>
                    @endif

                    @if (session('import_error'))
                        <div class="alert alert-danger py-2 mb-2">
                            {{ session('import_error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger py-2 mb-2">
                            <div><strong>Upload error:</strong></div>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- Search (client-side only) -->
                <div class="contacts-search-wrapper">
                    <input
                        type="text"
                        id="lead-search"
                        class="contacts-search-input"
                        placeholder="Search leads..."
                    >
                    <button class="contacts-search-btn" disabled>Search</button>
                </div>

                <!-- Add Lead + Upload -->
                <div class="button-row">
                    <button
                        id="add-lead-btn"
                        class="btn-gold"
                        data-create-url="{{ route('leads.create') }}"
                    >
                        Add Lead
                    </button>

                    <button
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadLeadModal"
                    >
                        Upload
                    </button>
                </div>

                <!-- Lead List -->
                <div id="lead-list">
                    @forelse ($leads as $lead)
                        @php
                            $isArchivedView = !empty($showingArchived) && $showingArchived;
                            $status         = (string)($lead->status ?? '');
                            $statusLower    = strtolower($status);
                            $isSold         = ($statusLower === 'sold');

                            $rowUrl = ($isArchivedView && $isSold)
                                ? route('book.show', $lead->id)
                                : route('leads.show', $lead->id);

                            // Blade-safe badge class (no @if inside attributes)
                            $badgeClass = 'badge bg-secondary';
                            if ($statusLower === 'sold') {
                                $badgeClass = 'badge bg-success';
                            } elseif ($statusLower === 'not interested') {
                                $badgeClass = 'badge bg-danger';
                            }

                            $name = $lead->full_name ?? trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? ''));
                        @endphp

                        <div
                            class="contact-list-item js-lead-row {{ (isset($selected) && $selected == $lead->id) ? 'active-contact-row' : '' }}"
                            data-id="{{ $lead->id }}"
                            data-show-url="{{ $rowUrl }}"
                        >
                            <span>{{ $name ?: '(No Name)' }}</span>

                            @if($isArchivedView)
                                <span class="{{ $badgeClass }}">{{ $status }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">No leads found.</p>
                    @endforelse
                </div>

            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="col-md-8 col-lg-9">
            <div id="contact-details-container" style="width:100%; min-height:400px;">
                <div class="empty-right-panel"></div>
            </div>
        </div>

    </div>
</div>

<!-- UPLOAD LEADS MODAL -->
<div class="modal fade" id="uploadLeadModal" tabindex="-1">
    <div class="modal-dialog">
        <form
            action="{{ route('leads.import') }}"
            method="POST"
            enctype="multipart/form-data"
            class="modal-content"
        >
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Upload Leads File</h5>
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
                <small class="text-muted d-block mt-2">
                    Tip: Header row should include fields like First Name / Last Name / Email / Phone, but blanks are allowed.
                </small>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-gold">Upload</button>
            </div>

        </form>
    </div>
</div>

{{-- ✅ REQUIRED for Leads Text/Email: includes modals + ABMessaging global --}}
@include('partials.messaging')

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const container = document.getElementById('contact-details-container');

    // ✅ Make loader available to AJAX partials + global note functions
    window.loadLeadPanel = function (url) {
        container.innerHTML = `
            <div style="padding:40px; text-align:center;">
                <div class="spinner-border text-warning" role="status"></div>
                <p class="mt-3 text-muted">Loading...</p>
            </div>
        `;

        fetch(url, { headers: {'X-Requested-With': 'XMLHttpRequest'} })
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

    /* CLICK A LEAD */
    document.querySelectorAll('.js-lead-row').forEach(row => {
        row.addEventListener('click', () => {
            document.querySelectorAll('.js-lead-row')
                .forEach(r => r.classList.remove('active-contact-row'));
            row.classList.add('active-contact-row');
            window.loadLeadPanel(row.dataset.showUrl);
        });
    });

    /* ADD LEAD */
    const addBtn = document.getElementById('add-lead-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            window.loadLeadPanel(this.dataset.createUrl);
        });
    }

    /* CLIENT SIDE SEARCH */
    document.getElementById('lead-search').addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#lead-list .js-lead-row')
            .forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term)
                    ? 'flex'
                    : 'none';
            });
    });

    // ✅ If upload had errors, reopen modal so user sees it (match Book tab)
    @if(session('import_error') || $errors->any())
        const modalEl = document.getElementById('uploadLeadModal');
        if (modalEl) new bootstrap.Modal(modalEl).show();
    @endif

    // Optional: auto-load selected lead if controller passes $selected
    @if(!empty($selected))
        window.loadLeadPanel("{{ route('leads.show', $selected) }}");
    @endif
});


/* =======================================================
   ✅ LEAD NOTES — MUST BE GLOBAL (AJAX partial calls these)
   ======================================================= */

window.saveLeadNote = function (contactId) {
    // This id must match your leads details partial textarea
    const textarea =
        document.getElementById('lead_new_note_body') ||
        document.getElementById('new_note_body'); // fallback if partial uses same as Book

    if (!textarea) {
        console.error('No note textarea found (expected #lead_new_note_body or #new_note_body).');
        alert('Could not find the note field on the page.');
        return;
    }

    const body = textarea.value.trim();
    if (!body) {
        alert("Note cannot be empty.");
        return;
    }

    fetch(`/leads/${contactId}/notes`, {
        method: 'POST',
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ body })
    })
    .then(async (res) => {
        if (!res.ok) {
            const txt = await res.text();
            console.error('Error saving lead note:', txt);
            alert('Error saving note. Check /debug-laravel-log.');
            return;
        }
        textarea.value = '';
        window.loadLeadPanel(`/leads/${contactId}`);
    })
    .catch(err => {
        console.error(err);
        alert("Network error saving note.");
    });
};

window.editLeadNote = function (contactId, noteId) {
    // Supports either markup:
    // - #lead-note-{id} .note-body
    // - #note-{id} .note-body
    const noteEl =
        document.querySelector(`#lead-note-${noteId} .note-body`) ||
        document.querySelector(`#note-${noteId} .note-body`) ||
        document.querySelector(`#lead-note-${noteId} div:first-child`);

    if (!noteEl) {
        console.error('Existing note element not found for noteId:', noteId);
        alert('Could not locate the note text to edit.');
        return;
    }

    const existing = (noteEl.innerText || '').trim();
    const updated = prompt("Edit note:", existing);
    if (updated === null) return;

    fetch(`/leads/${contactId}/notes/${noteId}`, {
        method: 'PUT',
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ body: updated })
    })
    .then(async (res) => {
        if (!res.ok) {
            const txt = await res.text();
            console.error('Error updating lead note:', txt);
            alert('Error updating note. Check /debug-laravel-log.');
            return;
        }
        window.loadLeadPanel(`/leads/${contactId}`);
    })
    .catch(err => {
        console.error(err);
        alert("Network error updating note.");
    });
};

window.deleteLeadNote = function (contactId, noteId) {
    if (!confirm("Delete this note?")) return;

    fetch(`/leads/${contactId}/notes/${noteId}`, {
        method: 'DELETE',
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    })
    .then(async (res) => {
        if (!res.ok) {
            const txt = await res.text();
            console.error('Error deleting lead note:', txt);
            alert('Error deleting note. Check /debug-laravel-log.');
            return;
        }
        window.loadLeadPanel(`/leads/${contactId}`);
    })
    .catch(err => {
        console.error(err);
        alert("Network error deleting note.");
    });
};
</script>
@endpush
