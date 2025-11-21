@extends('layouts.app')

@section('content')

<div class="container py-4">
    <div class="card shadow-sm border-0 p-4">
        <h2 class="fw-bold mb-3">Activity</h2>

        <p class="text-muted mb-3">
            Click the button below to view your current production activity.
        </p>

        <button 
            class="btn-gold"
            onclick="openActivityPanel()"
        >
            View Activity Summary
        </button>
    </div>
</div>

<script>
function openActivityPanel() {
    fetch("{{ route('activity.panel') }}")
        .then(res => res.text())
        .then(html => {
            document.getElementById('right-panel').innerHTML = html;
            document.getElementById('right-panel').classList.add('open');
        });
}
</script>

@endsection
