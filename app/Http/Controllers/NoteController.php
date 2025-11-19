<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Store a new note for a contact (AJAX)
     */
    public function store(Request $request, $contactId)
    {
        $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        // Ensure user only accesses contacts in their tenant
        $contact = Contact::forCurrentTenant()->findOrFail($contactId);

        // Create the note
        Note::create([
            'contact_id' => $contact->id,
            'note'       => $request->note,
            // tenant_id and created_by assigned automatically in Note::booted()
        ]);

        // Reload updated notes list HTML
        $html = view('contacts.partials._notes_list', [
            'contact' => $contact->fresh('notes')
        ])->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Load notes tab (for when user clicks "Notes")
     */
    public function index($contactId)
    {
        $contact = Contact::forCurrentTenant()->with('notes')->findOrFail($contactId);

        return view('contacts.partials.notes', compact('contact'));
    }

    /**
     * Return notes list partial (used after saving a new note)
     */
    public function list($contactId)
    {
        $contact = Contact::forCurrentTenant()->with('notes')->findOrFail($contactId);

        return view('contacts.partials._notes_list', compact('contact'));
    }
}
