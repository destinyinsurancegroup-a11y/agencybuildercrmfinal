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
            // DB column is "note"
            'note'       => trim($validated['body']),
            'created_by' => auth()->id(),
            'tenant_id'  => auth()->check() ? auth()->user()->tenant_id : null,
        ]);

        return redirect()
            ->route('contacts.index', ['selected' => $contact->id])
            ->with('status', 'Note added.');
    }

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
