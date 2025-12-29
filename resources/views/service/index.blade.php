@extends('layouts.app')

@section('content')

<style>
    /* Same layout as Book */
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
        padding: 6px 10px;
        font-weight: 600;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
        font-size: 12px;
        cursor: pointer;
        white-space: nowrap;
        text-decoration: none;
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

                <!-- HEADER + ARCHIVE BUTTON -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="contacts-header mb-0">
                        Service Clients
                    </div>

                    <a href="{{ route('service.archive') }}" class="btn-gold btn-sm">
                        View Service Archive
                    </a>
                </div>

                {{-- ✅ FLASH MESSAGES (match Book/Leads behavior) --}}
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

                <!-- Search -->
                <div class="contacts-search-wrapper">
                    <input
                        type="text"
                        id="service-search"
                        class="contacts-search-input"
                        placeholder="Search service clients..."
                    >
                    <button class="contacts-search-btn" disabled>Go</button>
                </div>

                <!-- Add Client + Upload -->
                <div class="button-row">
                    <button
                        id="add-service-client-btn"
                        class="btn-gold"
                        data-create-url="{{ route('service.create.panel') }}"
                    >
                        Add
                    </button>

                    <button
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadServiceModal"
                    >
                        Upload
                    </button>
                </div>

                <!-- Client List -->
                <div id="service-list">
                    @forelse ($clients as $client)
                        @php
                            $name = $client->full_name ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
                        @endphp

                        <div
                            class="contact-list-item js-service-row {{ (isset($selected) && $selected == $client->id) ? 'active-contact-row' : '' }}"
                            data-id="{{ $client->id }}"
                            data-show-url="{{ route('service.show', $client->id) }}"
                        >
                            {{ $name ?: '(No Name)' }}

                            @if($client->policy_type)
                                <br><small class="text-muted">{{ $client->policy_type }}</small>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">No service clients found.</p>
                    @endforelse
                </div>

            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="col-md-8 col-lg-9">
            <div id="service-details-container" style="width:100%; min-height:400px;">
                <div class="empty-right-panel"></div>
            </div>
        </div>

    </div>
</div>

<!-- ✅ UPLOAD SERVICE MODAL -->
<div class="modal fade" id="uploadServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form
            action="{{ route('service.import') }}"
            method="POST"
            enctype="multipart/form-data"
            class="modal-content"
        >
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Upload Service Clients</h5>
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

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const container = document.getElementById('service-details-container');

    /* ===== LOAD RIGHT PANEL ===== */
    window.loadServicePanel = function (url) {
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

    /* ===== CLICK A CLIENT ===== */
    document.querySelectorAll('.js-service-row').forEach(row => {
        row.addEventListener('click', () => {

            document.querySelectorAll('.js-service-row')
                .forEach(r => r.classList.remove('active-contact-row'));

            row.classList.add('active-contact-row');

            loadServicePanel(row.dataset.showUrl);
        });
    });

    /* ===== ADD CLIENT BUTTON ===== */
    const addBtn = document.getElementById('add-service-client-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            loadServicePanel(this.dataset.createUrl);
        });
    }

    /* ===== CLIENT SIDE SEARCH ===== */
    const searchEl = document.getElementById('service-search');
    if (searchEl) {
        searchEl.addEventListener('keyup', function () {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#service-list .js-service-row')
                .forEach(row =>
                    row.style.display = row.textContent.toLowerCase().includes(term)
                        ? 'block'
                        : 'none'
                );
        });
    }

    /* ===== AUTO-LOAD SELECTED ===== */
    @if(!empty($selected))
        loadServicePanel("{{ route('service.show', $selected) }}");
    @endif

    /* ✅ If upload had errors, reopen modal (match Book/Leads) */
    @if(session('import_error') || $errors->any())
        const modalEl = document.getElementById('uploadServiceModal');
        if (modalEl) new bootstrap.Modal(modalEl).show();
    @endif
});
</script>
@endpush
