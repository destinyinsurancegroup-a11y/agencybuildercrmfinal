@extends('layouts.app')

@section('content')
<div class="container-fluid" style="padding: 0 2rem;">

    <!-- PAGE TITLE -->
    <h1 class="mt-4 mb-2 fw-bold">All Contacts</h1>

    <!-- SEARCH + ADD + IMPORT -->
    <div class="d-flex align-items-center mb-3" style="gap: 10px;">
        <!-- Search bar (same style as dashboard) -->
        <form method="GET" action="{{ route('contacts.index') }}" class="flex-grow-1">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   class="form-control"
                   placeholder="Search contacts..."
                   style="border-radius: 8px; height: 45px;"
            >
        </form>

        <!-- Add Contact button -->
        <a href="{{ route('contacts.create') }}" class="btn btn-gold" style="height: 45px;">
            + Add Contact
        </a>

        <!-- Bulk Upload -->
        <a href="#" class="btn btn-outline-secondary" style="height: 45px;">
            Import CSV
        </a>
    </div>

    <div class="row" style="height: calc(100vh - 180px);">

        <!-- LEFT PANEL — CONTACT LIST -->
        <div class="col-md-4">

            <div class="card shadow-sm"
                 style="height: 100%; border-radius: 16px; overflow: hidden;">

                <div class="card-header bg-white fw-bold"
                     style="border-bottom: 1px solid #eee;">
                    Contacts
                </div>

                <div class="card-body p-0" style="height: 100%; overflow-y: auto;">
                    @forelse($contacts as $contact)
                        <a href="{{ route('contacts.show', $contact->id) }}"
                           class="list-group-item list-group-item-action"
                           style="
                               border: none;
                               padding: 16px;
                               font-size: 16px;
                               border-bottom: 1px solid #f2f2f2;
                               @if(isset($selected) && $selected && $selected->id === $contact->id)
                                   background: #f9f9f9;
                                   font-weight: 600;
                               @endif
                           ">
                            {{ $contact->full_name }}
                        </a>
                    @empty
                        <p class="p-3 text-muted">No contacts found.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL — CONTACT DETAILS -->
        <div class="col-md-8">

            @if($selected)
                <div class="card shadow-sm"
                     style="border-radius: 16px; padding: 25px;">

                    <h3 class="fw-bold mb-3">{{ $selected->full_name }}</h3>

                    <p><strong>Email:</strong> {{ $selected->email ?? '—' }}</p>
                    <p><strong>Phone:</strong> {{ $selected->phone ?? '—' }}</p>
                    <p><strong>Type:</strong> {{ $selected->contact_type ?? '—' }}</p>
                    <p><strong>Status:</strong> {{ $selected->status ?? '—' }}</p>

                    <hr>

                    <h5 class="fw-bold mt-3">Notes</h5>
                    <p>{{ $selected->notes ?? 'No notes added.' }}</p>

                </div>
            @else
                <div class="card shadow-sm d-flex justify-content-center align-items-center"
                     style="height: 100%; border-radius: 16px;">
                    <p class="text-muted">Select a contact from the left panel.</p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
