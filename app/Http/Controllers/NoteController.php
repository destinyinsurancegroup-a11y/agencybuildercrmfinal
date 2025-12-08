<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Optional standalone notes page for a contact.
     * (You may or may not be using this in the UI.)
     */
    public function index(Contact $contact)
    {
        $notes = $contact->notes()->latest()->get();

        return view('contacts.notes.index', compact('contact', 'notes'));
    }

    /**
     * Store a new note for a contact from the All Contacts details panel.
     * After saving, redirect back to the All Contacts page with this
     * contact still selected so the card stays open.
     */
    public function store(Request $request, $contactId)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $contact = Contact::findOrFail($contactId);

        // Make sure tenant_id is NEVER null
        $tenantId = $contact->tenant_id
            ?? (auth()->user()->tenant_id ?? 1);

        Note::create([
            'contact_id' => $contact->id,
            // DB column is "note"
            'note'       => trim($validated['body']),
            // fallback to contact->created_by if user is null (just in case)
            'created_by' => auth()->id() ?? $contact->created_by,
            'tenant_id'  => $tenantId,
        ]);

        // 🔑 KEY FIX:
        // Go back to All Contacts with THIS contact auto-selected
        return redirect()
            ->route('contacts.index', ['selected' => $contact->id])
            ->with('success', 'Note added successfully.');
    }

    /**
     * Lightweight JSON list endpoint (if used by any JS in the app).
     */
    public function list(Contact $contact)
    {
        $notes = $contact->notes()
            ->latest()
            ->get()
            ->map(function ($note) {
                return [
                    'id'                   => $note->id,
                    'contact_id'           => $note->contact_id,
                    'note'                 => $note->note ?? $note->body,
                    'created_at'           => $note->created_at,
                    'created_at_formatted' => optional($note->created_at)->format('m/d/Y g:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'notes'   => $notes,
        ]);
    }
}
