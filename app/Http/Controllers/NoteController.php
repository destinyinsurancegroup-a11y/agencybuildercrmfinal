<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Store a new note for a contact (AJAX).
     */
    public function store(Request $request, $contactId)
    {
        // Validate the note body (matches Note::$fillable 'body')
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        // Find the contact (TenantScoped / agency scoping will be added later; for now simple)
        $contact = Contact::with('notes')->findOrFail($contactId);

        // Create the note. Note model has: tenant_id, contact_id, created_by, body
        $note = Note::create([
            'contact_id' => $contact->id,
            'body'       => $validated['body'],
            'created_by' => auth()->id(), // will be null if not logged in, which is OK for now
            // avoid calling ->tenant_id on null user
            'tenant_id'  => auth()->check() ? auth()->user()->tenant_id : null,
        ]);

        // Reload updated notes list HTML with the new note included
        $contact->load('notes');

        $html = view('contacts.partials._notes_list', [
            'contact' => $contact,
        ])->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Load notes tab (for when user clicks "Notes").
     */
    public function index($contactId)
    {
        $contact = Contact::with('notes')->findOrFail($contactId);

        return view('contacts.partials.notes', compact('contact'));
    }

    /**
     * Return notes list partial (used after saving a new note).
     */
    public function list($contactId)
    {
        $contact = Contact::with('notes')->findOrFail($contactId);

        return view('contacts.partials._notes_list', compact('contact'));
    }
}
