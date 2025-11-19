<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    /**
     * Display the contacts index page (master-detail layout).
     * Shows the contacts list in the left panel.
     * Right panel stays empty until a contact is clicked.
     */
    public function index(Request $request)
    {
        $tenantId = 1; // Temporary tenant binding

        $contacts = Contact::where('tenant_id', $tenantId)
            ->orderBy('last_name')
            ->when($request->search, function ($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            })
            ->get();

        return view('contacts.index', [
            'contacts' => $contacts,
            'selected' => null,   // No contact selected yet
        ]);
    }

    /**
     * AJAX contact loader for right panel.
     * Always returns ONLY the partial that goes in the right pane.
     * Route: GET /contacts/{id}
     */
    public function show(Request $request, $id)
    {
        $tenantId = 1; // Temporary tenant binding

        // Tenant-safe lookup
        $contact = Contact::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        // Always return ONLY the right-side panel partial
        return view('contacts.partials.details', compact('contact'));
    }

    /**
     * AJAX "create contact" panel loader.
     * Returns ONLY the empty create form to display in the right pane.
     * Route: GET /contacts-create-panel  (name: contacts.create.panel)
     */
    public function createAjax(Request $request)
    {
        // This will be rendered into the right-side panel via fetch()
        return view('contacts.partials.create');
    }

    /**
     * Show standalone create form (legacy/full-page).
     * Route: GET /contacts/create  (resource route)
     */
    public function create()
    {
        return view('contacts.create');
    }

    /**
     * Store a newly created contact.
     */
    public function store(Request $request)
    {
        // Minimal required validation
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',

            // Optional fields
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'contact_type'   => 'nullable|string|max:50',
            'status'         => 'nullable|string|max:50',
            'source'         => 'nullable|string|max:100',
            'address_line1'  => 'nullable|string|max:255',
            'address_line2'  => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:50',
            'postal_code'    => 'nullable|string|max:20',
            'notes'          => 'nullable|string',
        ]);

        // Temporary tenant & creator values
        $validated['tenant_id']  = 1;
        $validated['created_by'] = 1;

        // Create contact
        $contact = Contact::create($validated);

        // For now, redirect back to the main contacts page.
        // (In a later step, we'll wire this to keep the right panel populated via AJAX.)
        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(Contact $contact)
    {
        return view('contacts.edit', compact('contact'));
    }

    /**
     * Update selected contact.
     */
    public function update(Request $request, Contact $contact)
    {
        // Placeholder until we build the full update flow
        return back()->with('success', 'Update placeholder.');
    }

    /**
     * Delete a contact.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contact deleted.');
    }

    /**
     * Bulk CSV/Excel Import Handler (placeholder for now).
     */
    public function import(Request $request)
    {
        // Placeholder until we implement real import logic
        return back()->with('success', 'Import placeholder working.');
    }
}
