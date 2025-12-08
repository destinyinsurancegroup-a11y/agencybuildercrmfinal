<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use App\Models\ContactRelation;   // Unified relations table
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX – LEFT LIST + RIGHT PANEL
    |--------------------------------------------------------------------------
    |
    | Shows the Book of Business list for the CURRENT AGENCY only.
    | Contact model is TenantScoped, so all queries are automatically filtered
    | by agency_id for the logged-in user.
    */
    public function index(Request $request)
    {
        $query = Contact::query()
            // Show:
            //  - anything explicitly in Book of Business
            //  - OR legacy book/client/Sold records
            ->where(function ($q) {
                $q->where('in_book_of_business', true)
                  ->orWhere(function ($q2) {
                      $q2->where('contact_type', 'book')
                         ->orWhere('contact_type', 'client')
                         ->orWhere('status', 'Sold');
                  });
            });

        // Optional search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // ORDER:
        // 1) Urgent service contacts first:
        //      contact_type = 'service' AND service_archived_at IS NULL
        // 2) Then by last name / first name
        $query->orderByRaw("
            CASE
                WHEN contact_type = 'service' AND service_archived_at IS NULL THEN 0
                ELSE 1
            END
        ")
        ->orderBy('last_name')
        ->orderBy('first_name');

        $clients  = $query->get();
        $selected = $request->get('selected');

        return view('book.index', compact('clients', 'selected'));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PANEL (AJAX)
    |--------------------------------------------------------------------------
    */
    public function createPanel()
    {
        return view('book.partials.create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE – NEW BOOK CLIENT
    |--------------------------------------------------------------------------
    |
    | New Book clients are:
    |  - tagged contact_type = 'book'
    |  - explicitly flagged in_book_of_business = true
    |  - scoped to the current agency via TenantScoped on Contact
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:255',

            'address_line1'     => 'nullable|string|max:255',
            'address_line2'     => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:255',
            'postal_code'       => 'nullable|string|max:50',

            'date_of_birth'     => 'nullable|date',
            'anniversary'       => 'nullable|date',

            'carrier'           => 'nullable|string|max:255',
            'policy_type'       => 'nullable|string|max:255',
            'face_amount'       => 'nullable|numeric',
            'premium_amount'    => 'nullable|numeric',
            'premium_due_date'  => 'nullable|date',
            'policy_issue_date' => 'nullable|date',
            'premium_due_text'  => 'nullable|string|max:255',

            'notes'             => 'nullable|string',
        ]);

        $user = Auth::user();

        // New records created from Book are tagged as "book"
        $validated['contact_type'] = 'book';
        $validated['created_by']   = $user?->id;

        // agency_id is set automatically by TenantScoped::creating()
        $client = Contact::create($validated);

        // Explicitly mark as in Book of Business
        $client->in_book_of_business = true;
        $client->save();

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW PANEL (AJAX ONLY)
    |--------------------------------------------------------------------------
    */
    public function show(Contact $client)
    {
        if (request()->ajax()) {
            return view('book.partials.details', compact('client'));
        }

        return abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT PANEL (AJAX)
    |--------------------------------------------------------------------------
    */
    public function editPanel(Contact $client)
    {
        return view('book.partials.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | FULL PAGE EDIT
    |--------------------------------------------------------------------------
    */
    public function edit(Contact $client)
    {
        return view('book.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE – SAVES EVERYTHING INCLUDING DESTINY RELATIONS
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Contact $client)
    {
        // 1. Validate base fields
        $validated = $request->validate([
            'first_name'        => 'nullable|string|max:255',
            'last_name'         => 'nullable|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:255',

            'address_line1'     => 'nullable|string|max:255',
            'address_line2'     => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:255',
            'postal_code'       => 'nullable|string|max:50',

            'date_of_birth'     => 'nullable|date',
            'anniversary'       => 'nullable|date',

            'carrier'           => 'nullable|string|max:255',
            'policy_type'       => 'nullable|string|max:255',
            'face_amount'       => 'nullable|numeric',
            'premium_amount'    => 'nullable|numeric',
            'premium_due_date'  => 'nullable|date',
            'policy_issue_date' => 'nullable|date',
            'premium_due_text'  => 'nullable|string|max:255',
        ]);

        // 2. Partial update – only overwrite non-empty values
        foreach ($validated as $key => $value) {
            if ($value !== null && $value !== '') {
                $client->{$key} = $value;
            }
        }

        // Ensure it's treated as a Book record after editing here
        $client->contact_type        = 'book';
        $client->in_book_of_business = true;
        $client->save();

        /*
        |--------------------------------------------------------------------------
        | DESTINY: BENEFICIARIES & EMERGENCY CONTACTS
        |--------------------------------------------------------------------------
        */
        $this->saveRelations($request, $client, 'beneficiary');
        $this->saveRelations($request, $client, 'emergency');

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE RELATIONS (Unified Destiny Logic)
    |--------------------------------------------------------------------------
    |
    | Uses ContactRelation model, which is TenantScoped. We set agency_id to the
    | same agency as the client and created_by to the current user.
    */
    private function saveRelations(Request $request, Contact $client, string $type)
    {
        $key  = $type === 'beneficiary' ? 'beneficiaries' : 'emergency_contacts';
        $user = Auth::user();

        if (!$request->has($key)) {
            return;
        }

        foreach ($request->$key as $row) {
            if (!isset($row['name']) || trim($row['name']) === '') {
                continue;
            }

            // Update existing
            if (!empty($row['id'])) {
                $relation = ContactRelation::where('id', $row['id'])
                    ->where('contact_id', $client->id)
                    ->where('type', $type)
                    ->first();

                if ($relation) {
                    $relation->update([
                        'name'         => $row['name'],
                        'relationship' => $row['relationship'] ?? null,
                        'phone'        => $row['phone'] ?? null,
                        'contacted'    => $row['contacted'] ?? 0,
                    ]);
                }
                continue;
            }

            // Create new
            ContactRelation::create([
                'contact_id'   => $client->id,
                'type'         => $type,
                'name'         => $row['name'],
                'relationship' => $row['relationship'] ?? null,
                'phone'        => $row['phone'] ?? null,
                'contacted'    => $row['contacted'] ?? 0,
                // legacy tenant_id kept for compatibility, but no hard-coded 1
                'tenant_id'    => $client->tenant_id ?? $user?->tenant_id,
                'created_by'   => $user?->id ?? $client->created_by,
                'agency_id'    => $client->agency_id,  // multi-tenant scoping
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NOTES – ADD (BOOK + SERVICE + LEADS)
    |--------------------------------------------------------------------------
    |
    | Used by:
    |   POST /book/{client}/notes
    |   POST /service/{client}/notes
    |   POST /leads/{client}/notes
    |
    | Accepts either "body" or "note" from the request so old JS and new JS
    | both work.
    */
    public function storeNote(Request $request, Contact $client)
    {
        // Accept either "body" or "note"
        $text = trim(
            $request->input('body', $request->input('note', ''))
        );

        if ($text === '') {
            return response()->json([
                'success' => false,
                'message' => 'Note body is required.',
            ], 422);
        }

        $user     = Auth::user();
        $tenantId = $client->tenant_id ?? $user?->tenant_id;

        $note = Note::create([
            'contact_id' => $client->id,
            'note'       => $text, // DB column is "note"
            'created_by' => $user?->id ?? $client->created_by,
            'tenant_id'  => $tenantId,
        ]);

        return response()->json([
            'success' => true,
            'note'    => [
                'id'                    => $note->id,
                'contact_id'            => $note->contact_id,
                'note'                  => $note->note,
                'created_at'            => $note->created_at,
                'created_at_formatted'  => optional($note->created_at)->format('m/d/Y g:i A'),
            ],
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTES – UPDATE (BOOK + SERVICE + LEADS)
    |--------------------------------------------------------------------------
    |
    | Used by:
    |   PUT /book/{client}/notes/{note}
    |   PUT /service/{client}/notes/{note}
    |   PUT /leads/{client}/notes/{note}
    |
    | Accepts either "body" or "note".
    */
    public function updateNote(Request $request, Contact $client, Note $note)
    {
        // Ensure the note actually belongs to this client
        if ($note->contact_id !== $client->id) {
            abort(404);
        }

        $text = trim(
            $request->input('body', $request->input('note', ''))
        );

        if ($text === '') {
            return response()->json([
                'success' => false,
                'message' => 'Note body is required.',
            ], 422);
        }

        $note->update([
            'note' => $text,
        ]);

        $fresh = $note->fresh();

        return response()->json([
            'success' => true,
            'note'    => [
                'id'                    => $fresh->id,
                'contact_id'            => $fresh->contact_id,
                'note'                  => $fresh->note,
                'created_at'            => $fresh->created_at,
                'created_at_formatted'  => optional($fresh->created_at)->format('m/d/Y g:i A'),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTES – DELETE (BOOK + SERVICE + LEADS)
    |--------------------------------------------------------------------------
    |
    | Used by:
    |   DELETE /book/{client}/notes/{note}
    |   DELETE /service/{client}/notes/{note}
    |   DELETE /leads/{client}/notes/{note}
    */
    public function destroyNote(Contact $client, Note $note)
    {
        if ($note->contact_id !== $client->id) {
            abort(404);
        }

        $note->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SEND TO SERVICE – REUSE SAME CONTACT FOR SERVICE WORK
    |--------------------------------------------------------------------------
    */
    public function sendToService(Contact $client)
    {
        $user = Auth::user();

        // Extra safety, though TenantScoped + route-model binding already protect this
        if ($user && $client->agency_id !== $user->agency_id) {
            abort(403, 'Unauthorized');
        }

        $client->contact_type        = 'service';
        $client->in_book_of_business = true;
        $client->service_status      = null;
        $client->service_archived_at = null;
        $client->save();

        // Jump to Service tab with this client selected
        return redirect()->route('service.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE RELATIONS (Destiny Style)
    |--------------------------------------------------------------------------
    */
    public function deleteRelation(Request $request, Contact $client, ContactRelation $relation)
    {
        if ($relation->contact_id !== $client->id) {
            abort(403);
        }

        $relation->delete();

        return response()->json(['success' => true]);
    }
}
