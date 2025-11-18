@extends('layouts.app')

@section('content')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-gold fw-bold m-0">All Contacts</h1>

        <a href="{{ route('contacts.create') }}" class="btn btn-primary">
            + Add Contact
        </a>
    </div>

    <!-- Contacts Table Container -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <table class="table table-hover mb-0">
                <thead class="bg-dark text-gold">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>

                <tbody class="align-middle">
                    @forelse($contacts as $contact)
                        <tr>
                            <td class="fw-semibold">{{ $contact->full_name }}</td>
                            <td>{{ $contact->email }}</td>
                            <td>{{ $contact->phone }}</td>
                            <td>{{ $contact->contact_type }}</td>
                            <td>{{ $contact->status }}</td>
                            <td>
                                <div class="btn-group">

                                    <a href="{{ route('contacts.show', $contact->id) }}"
                                       class="btn btn-sm btn-outline-dark">
                                        View
                                    </a>

                                    <a href="{{ route('contacts.edit', $contact->id) }}"
                                       class="btn btn-sm btn-outline-dark">
                                        Edit
                                    </a>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No contacts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>

</div>

@endsection
