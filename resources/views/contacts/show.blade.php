@extends('layouts.app')

@section('content')

<style>
    .contact-profile-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 22px;
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.35),
            0 8px 16px -8px rgba(0,0,0,0.18);
        border: 1px solid #e5e7eb;
        height: calc(100vh - 120px);
        overflow-y: auto;
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
    }

    .btn-gold:hover {
        background: #b5901f;
    }
</style>

<div class="dashboard-page">

    <div class="contact-profile-card">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">{{ $contact->full_name }}</h2>

            <a href="{{ route('contacts.edit', $contact->id) }}" class="btn-gold">
                Edit Contact
            </a>
        </div>

        {{-- PRIMARY DETAILS --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="text-muted">Email</label>
                <div class="fw-semibold">{{ $contact->email ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <label class="text-muted">Phone</label>
                <div class="fw-semibold">{{ $contact->phone ?: '—' }}</div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <label class="text-muted">Contact Type</label>
                <div class="fw-semibold">{{ $contact->contact_type ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <label class="text-muted">Status</label>
                <span class="badge bg-secondary" style="font-size:12px; padding:6px 10px;">
                    {{ $contact->status ?: '—' }}
                </span>
            </div>
        </div>

        {{-- ADDRESS --}}
        <div class="mb-4">
            <label class="text-muted">Address</label>
            <div class="fw-semibold">
                @if($contact->address_line1)
                    {{ $contact->address_line1 }}<br>
                    {{ $contact->city }} {{ $contact->state }} {{ $contact->postal_code }}
                @else
                    —
                @endif
            </div>
        </div>

        <hr>

        {{-- TABS --}}
        <ul class="nav nav-tabs mt-4 fw-bold" id="contactPageTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-details">
                    Details
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-notes">
                    Notes
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs">
                    Documents
                </button>
            </li>
        </ul>

        {{-- TAB CONTENT --}}
        <div class="tab-content pt-3">

            {{-- DETAILS TAB --}}
            <div class="tab-pane fade show active" id="tab-details">
                <h5 class="fw-bold mb-2">Additional Details</h5>
                <p class="text-muted">More custom contact information can go here.</p>
            </div>

            {{-- NOTES TAB --}}
            <div class="tab-pane fade" id="tab-notes">
                <h5 class="fw-bold mb-2">Notes</h5>
                <p class="text-muted">Notes functionality coming soon.</p>
            </div>

            {{-- DOCUMENTS TAB --}}
            <div class="tab-pane fade" id="tab-docs">
                <h5 class="fw-bold mb-2">Documents</h5>
                <p class="text-muted">Document uploads & previews coming soon.</p>
            </div>

        </div>

    </div>

</div>

@endsection
