@extends('layouts.app')

@section('content')

<style>
    :root {
        --ab-bg-light: #f3f4f6;
        --ab-card-bg: #ffffff;
        --ab-border-subtle: #e5e7eb;
        --ab-text-main: #111827;
        --ab-text-muted: #6b7280;
        --ab-gold: #c9a227;
        --ab-gold-dark: #b5901f;
        --ab-gold-soft: #f3e6b8;
        --ab-shadow-soft:
            0 18px 30px -12px rgba(0,0,0,0.35),
            0 8px 16px -8px rgba(0,0,0,0.18);
    }

    .dashboard-page {
        padding-top: 10px;
    }

    .contacts-card-wrapper {
        width: 320px !important;
        max-width: 320px !important;
    }

    .contacts-card {
        background: var(--ab-card-bg);
        border-radius: 18px;
        padding: 22px 20px 18px;
        height: calc(100vh - 120px);
        overflow-y: auto;
        box-shadow: var(--ab-shadow-soft);
        border: 1px solid var(--ab-border-subtle);
        display: flex;
        flex-direction: column;
    }

    .contacts-header {
        font-size: 22px;
        font-weight: 700;
        color: var(--ab-text-main);
        margin-bottom: 16px;
        letter-spacing: 0.01em;
    }

    /* SEARCH BAR */
    .contacts-search-wrapper {
        display: flex;
        align-items: stretch;
        gap: 10px;
        margin-bottom: 14px;
    }

    .contacts-search-input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 999px;
        border: 1px solid #d1d5db;
        background: #ffffff;
        font-size: 14px;
        outline: none;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .contacts-search-input:focus {
        border-color: var(--ab-gold);
        box-shadow: 0 0 0 1px rgba(201,162,39,0.3);
    }

    .contacts-search-btn {
        padding: 0 18px;
        border-radius: 999px;
        border: none;
        background: var(--ab-gold);
        color: #111827;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .contacts-search-btn:hover {
        background: var(--ab-gold-dark);
    }

    /* GOLD BUTTON */
    .btn-gold {
        background: var(--ab-gold);
        color: #111827;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 999px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.20);
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.07em;
        cursor: pointer;
    }

    .btn-gold:hover {
        background: var(--ab-gold-dark);
    }

    .button-row {
        margin-bottom: 16px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* CONTACT LIST */
    .contacts-list-scroll {
        flex: 1;
        overflow-y: auto;
        padding-right: 2px;
    }

    .contact-list-item {
        padding: 10px 6px;
        font-size: 14px;
        border-radius: 10px;
        cursor: pointer;
        color: var(--ab-text-main);
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.12s ease, transform 0.08s ease;
    }

    .contact-list-item:hover {
        background: #f9fafb;
        transform: translateY(-1px);
    }

    .active-contact-row {
        background: var(--ab-gold-soft) !important;
        font-weight: 700;
        box-shadow: 0 0 0 1px rgba(201,162,39,0.4);
    }

    .contact-list-empty {
        font-size: 13px;
        color: var(--ab-text-muted);
    }

    /* RIGHT PANEL */
    #contact-details-container {
        width: 100%;
        min-height: 400px;
    }

    .empty-right-panel {
        height: calc(100vh - 120px);
        border-radius: 18px;
        border: 1px dashed #d1d5db;
        background: radial-gradient(circle at top left, #fdf6e3, #f3f4f6);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        text-align: center;
        padding: 32px;
        color: var(--ab-text-muted);
    }

    .empty-right-panel h2 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 6px;
        color: var(--ab-text-main);
    }
</style>

<div class="dashboard-page">
    <div class="row g-4">

        <!-- LEFT COLUMN -->
        <div class="col-md-4 col-lg-3 contacts-card-wrapper">
            <div class="contacts-card">

                <div class="contacts-header">All Contacts</div>

                <!-- Search -->
                <form method="GET" action="{{ route('contacts.index') }}">
                    <div class="contacts-search-wrapper">
                        <input 
                            type="text"
                            name="search"
                            class="contacts-search-input"
                            placeholder="Search contacts..."
                            value="{{ request('search') }}"
                        >
                        <button class="contacts-search-btn">Search</button>
                    </div>
                </form>

                <!-- Add Contact + Upload -->
                <div class="button-row">
                    <button 
                        id="add-contact-btn"
                        class="btn-gold"
                        data-create-url="{{ route('contacts.create.panel') }}"
                    >
                        Add Contact
                    </button>

                    <button 
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadModal"
                    >
                        Upload File
                    </button>
                </div>

                <!-- Contact List -->
                <div class="contacts-list-scroll">
                    @forelse ($contacts as $contact)
                        <div 
                            class="contact-list-item js-contact-row"
                            data-contact-url="{{ route('contacts.show', $contact->id) }}"
                            data-contact-id="{{ $contact->id }}"
                        >
                            {{ $contact->full_name }}
                        </div>
                    @empty
                        <p class="contact-list-empty">No contacts found.</p>
                    @endforelse
                </div>

            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-md-8 col-lg-9">
            <div id="contact-details-container">
                <div class="empty-right-panel">
                    <h2>Select a contact</h2>
                    <p>Or click <strong>Add Contact</strong> to create a new one.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- UPLOAD MODAL -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form 
            action="{{ route('contacts.import') }}" 
            method="POST" 
            enctype="multipart/form-data"
            class="modal-content"
        >
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Upload Contacts File</h5>
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
        .then(html => {
            container.innerHTML = html;
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

    // Contact row click handler
    document.querySelectorAll('.js-contact-row').forEach(row => {
        row.addEventListener('click', () => {
            document
                .querySelectorAll('.js-contact-row')
                .forEach(r => r.classList.remove('active-contact-row'));

            row.classList.add('active-contact-row');
            loadPanel(row.dataset.contactUrl);
        });
    });

    // Add Contact button
    const addBtn = document.getElementById('add-contact-btn');

    addBtn.addEventListener('click', () => {
        loadPanel(addBtn.dataset.createUrl);
    });

    // Auto-load selected contact after edit
    const selectedId = "{{ $selected ?? '' }}";

    if (selectedId) {
        const target = document.querySelector(
            `.js-contact-row[data-contact-id='${selectedId}']`
        );

        if (target) {
            target.click();
        }
    }

});
</script>
@endpush
