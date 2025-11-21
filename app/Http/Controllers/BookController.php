<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use App\Models\Beneficiary;
use App\Models\EmergencyContact;
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
    | SHOW – LOAD CLIENT FILE INTO RIGHT PANEL (AJAX ONLY)
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
    | EDIT PANEL – AJAX
    |--------------------------------------------------------------------------
    */
    public function editPanel(Contact $client)
    {
        return view('book.partials.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | (OPTIONAL) FULL PAGE EDIT
    |--------------------------------------------------------------------------
    */
    public function edit(Contact $client)
    {
        return view('book.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE – SAVE CHANGES (NEW VERSION)
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Contact $client)
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

        $client->update($validated);

        // Return back to list & reload the edited client panel
        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTES – ADD (AJAX)
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
    | NOTES – UPDATE (AJAX)
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
    | BENEFICIARIES – STORE
    |--------------------------------------------------------------------------
    */
    public function storeBeneficiary(Request $request, Contact $client)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'relationship'=> 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:50',
            'contacted'   => 'nullable|boolean',
        ]);

        $data['contact_id'] = $client->id;
        $data['contacted']  = $data['contacted'] ?? false;

        Beneficiary::create($data);

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | BENEFICIARIES – UPDATE
    |--------------------------------------------------------------------------
    */
    public function updateBeneficiary(Request $request, Contact $client, Beneficiary $beneficiary)
    {
        if ($beneficiary->contact_id !== $client->id) {
            abort(403);
        }

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'relationship'=> 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:50',
            'contacted'   => 'nullable|boolean',
        ]);

        $data['contacted'] = $data['contacted'] ?? false;

        $beneficiary->update($data);

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | BENEFICIARIES – DELETE
    |--------------------------------------------------------------------------
    */
    public function deleteBeneficiary(Request $request, Contact $client, Beneficiary $beneficiary)
    {
        if ($beneficiary->contact_id !== $client->id) {
            abort(403);
        }

        $beneficiary->delete();

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | EMERGENCY CONTACTS – STORE
    |--------------------------------------------------------------------------
    */
    public function storeEmergency(Request $request, Contact $client)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'relationship'=> 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:50',
            'contacted'   => 'nullable|boolean',
        ]);

        $data['contact_id'] = $client->id;
        $data['contacted']  = $data['contacted'] ?? false;

        EmergencyContact::create($data);

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | EMERGENCY CONTACTS – UPDATE
    |--------------------------------------------------------------------------
    */
    public function updateEmergency(Request $request, Contact $client, EmergencyContact $contact)
    {
        if ($contact->contact_id !== $client->id) {
            abort(403);
        }

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'relationship'=> 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:50',
            'contacted'   => 'nullable|boolean',
        ]);

        $data['contacted'] = $data['contacted'] ?? false;

        $contact->update($data);

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | EMERGENCY CONTACTS – DELETE
    |--------------------------------------------------------------------------
    */
    public function deleteEmergency(Request $request, Contact $client, EmergencyContact $contact)
    {
        if ($contact->contact_id !== $client->id) {
            abort(403);
        }

        $contact->delete();

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT – (NOT IMPLEMENTED YET)
    |--------------------------------------------------------------------------
    */
    public function import(Request $request)
    {
        return back()->with('message', 'Import not implemented yet.');
    }
}
