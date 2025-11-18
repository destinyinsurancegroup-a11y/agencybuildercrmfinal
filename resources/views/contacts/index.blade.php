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

    /* Completely remove placeholder spacing */
    .empty-right-panel {
        padding: 0 !important;
        margin: 0 !important;
        height: 100%;
        background: transparent;
        border: none !important;
        box-shadow: none !important;
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

                <!-- Add button ONLY -->
                <div class="d-flex gap-2 mb-3">
                    <a href="{{ route('contacts.create') }}" class="btn btn-sm btn-primary">+ Add</a>
                </div>

                <!-- Contact List -->
                <div>
                    @forelse ($contacts as $contact)
                        <a href="{{ route('contacts.show', $contact->id) }}"
                            class="contact-list-item
                                {{ isset($selected) && $selected && $selected->id === $contact->id ? 'contact-selected' : '' }}">
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
                <!-- Completely empty right panel -->
                <div class="empty-right-panel"></div>
            @endif

        </div>

    </div>
</div>

@endsection
