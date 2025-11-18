@extends('layouts.app')

@section('content')

<div class="container-fluid py-4">

    <div class="row g-3">

        <!-- LEFT COLUMN — CONTACT LIST -->
        <div class="col-md-4 col-lg-3">

            <div class="card shadow-sm border-0 h-100">

                <!-- HEADER -->
                <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between align-items-center">
                    <span>All Contacts</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('contacts.create') }}" class="btn btn-sm btn-primary">+ Add</a>
                        <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#importModal">
                            Import
                        </button>
                    </div>
                </div>

                <!-- SEARCH BAR -->
                <div class="p-3 pb-0">
                    <form method="GET" action="{{ route('contacts.index') }}">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="Search contacts..."
                        >
                    </form>
                </div>

                <!-- LIST OF CONTACTS -->
                <div class="list-group list-group-flush mt-2" style="height: calc(100vh - 250px); overflow-y: auto;">
                    @forelse ($contacts as $contact)
                        <a 
                            href="{{ route('contacts.show', $contact->id) }}"
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                            {{ isset($selected) && $selected && $selected->id === $contact->id ? 'active' : '' }}"
                        >
                            <span>{{ $contact->full_name }}</span>
                        </a>
                    @empty
                        <div class="p-3 text-muted">
                            No contacts found.
                        </div>
                    @endforelse
                </div>

            </div>

        </div>


        <!-- RIGHT COLUMN — CONTACT DETAIL -->
        <div class="col-md-8 col-lg-9">

            @if(isset($selected) && $selected)
                @include('contacts.partials.detail', ['contact' => $selected])
            @else
                <div class="h-100 d-flex justify-content-center align-items-center text-muted" style="min-height: 60vh;">
                    <p>Select a contact from the left to view details.</p>
                </div>
            @endif

        </div>

    </div>

</div>


<!-- IMPORT MODAL -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog">
      <form 
        action="{{ route('contacts.import') }}" 
        method="POST" 
        enctype="multipart/form-data"
        class="modal-content"
      >
        @csrf

        <div class="modal-header bg-black text-gold">
            <h5 class="modal-title">Import Contacts</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

            <p class="text-muted mb-2">Upload a CSV or Excel file with contact data.</p>

            <input 
                type="file" 
                name="file" 
                class="form-control" 
                accept=".csv, .xlsx, .xls"
                required
            >

        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>

      </form>
  </div>
</div>

@endsection
