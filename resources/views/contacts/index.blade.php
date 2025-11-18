@extends('layouts.app')

@section('content')

<!-- FIX: wrap content so it aligns like the dashboard -->
<div class="content p-4" style="margin-left: 250px;">

<div class="container-fluid py-4">

    <div class="row g-4">

        <!-- LEFT COLUMN — TALL VERTICAL DASHBOARD-STYLE CARD -->
        <div class="col-md-4 col-lg-3">

            <div class="card shadow border-0" style="border-radius: 16px; min-height: 80vh;">

                <!-- CARD HEADER -->
                <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between align-items-center" 
                     style="border-radius: 16px 16px 0 0;">
                    <span>All Contacts</span>

                    <div class="d-flex gap-2">
                        <a href="{{ route('contacts.create') }}" class="btn btn-sm btn-primary">+ Add</a>

                        <button class="btn btn-sm btn-outline-light"
                                data-bs-toggle="modal"
                                data-bs-target="#importModal">
                            Import
                        </button>
                    </div>
                </div>

                <!-- SEARCH BAR ABOVE LIST -->
                <div class="p-3 pt-3 pb-0">
                    <form method="GET" action="{{ route('contacts.index') }}">
                        <input 
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="Search contacts..."
                            style="height: 42px; border-radius: 10px;"
                        >
                    </form>
                </div>

                <!-- CONTACT LIST INSIDE THE CARD -->
                <div class="p-2" style="overflow-y: auto; max-height: calc(80vh - 140px);">
                    
                    @forelse ($contacts as $contact)
                        <a href="{{ route('contacts.show', $contact->id) }}"
                           class="list-group-item list-group-item-action border-0 py-2 px-2 mb-1 shadow-sm
                           {{ isset($selected) && $selected && $selected->id === $contact->id ? 'active' : '' }}"
                           style="border-radius: 8px;">
                            <strong>{{ $contact->full_name }}</strong>
                        </a>
                    @empty
                        <p class="text-muted px-2 mt-3">No contacts found.</p>
                    @endforelse

                </div>

            </div>

        </div>



        <!-- RIGHT COLUMN — CONTACT DETAILS -->
        <div class="col-md-8 col-lg-9">

            @if(isset($selected) && $selected)

                @include('contacts.partials.detail', ['contact' => $selected])

            @else

                <div class="card shadow border-0 d-flex justify-content-center align-items-center"
                     style="border-radius: 16px; min-height: 80vh;">
                    <p class="text-muted">
                        Select a contact from the left to view details.
                    </p>
                </div>

            @endif

        </div>

    </div>

</div>

</div> <!-- END OF WRAPPER FIX -->

@endsection
