<div class="all-contacts-page" style="padding: 30px 40px 40px; background:#f5f5f5; min-height:100vh; font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">

    <style>
        .all-contacts-layout {
            display: grid;
            grid-template-columns: 340px minmax(0, 1fr);
            gap: 24px;
        }

        @media (max-width: 1024px) {
            .all-contacts-layout {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        .contacts-panel {
            background:#050608;
            border-radius:18px;
            padding:18px 18px 14px;
            box-shadow:0 18px 30px -12px rgba(0,0,0,0.55);
            border:1px solid #facc15;
            color:#f9fafb;
            display:flex;
            flex-direction:column;
            max-height: calc(100vh - 120px);
        }

        .contacts-header-row {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:10px;
        }

        .contacts-title {
            font-size:18px;
            font-weight:700;
        }

        .contacts-count {
            font-size:13px;
            color:#e5e7eb;
        }

        .contacts-search {
            margin-bottom:10px;
        }

        .contacts-search input {
            width:100%;
            border-radius:999px;
            border:1px solid #facc15;
            background:#020617;
            color:#f9fafb;
            padding:8px 12px;
            font-size:14px;
        }

        .contacts-list {
            margin-top:8px;
            overflow-y:auto;
            padding-right:4px;
        }

        .contact-item {
            padding:8px 10px;
            border-radius:999px;
            margin-bottom:4px;
            display:flex;
            flex-direction:column;
            cursor:pointer;
            background:#111827;
            border:1px solid transparent;
            transition:background 0.15s ease, border-color 0.15s ease, transform 0.05s ease;
        }

        .contact-item:hover {
            background:#1f2937;
        }

        .contact-item.active {
            border-color:#facc15;
            background:#1f2937;
            transform:translateY(-1px);
        }

        .contact-item-name {
            font-size:14px;
            font-weight:600;
            color:#f9fafb;
        }

        .contact-item-meta {
            font-size:12px;
            color:#d1d5db;
        }

        .contact-detail-card {
            background:#ffffff;
            border-radius:18px;
            padding:24px 24px 28px;
            box-shadow:0 18px 30px -12px rgba(0,0,0,0.30);
            border:1px solid #e5e7eb;
        }

        .contact-detail-header {
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            margin-bottom:18px;
        }

        .contact-detail-name {
            font-size:24px;
            font-weight:700;
            color:#111827;
            margin-bottom:4px;
        }

        .contact-detail-underline {
            width:60px;
            height:3px;
            border-radius:999px;
            background:#facc15;
        }

        .contact-detail-actions button {
            padding:8px 14px;
            border-radius:999px;
            border:none;
            font-size:14px;
            font-weight:600;
            cursor:pointer;
        }

        .btn-edit {
            background:#facc15;
            color:#111827;
            margin-right:8px;
        }

        .btn-back {
            background:#9ca3af;
            color:#111827;
        }

        .contact-detail-grid {
            display:grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap:12px 16px;
            margin-bottom:20px;
        }

        @media (max-width: 1024px) {
            .contact-detail-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .contact-detail-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        .detail-field {
            background:#f9fafb;
            border-radius:12px;
            padding:10px 12px;
            border:1px solid #e5e7eb;
        }

        .detail-label {
            font-size:12px;
            color:#6b7280;
            margin-bottom:2px;
        }

        .detail-value {
            font-size:14px;
            font-weight:500;
            color:#111827;
        }

        .notes-card {
            margin-top:18px;
            background:#ffffff;
            border-radius:18px;
            padding:18px 20px 22px;
            border:1px solid #e5e7eb;
        }

        .notes-title-row {
            display:flex;
            align-items:center;
            gap:8px;
            margin-bottom:10px;
        }

        .notes-title {
            font-size:16px;
            font-weight:600;
            color:#111827;
        }

        .notes-textarea {
            width:100%;
            min-height:80px;
            border-radius:12px;
            border:1px solid #d1d5db;
            padding:8px 10px;
            font-size:14px;
            resize:vertical;
        }

        .btn-save-note {
            margin-top:8px;
            padding:8px 14px;
            border-radius:999px;
            border:none;
            font-size:14px;
            font-weight:600;
            cursor:pointer;
            background:#facc15;
            color:#111827;
        }

        .notes-empty {
            margin-top:8px;
            font-size:13px;
            color:#9ca3af;
        }

        .contacts-pagination {
            margin-top:8px;
            font-size:12px;
            color:#e5e7eb;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }
    </style>

    <div class="all-contacts-layout">
        {{-- LEFT: CONTACTS LIST --}}
        <div class="contacts-panel">
            <div class="contacts-header-row">
                <div class="contacts-title">All Contacts</div>
                <div class="contacts-count">
                    Showing {{ $contacts->firstItem() ?? 0 }}–{{ $contacts->lastItem() ?? 0 }}
                    of {{ $contacts->total() }}
                </div>
            </div>

            <div class="contacts-search">
                <input
                    type="text"
                    placeholder="Search by name, email, or phone..."
                    wire:model.debounce.300ms="search"
                />
            </div>

            <div class="contacts-list">
                @forelse ($contacts as $contact)
                    <div
                        class="contact-item {{ $selectedContactId === $contact->id ? 'active' : '' }}"
                        wire:click="selectContact({{ $contact->id }})"
                    >
                        <div class="contact-item-name">
                            {{ $contact->full_name }}
                        </div>
                        <div class="contact-item-meta">
                            @if($contact->contact_type)
                                {{ ucfirst($contact->contact_type) }}
                            @endif
                            @if($contact->phone)
                                • {{ $contact->phone }}
                            @elseif($contact->email)
                                • {{ $contact->email }}
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="font-size:13px; color:#e5e7eb; margin-top:8px;">
                        No contacts found for this search.
                    </div>
                @endforelse
            </div>

            <div class="contacts-pagination">
                <div>
                    {{-- Simple text; you can replace with $contacts->links() if desired --}}
                    Page {{ $contacts->currentPage() }} of {{ $contacts->lastPage() }}
                </div>
                <div>
                    @if($contacts->onFirstPage() === false)
                        <button wire:click="previousPage" style="border:none; background:#111827; color:#facc15; border-radius:999px; padding:4px 10px;cursor:pointer;">◀</button>
                    @endif
                    @if($contacts->hasMorePages())
                        <button wire:click="nextPage" style="border:none; background:#111827; color:#facc15; border-radius:999px; padding:4px 10px;cursor:pointer;">▶</button>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT: CONTACT DETAILS --}}
        <div>
            @php
                $contact = $this->selectedContact;
            @endphp

            @if($contact)
                <div class="contact-detail-card">
                    <div class="contact-detail-header">
                        <div>
                            <div class="contact-detail-name">{{ $contact->full_name }}</div>
                            <div class="contact-detail-underline"></div>
                        </div>
                        <div class="contact-detail-actions">
                            <button class="btn-edit" type="button">
                                Edit
                            </button>
                            <button class="btn-back" type="button">
                                Back
                            </button>
                        </div>
                    </div>

                    <div class="contact-detail-grid">
                        <div class="detail-field">
                            <div class="detail-label">Name</div>
                            <div class="detail-value">{{ $contact->full_name }}</div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value">{{ $contact->phone ?? '—' }}</div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">{{ $contact->email ?? '—' }}</div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-label">Address</div>
                            <div class="detail-value">
                                @if($contact->address_line1)
                                    {{ $contact->address_line1 }}
                                    @if($contact->city || $contact->state || $contact->postal_code)
                                        , {{ $contact->city }} {{ $contact->state }} {{ $contact->postal_code }}
                                    @endif
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-label">Date of Birth</div>
                            <div class="detail-value">
                                {{ optional($contact->date_of_birth)->format('m/d/Y') ?? '—' }}
                            </div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-label">Policy Type</div>
                            <div class="detail-value">{{ $contact->policy_type ?? '—' }}</div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-label">Face Amount</div>
                            <div class="detail-value">
                                {{ $contact->face_amount ? '$' . number_format($contact->face_amount, 2) : '—' }}
                            </div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-label">Premium Amount</div>
                            <div class="detail-value">
                                {{ $contact->premium_amount ? '$' . number_format($contact->premium_amount, 2) : '—' }}
                            </div>
                        </div>
                        <div class="detail-field">
                            <div class="detail-label">Premium Due Date</div>
                            <div class="detail-value">
                                {{ optional($contact->premium_due_date)->format('m/d/Y') ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <div class="notes-card">
                        <div class="notes-title-row">
                            <div class="notes-title">Notes</div>
                        </div>
                        <textarea class="notes-textarea" placeholder="Add a note about {{ $contact->full_name }}..."></textarea>
                        <button class="btn-save-note" type="button">Save Note</button>
                        <div class="notes-empty">No notes yet.</div>
                    </div>
                </div>
            @else
                <div class="contact-detail-card">
                    <div style="font-size:14px; color:#6b7280;">
                        No contact selected.
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
