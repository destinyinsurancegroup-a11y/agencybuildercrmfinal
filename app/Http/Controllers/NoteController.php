<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct()
    {
        // Ensure only logged-in users can hit these endpoints
        $this->middleware('auth');
    }

    /**
     * Store a new note for a contact (AJAX).
     */
    public function store(Request $request, $contactId)
    {
        // Validate the note body (matches Note::$fillable 'body')
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        // Find the contact. Tenant/agency scoping is handled by model scopes/middleware.
        $contact = Contact::with('notes')->findOrFail($contactId);

        // Create the note. Note model still uses tenant_id + created_by for now.
        $note = Note::create([
            'contact_id' => $contact->id,
            'body'       => $validated['body'],
            'created_by' => auth()->id(),
            'tenant_id'  => auth()->user()->tenant_id ?? null, // legacy multi-tenant
        ]);

        // Reload updated notes list HTML
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
