@extends('layouts.app')

@section('content')

<div class="container py-4">

    <!-- Page Title -->
    <h1 class="fw-bold mb-4">All Contacts</h1>

    <!-- SEARCH + BUTTONS (same style as dashboard) -->
    <div class="d-flex mb-3" style="gap: 12px;">
        <form method="GET" action="{{ route('contacts.index') }}" class="flex-grow-1">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   class="form-control"
                   placeholder="Search contacts..."
                   style="height: 45px; border-radius:10px;">
        </form>

        <a href="{{ route('contacts.create') }}"
           class="btn btn-gold px-4"
           style="height: 45px; display:flex; align-items:center; border-radius:10px;">
            + Add Contact
        </a>

        <a href="#"
           class="btn btn-outline-secondary px-4"
           style="height: 45px; display:flex; align-items:center; border-radius:10px;">
            Import CSV
        </a>
    </div>


    <!-- MASTER–DETAIL LAYOUT -->
    <div class="row" style="min-height: 75vh;">

        <!-- LEFT COLUMN – LONG VERTICAL CARD -->
        <div class="col-md-4">

            <div class="card shadow"
                 style="height: 75vh; border-radius:16px; overflow:hidden;">

                <div class="card-header bg-white fw-bold" style="font-size:18px;">
                    Contacts
                </div>

                <div class="card-body p-0" style="overflow-y: auto;">

                    @forelse($contacts as $contact)
                        <a href="{{ route('contacts.show', $contact->id) }}"
                           class="d-block px-3 py-3"
                           style="
                               text-decoration:none;
                               color:#000;
                               border-bottom:1px solid #f1f1f1;
                               font-size:16px;
                               @if(isset($selected) && $selected && $selected->id === $contact->id)
                                   background:#f7f7f7;
                                   font-weight:600;
                               @endif
                           ">
                            {{ $contact->full_name }}
                        </a>
                    @empty
                        <p class="p-3 text-muted">
                            No contacts found.
                        </p>
                    @endforelse

                </div>
            </div>

        </div>


        <!-- RIGHT COLUMN – CONTACT DETAIL CARD -->
        <div class="col-md-8">

            @if($selected)
                <div class="card shadow" style="border-radius:16px;">
                    <div class="card-body">

                        <h3 class="fw-bold mb-3">{{ $selected->full_name }}</h3>

                        <p><strong>Email:</strong> {{ $selected->email ?? '—' }}</p>
                        <p><strong>Phone:</strong> {{ $selected->phone ?? '—' }}</p>
                        <p><strong>Type:</strong> {{ $selected->contact_type ?? '—' }}</p>
                        <p><strong>Status:</strong> {{ $selected->status ?? '—' }}</p>

                        <hr>

                        <h5 class="fw-bold mt-3">Notes</h5>
                        <p>{{ $selected->notes ?? 'No notes added.' }}</p>

                    </div>
                </div>
            @else
                <!-- Empty Placeholder Card -->
                <div class="card shadow d-flex align-items-center justify-content-center"
                     style="height:75vh; border-radius:16px;">
                    <p class="text-muted">Select a contact from the left panel.</p>
                </div>
            @endif

        </div>

    </div>

</div>

@endsection
