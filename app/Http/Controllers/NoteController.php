<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    /**
     * Full notes page for a contact (rarely used – safe to keep).
     */
    public function index(Contact $contact)
    {
        // Load notes newest first
        $notes = $contact->notes()->latest()->get();

        return view('contacts.notes.index', compact('contact', 'notes'));
    }

    /**
     * Store a new note for a contact.
     *
     * Used by:
     *   POST /contacts/{contact}/notes  (name: contacts.notes.store)
     *
     * IMPORTANT:
     *  - Ensure tenant_id is NEVER null (same pattern as BookController::storeNote).
     *  - Support both normal form POST (redirect) and AJAX (JSON).
     */
    public function store(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $user = Auth::user();

        // 🔐 Make sure tenant_id is never null
        $tenantId = $contact->tenant_id
            ?? ($user?->tenant_id ?? 1);

        $note = Note::create([
            'contact_id' => $contact->id,
            // Actual DB column is "note"
            'note'       => trim($validated['body']),
            // fallback to contact->created_by if no user (very unlikely)
            'created_by' => $user?->id ?? $contact->created_by,
            'tenant_id'  => $tenantId,
        ]);

        // If this was an AJAX request (expects JSON), return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'note'    => [
                    'id'                   => $note->id,
                    'contact_id'           => $note->contact_id,
                    'note'                 => $note->note,
                    'created_at'           => $note->created_at,
                    'created_at_formatted' => optional($note->created_at)->format('m/d/Y g:i A'),
                ],
            ], 201);
        }

        // Default: normal form POST → redirect back to contact details
        return back()->with('success', 'Note added successfully.');
    }

    /**
     * Return just the notes list partial for a contact (used by JS refresh).
     *
     * GET /contacts/{contact}/notes/list  (name: contacts.notes.list)
     */
    public function list(Contact $contact)
    {
        $notes = $contact->notes()->latest()->get();

        return view('contacts.notes.list', compact('contact', 'notes'));
    }
}
