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

        // Find the contact; TenantScoped on Contact will handle agency scoping.
        $contact = Contact::with('notes')->findOrFail($contactId);

        // Create the note. Note model has: contact_id, created_by, tenant_id, body
        Note::create([
            'contact_id' => $contact->id,
            'body'       => $validated['body'],
            'created_by' => auth()->id(),
            // keep legacy tenant_id behavior for now
            'tenant_id'  => auth()->user()->tenant_id ?? null,
        ]);

        // Reload updated notes list HTML
        $html = view('contacts.partials._notes_list', [
            'contact' => $contact->fresh('notes'),
        ])->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Load notes tab (for when user clicks "Notes").
     */
    public function index($contactId)
    {
        // TenantScoped on Contact ensures proper scoping.
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
