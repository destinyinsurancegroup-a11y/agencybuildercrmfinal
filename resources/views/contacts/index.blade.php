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

    /* Search bar */
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

    /* Buttons */
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
    }

    .btn-gold:hover {
        background: #b5901f;
    }

    .button-row {
        margin-bottom: 20px;
        display: flex;
        gap: 8px;
    }

    /* Contact list */
    .contact-list-item {
        padding: 10px 6px;
        font-size: 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        display: block;
    }

    .contact-list-item:hover {
        background: #f9fafb;
    }

    .contact-selected,
    .active-contact-row {
        background: #eae6d1 !important;
        font-weight: 600;
    }

    /* Right empty panel */
    .empty-right-panel {
        height: 100%;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
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

                <!-- Add Contacts + Upload File -->
                <div class="button-row">
                    <a href="{{ route('contacts.create') }}" class="btn-gold">Add Contacts</a>

                    <button 
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadModal"
                    >
                        Upload File
                    </button>
                </div>

                <!-- Contact List (NOW AJAX ENABLED) -->
                <div>
                    @forelse ($contacts as $contact)
                        <div 
                            class="contact-list-item js-contact-row"
                            data-contact-url="{{ route('contacts.show', $contact->id) }}"
                        >
                            {{ $contact->full_name }}
                        </div>
                    @empty
                        <p class="text-muted">No contacts found.</p>
                    @endforelse
                </div>

            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-md-8 col-lg-9">

            <!-- AJAX will load details here -->
            <div id="contact-details-container">
                <div class="empty-right-panel"></div>
            </div>

        </div>

    </div>
</div>


<!-- UPLOAD FILE MODAL -->
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
document.addEventListener('DOMContentLoaded', function () {

    const rows = document.querySelectorAll('.js-contact-row');
    const container = document.getElementById('contact-details-container');

    rows.forEach(row => {
        row.addEventListener('click', function () {

            // Remove highlight + activate clicked row
            rows.forEach(r => r.classList.remove('active-contact-row'));
            this.classList.add('active-contact-row');

            const url = this.dataset.contactUrl;

            // Loading animation
            container.innerHTML = `
                <div class="card" style="padding:40px; text-align:center;">
                    <div class="spinner-border text-warning" role="status"></div>
                    <p class="mt-3 text-muted">Loading contact...</p>
                </div>
            `;

            // AJAX call
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
            })
            .catch(err => {
                container.innerHTML = `
                    <div class="card" style="padding:40px; text-align:center; color:red;">
                        Failed to load contact details.
                    </div>
                `;
            });

        });
    });

});
</script>
@endpush
