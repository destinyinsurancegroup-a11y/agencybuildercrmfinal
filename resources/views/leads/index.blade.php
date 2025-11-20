@extends('layouts.app')

@section('content')
<div class="row h-100">
    
    <!-- LEFT PANEL: LIST OF LEADS -->
    <div class="col-md-4 border-end pe-0 d-flex flex-column">
        
        <!-- Header with search + add lead button -->
        <div class="p-3 border-bottom">
            <h4 class="mb-2">Leads</h4>

            <!-- Search -->
            <input type="text" id="lead-search" class="form-control form-control-sm" placeholder="Search leads...">

            <!-- Buttons -->
            <div class="mt-2 d-flex gap-2">
                <button id="add-lead-btn" class="btn btn-sm btn-primary">+ Add Lead</button>
            </div>
        </div>

        <!-- Lead List -->
        <div class="flex-grow-1 overflow-auto">
            <table class="table table-sm mb-0">
                <tbody id="lead-list">
                    @foreach($leads as $lead)
                        <tr class="js-lead-row" data-id="{{ $lead->id }}" style="cursor: pointer;">
                            <td class="py-2 px-3">
                                <div class="fw-bold">
                                    {{ $lead->last_name }}, {{ $lead->first_name }}
                                </div>
                                @if($lead->phone)
                                    <div class="small text-muted">{{ $lead->phone }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- RIGHT PANEL: DETAILS / CREATE FORM -->
    <div class="col-md-8 ps-0">
        <div id="contact-details-container" class="h-100">
            <div class="h-100 d-flex justify-content-center align-items-center text-muted">
                Select a lead or click "Add Lead"
            </div>
        </div>
    </div>

</div>
@endsection



@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ---------------------------
       CLICK LEAD ROW (AJAX LOAD)
    ----------------------------*/
    $(document).on('click', '.js-lead-row', function () {
        const id = $(this).data('id');
        const url = "{{ route('leads.show', ':id') }}".replace(':id', id);
        $('#contact-details-container').load(url);
    });

    /* ---------------------------
       ADD LEAD BUTTON (AJAX LOAD)
    ----------------------------*/
    $('#add-lead-btn').on('click', function () {
        const url = "{{ route('leads.create') }}";
        $('#contact-details-container').load(url);
    });

    /* ---------------------------
       CLIENT-SIDE SEARCH
    ----------------------------*/
    $('#lead-search').on('keyup', function () {
        const term = $(this).val().toLowerCase();
        $('#lead-list tr').each(function () {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(term));
        });
    });

});
</script>
@endpush
