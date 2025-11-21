@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">

        <!-- ======================
             LEFT COLUMN — CLIENT LIST
        ======================= -->
        <div class="col-md-4" style="max-height: 90vh; overflow-y: auto;">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-gold">Book of Business</h4>

                <button 
                    class="btn-gold"
                    id="create-client-btn"
                    data-url="{{ route('book.create.panel') }}"
                >
                    + New Client
                </button>
            </div>

            <form method="GET" action="{{ route('book.index') }}" class="mb-3">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control"
                    placeholder="Search clients..."
                    value="{{ request('search') }}"
                >
            </form>

            <ul class="list-group">
                @foreach($clients as $c)
                    <li 
                        class="list-group-item client-select cursor-pointer {{ $selected == $c->id ? 'active' : '' }}"
                        data-id="{{ $c->id }}"
                        data-url="{{ route('book.show', $c->id) }}"
                    >
                        <strong>{{ $c->full_name }}</strong><br>
                        <small class="text-muted">{{ $c->email }}</small>
                    </li>
                @endforeach
            </ul>
        </div>


        <!-- ======================
             RIGHT COLUMN — AJAX PANEL
        ======================= -->
        <div class="col-md-8">
            <div id="book-right-panel" class="p-3">
                @if($selected)
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            let url = "{{ route('book.show', $selected) }}";
                            loadBookPanel(url);
                        });
                    </script>
                @else
                    <div class="text-muted">Select a client from the left.</div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function loadBookPanel(url) {
    fetch(url)
        .then(res => res.text())
        .then(html => {
            document.getElementById('book-right-panel').innerHTML = html;
        })
        .catch(err => console.error(err));
}

document.addEventListener('click', function (e) {
    if (e.target.closest('.client-select')) {
        const li = e.target.closest('.client-select');
        loadBookPanel(li.dataset.url);
    }

    if (e.target.id === 'create-client-btn') {
        loadBookPanel(e.target.dataset.url);
    }
});
</script>
@endpush
