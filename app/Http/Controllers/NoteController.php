<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Store a new note for a contact.
     */
    public function store(Request $request, $contactId)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $contact = Contact::findOrFail($contactId);

        Note::create([
            'contact_id' => $contact->id,
            'body'       => trim($validated['body']),
            'created_by' => auth()->id(),
            'tenant_id'  => auth()->check() ? auth()->user()->tenant_id : null,
        ]);

        // After saving, go back to All Contacts with this contact selected
        return redirect()
            ->route('contacts.index', ['selected' => $contact->id])
            ->with('status', 'Note added.');
    }

    /**
     * (Optional) These can stay for future use, but aren't required
     * for the All Contacts flow anymore.
     */
    public function index($contactId)
    {
        $contact = Contact::with('notes')->findOrFail($contactId);

        return view('contacts.partials.notes', compact('contact'));
    }

    public function list($contactId)
    {
        $contact = Contact::with('notes')->findOrFail($contactId);

        return view('contacts.partials._notes_list', compact('contact'));
    }
}
