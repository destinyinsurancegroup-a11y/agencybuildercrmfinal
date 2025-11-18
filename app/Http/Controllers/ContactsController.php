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
     * Show standalone create form (we will later convert to modal).
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
        // Real implementation coming in next steps
        return back()->with('success', 'Contact creation placeholder.');
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
        // Real update logic coming soon
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
        // Actual CSV/Excel processing will be implemented after UI setup
        return back()->with('success', 'Import placeholder working.');
    }
}
