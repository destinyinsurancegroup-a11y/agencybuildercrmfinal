<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    /**
     * Display the contacts index page (master-detail layout).
     * Shows the contacts list in the left panel.
     * Optionally loads a selected contact in the right panel.
     */
    public function index(Request $request)
    {
        $contacts = Contact::orderBy('last_name')
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
     * Display selected contact inside the master-detail layout.
     * Reuses the same index page but passes a selected contact.
     */
    public function show(Contact $contact, Request $request)
    {
        $contacts = Contact::orderBy('last_name')->get();

        return view('contacts.index', [
            'contacts' => $contacts,
            'selected' => $contact
        ]);
    }

    /**
     * Show standalone create form.
     */
    public function create()
    {
        return view('contacts.create');
    }

    /**
     * Store a newly created contact (Minimal Validation — Option A).
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

        // Temporary static values until authentication & tenancy are added
        $validated['tenant_id']  = 1;
        $validated['created_by'] = 1;

        // Create the contact
        $contact = Contact::create($validated);

        // Redirect to master-detail view with selected contact
        return redirect()
            ->route('contacts.show', $contact->id)
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
     * Update selected contact (will be implemented after UI is built).
     */
    public function update(Request $request, Contact $contact)
    {
        return back()->with('success', 'Update placeholder.');
    }

    /**
     * Delete a contact.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();
        return redirect()->route('contacts.index')->with('success', 'Contact deleted.');
    }

    /**
     * Bulk CSV/Excel Import Handler (placeholder for now).
     */
    public function import(Request $request)
    {
        return back()->with('success', 'Import placeholder working.');
    }
}
