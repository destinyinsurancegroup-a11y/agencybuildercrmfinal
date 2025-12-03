<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Store a new note for a contact (AJAX or normal POST).
     */
    public function store(Request $request, $contactId)
    {
        // Accept either "body" (new) or "note" (old) from the form
        $body = $request->input('body') ?? $request->input('note');

        if (! $body || ! is_string($body) || trim($body) === '') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Note text is required.',
                ], 422);
            }

            return back()->withErrors(['body' => 'Note text is required.']);
        }

        // Find the contact
        $contact = Contact::with('notes')->findOrFail($contactId);

        // Create the note. Note model has: tenant_id, contact_id, created_by, body
        $note = Note::create([
            'contact_id' => $contact->id,
            'body'       => trim($body),
            'created_by' => auth()->id(), // null if not logged in, that's fine for now
            'tenant_id'  => auth()->check() ? auth()->user()->tenant_id : null,
        ]);

        // Reload updated notes list HTML with the new note included
        $contact->load('notes');

        $html = view('contacts.partials._notes_list', [
            'contact' => $contact,
        ])->render();

        // If this is AJAX (All Contacts), return JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['html' => $html]);
        }

        // Fallback for non-AJAX usage
        return back()->with('status', 'Note added.');
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
