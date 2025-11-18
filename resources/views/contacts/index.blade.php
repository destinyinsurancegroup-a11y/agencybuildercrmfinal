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

    /* Same width as dashboard card */
    .contacts-card-wrapper {
        width: 320px !important;
        max-width: 320px !important;
    }

    .contacts-header {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 12px;
    }

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

    .contact-selected {
        background: #eae6d1 !important;
        font-weight: 600;
    }

    /* Remove blank right panel spacing */
    .empty-right-panel {
        padding: 0 !important;
        margin: 0 !important;
        height: 100%;
        background: transparent;
        border: none !important;
        box-shadow: none !important;
    }

    /* NEW — Dashboard-style gold button */
    .btn-gold {
        background: #c9a227;
        color: #111827;
        border: none;
        padding: 8px 14px;
        font-weight: 600;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
        text-transform: uppercase;
        font-size: 13px;
    }

    .btn-gold:hover {
        background: #b5901f;
        color: #111;
    }

</style>

<div class="dashboard-page">

    <div class="row g-4">

        <!-- LEFT COLUMN — CONTACT LIST -->
        <div class="col-md-4 col-lg-3 contacts-card-wrapper">
            <div class="contacts-card">

                <div class="contacts-header">All Contacts</div>

                <!-- Search -->
                <form method="GET" action="{{ route('contacts.index') }}">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control mb-3"
                        placeholder="Search contacts..."
                        value="{{ request('search') }}"
                    >
                </form>

                <!-- Dashboard-style Buttons -->
                <div class="d-flex gap-2 mb-3">
                    <a href="{{ route('contacts.create') }}" class="btn-gold">+ Add</a>

                    <button 
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadModal"
                    >
                        Upload File
                    </button>
                </div>

                <!-- Contact List -->
                <div>
                    @forelse ($contacts as $contact)
                        <a href="{{ route('contacts.show', $contact->id) }}"
                            class="contact-list-item
                                {{ isset($selected) && $selected->id === $contact->id ? 'contact-selected' : '' }}">
                            {{ $contact->full_name }}
                        </a>
                    @empty
                        <p class="text-muted">No contacts found.</p>
                    @endforelse
                </div>

            </div>
        </div>

        <!-- RIGHT COLUMN — CONTACT DETAILS -->
        <div class="col-md-8 col-lg-9">
            @if(isset($selected) && $selected)
                @include('contacts.partials.detail', ['contact' => $selected])
            @else
                <div class="empty-right-panel"></div>
            @endif
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
