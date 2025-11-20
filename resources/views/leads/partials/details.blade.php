<div class="card h-100 shadow-sm border-0">

    @php
        // Normalize variable naming
        $contact = $contact ?? $lead ?? null;
    @endphp

    @if ($contact)

        {{-- HEADER WITH LEAD NAME --}}
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
            <h2 class="fw-bold m-0">{{ $contact->first_name }} {{ $contact->last_name }}</h2>
        </div>

        {{-- DISPOSITION BUTTONS --}}
        <div class="px-4 pt-2 pb-1 d-flex gap-2">

            <button type="button"
                class="btn btn-success btn-sm fw-bold"
                onclick="alert('Sold action coming soon…');">
                Sold
            </button>

            <button type="button"
                class="btn btn-warning btn-sm fw-bold"
                onclick="alert('Follow Up action coming soon…');">
                Follow Up
            </button>

            <button type="button"
                class="btn btn-danger btn-sm fw-bold"
                onclick="alert('Not Interested action coming soon…');">
                Not Interested
            </button>

        </div>

        {{-- UPLOAD LEADS BUTTON --}}
        <div class="px-4 pb-3 pt-1">
            <button class="btn-gold btn-sm fw-bold"
                    data-bs-toggle="modal"
                    data-bs-target="#uploadLeadModal">
                Upload Leads File
            </button>
        </div>

        {{-- CONTACT DETAILS CARD --}}
        @include('contacts.partials.details', ['contact' => $contact])

    @else
        <div class="p-4">
            <p class="text-muted">Lead not found.</p>
        </div>
    @endif

</div>


{{-- ========================= --}}
{{-- LEADS UPLOAD MODAL --}}
{{-- ========================= --}}

<div class="modal fade" id="uploadLeadModal" tabindex="-1">
    <div class="modal-dialog">
        <form 
            action="{{ route('contacts.import') }}" 
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
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-gold">Upload</button>
            </div>

        </form>
    </div>
</div>
