<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * INDEX — same master-detail layout as Contacts & Leads
     */
    public function index(Request $request)
    {
        $tenantId = 1;

        $clients = Contact::where('tenant_id', $tenantId)
            ->where('contact_type', 'book')
            ->orderBy('last_name')
            ->when($request->search, function ($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            })
            ->get();

        return view('book.index', [
            'clients'  => $clients,
            'selected' => $request->selected,
        ]);
    }

    /**
     * AJAX — load right panel details
     */
    public function show($id)
    {
        $tenantId = 1;

        $client = Contact::where('tenant_id', $tenantId)
            ->where('contact_type', 'book')
            ->findOrFail($id);

        return view('book.partials.details', compact('client'));
    }

    /**
     * AJAX — create panel
     */
    public function createAjax()
    {
        return view('book.partials.create');
    }

    /**
     * STORE new Book of Business client
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:255',

            // Optional shared fields
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:50',
            'postal_code'   => 'nullable|string|max:20',
        ]);

        $validated['tenant_id']    = 1;
        $validated['created_by']   = 1;
        $validated['contact_type'] = 'book';

        $client = Contact::create($validated);

        return redirect()
            ->route('book.index', ['selected' => $client->id])
            ->with('success', 'Book of Business client created.');
    }

    /**
     * AJAX — edit panel
     */
    public function edit(Contact $client)
    {
        return view('book.partials.edit', compact('client'));
    }

    /**
     * UPDATE Book client
     */
    public function update(Request $request, Contact $client)
    {
        // Multi-tenant security
        $tenantId = 1;
        if ($client->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized tenant access.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:255',

            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:50',
            'postal_code'   => 'nullable|string|max:20',
        ]);

        // Force contact_type to remain "book"
        $validated['contact_type'] = 'book';

        $client->update($validated);

        return redirect()
            ->route('book.index', ['selected' => $client->id])
            ->with('success', 'Client updated successfully.');
    }
}
