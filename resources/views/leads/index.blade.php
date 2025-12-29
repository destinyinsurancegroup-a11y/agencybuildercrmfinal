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

                {{-- Import errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger" style="border-radius:12px;">
                        <div class="fw-bold mb-1">Upload failed</div>
                        <ul class="mb-0" style="padding-left:18px;">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Import summary --}}
                @if (session('import_summary'))
                    @php($s = session('import_summary'))
                    <div class="alert alert-success" style="border-radius:12px;">
                        <div class="fw-bold mb-1">Upload results</div>
                        <div>Created: <strong>{{ $s['created'] ?? 0 }}</strong></div>
                        <div>Skipped: <strong>{{ $s['skipped'] ?? 0 }}</strong></div>

                        @if (!empty($s['row_errors']))
                            <hr style="margin:10px 0;">
                            <div class="fw-bold">Row issues (first {{ count($s['row_errors']) }})</div>
                            <ul class="mb-0" style="padding-left:18px;">
                                @foreach ($s['row_errors'] as $e)
                                    <li>Row {{ $e['row'] ?? '?' }}: {{ $e['error'] ?? 'Unknown error' }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

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

                    @if (Route::has('leads.import.template'))
                        <a class="btn-gold" style="text-decoration:none; display:inline-flex; align-items:center;"
                           href="{{ route('leads.import.template') }}">
                            Template
                        </a>
                    @endif
                </div>

                <!-- Lead List -->
                <div id="lead-list">
                    @forelse ($leads as $lead)
                        @php
                            $isArchivedView = !empty($showingArchived) && $showingArchived;
                            $status         = $lead->status ?? '';
                            $statusLower    = strtolower($status);
                            $isSold         = $statusLower === 'sold';

                            if ($isArchivedView && $isSold) {
                                $rowUrl = route('book.show', $lead->id);
                            } else {
                                $rowUrl = route('leads.show', $lead->id);
                            }
                        @endphp

                        <div
                            class="contact-list-item js-lead-row"
                            data-id="{{ $lead->id }}"
                            data-show-url="{{ $rowUrl }}"
                        >
                            <span>
                                {{ $lead->full_name ?? ($lead->first_name . ' ' . $lead->last_name) }}
                            </span>

                            @if($isArchivedView)
                                <span class="badge
                                    @if($statusLower === 'sold')
                                        bg-success
                                    @elseif($statusLower === 'not interested')
                                        bg-danger
                                    @else
                                        bg-secondary
                                    @endif">
                                    {{ $status }}
                                </span>
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
                <div class="form-text">
                    Blank fields are allowed. Each row creates a lead contact card.
                </div>
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

    const container = document.getElementById('contact-details-container');

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
        .then(html => container.innerHTML = html)
        .catch(() => {
            container.innerHTML = `
                <div style="padding:40px; text-align:center; color:red;">
                    Failed to load.
                </div>
            `;
        });
    }

    /* CLICK A LEAD */
    document.querySelectorAll('.js-lead-row').forEach(row => {
        row.addEventListener('click', () => {

            document.querySelectorAll('.js-lead-row')
                .forEach(r => r.classList.remove('active-contact-row'));

            row.classList.add('active-contact-row');

            loadPanel(row.dataset.showUrl);
        });
    });

    /* ADD LEAD */
    document.getElementById('add-lead-btn').addEventListener('click', function () {
        loadPanel(this.dataset.createUrl);
    });

    /* CLIENT SIDE SEARCH */
    document.getElementById('lead-search').addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#lead-list .js-lead-row')
            .forEach(row =>
                row.style.display = row.textContent.toLowerCase().includes(term)
                    ? 'flex'
                    : 'none'
            );
    });

    /* =======================================================
       AUTO-LOAD SELECTED LEAD AFTER EDIT/CREATE (from ?selected=)
       ======================================================= */
    (function () {
        const params = new URLSearchParams(window.location.search);
        const selected = params.get('selected');
        if (!selected) return;

        const row = document.querySelector(`.js-lead-row[data-id="${selected}"]`);
        if (row) {
            document.querySelectorAll('.js-lead-row')
                .forEach(r => r.classList.remove('active-contact-row'));
            row.classList.add('active-contact-row');

            loadPanel(row.dataset.showUrl);
        }
    })();

    window.saveLeadNote = function (contactId) {
        const bodyField = document.getElementById('lead_new_note_body');
        if (!bodyField) {
            console.error('lead_new_note_body textarea not found');
            alert('Could not find note field.');
            return;
        }

        const body = bodyField.value.trim();
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
                console.error('Error saving note:', txt);
                alert("Error saving note. Check /debug-laravel-log.");
                return;
            }
            loadPanel(`/leads/${contactId}`);
        })
        .catch(err => {
            console.error(err);
            alert("Network error saving note.");
        });
    };

    window.editLeadNote = function (contactId, noteId) {
        const existingEl = document.querySelector(`#lead-note-${noteId} div:first-child`);
        if (!existingEl) {
            console.error('Existing note element not found');
            return;
        }

        const existing = existingEl.innerText;
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
                console.error('Error updating note:', txt);
                alert("Error updating note. Check /debug-laravel-log.");
                return;
            }
            loadPanel(`/leads/${contactId}`);
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
                console.error('Error deleting note:', txt);
                alert("Error deleting note. Check /debug-laravel-log.");
                return;
            }
            loadPanel(`/leads/${contactId}`);
        })
        .catch(err => {
            console.error(err);
            alert("Network error deleting note.");
        });
    };

});
</script>
@endpush
