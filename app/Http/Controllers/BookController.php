<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use App\Models\ContactRelation;   // <-- UNIFIED relations table
use Illuminate\Http\Request;

class BookController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX – LEFT LIST + RIGHT PANEL
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = Contact::where('contact_type', 'book');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $clients = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

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

        $validated['contact_type'] = 'book';
        $validated['tenant_id']    = 1;
        $validated['created_by']   = 1;

        $client = Contact::create($validated);

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

        // 2. Partial update
        foreach ($validated as $key => $value) {
            if ($value !== null && $value !== '') {
                $client->{$key} = $value;
            }
        }

        $client->contact_type = 'book';
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
    */
    private function saveRelations(Request $request, Contact $client, string $type)
    {
        $key = $type === 'beneficiary'
            ? 'beneficiaries'
            : 'emergency_contacts';

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
                'tenant_id'    => 1,
                'created_by'   => 1,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NOTES – ADD
    |--------------------------------------------------------------------------
    */
    public function storeNote(Request $request, Contact $client)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        Note::create([
            'contact_id' => $client->id,
            'body'       => $request->body,
        ]);

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTES – UPDATE
    |--------------------------------------------------------------------------
    */
    public function updateNote(Request $request, Contact $client, Note $note)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $note->update([
            'body' => $request->body,
        ]);

        return response()->json(['success' => true]);
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
