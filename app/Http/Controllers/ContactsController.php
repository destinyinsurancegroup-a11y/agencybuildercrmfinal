<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    /**
     * Display the contacts index page (master-detail layout).
     */
    public function index(Request $request)
    {
        $tenantId = 1;

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
            'selected' => null,
        ]);
    }

    /**
     * AJAX contact loader for right panel.
     */
    public function show(Request $request, $id)
    {
        $tenantId = 1;

        $contact = Contact::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        return view('contacts.partials.details', compact('contact'));
    }

    /**
     * AJAX "create contact" panel loader.
     */
    public function createAjax(Request $request)
    {
        return view('contacts.partials.create');
    }

    /**
     * Standalone full-page create (legacy)
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
        $validated = $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
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

        $validated['tenant_id']  = 1;
        $validated['created_by'] = 1;

        $contact = Contact::create($validated);

        return redirect()
            ->route('contacts.show', $contact->id)
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Load your existing working edit partial.
     */
    public function edit(Contact $contact)
    {
        return view('contacts.partials.edit', compact('contact'));
    }

    /**
     * Update contact and return to contact's detail page.
     */
    public function update(Request $request, Contact $contact)
    {
        // Multi-tenant safety
        $tenantId = 1;
        if ($contact->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized tenant access.');
        }

        // Validation
        $validated = $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
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

        // Update contact
        $contact->update($validated);

        // ✔ Return to the contact's detail page
        return redirect()
            ->route('contacts.show', $contact->id)
            ->with('success', 'Contact updated successfully.');
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
     * CSV/Excel Import placeholder.
     */
    public function import(Request $request)
    {
        return back()->with('success', 'Import placeholder working.');
    }
}
