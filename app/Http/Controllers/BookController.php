<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX – LEFT LIST + RIGHT PANEL
    |--------------------------------------------------------------------------
    | Shows the Book of Business list on the left and an empty right panel.
    | This matches your Contacts and Leads layouts.
    */
    public function index(Request $request)
    {
        $query = Contact::where('contact_type', 'book');

        // Optional search
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

        // used by JS to auto-open a client after save
        $selected = $request->get('selected');

        return view('book.index', compact('clients', 'selected'));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PANEL (AJAX) – RIGHT SIDE FORM
    |--------------------------------------------------------------------------
    */
    public function createPanel()
    {
        return view('book.partials.create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE – SAVE NEW CLIENT (Book of Business)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:255',

            // Address
            'address_line1'     => 'nullable|string|max:255',
            'address_line2'     => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:255',
            'postal_code'       => 'nullable|string|max:50',

            // Policy info
            'policy_type'       => 'nullable|string|max:255',
            'face_amount'       => 'nullable|numeric',
            'premium_amount'    => 'nullable|numeric',
            'recurring_due_date'=> 'nullable|date',
            'policy_issue_date' => 'nullable|date',

            // Client info
            'date_of_birth'     => 'nullable|date',
            'anniversary'       => 'nullable|date',
        ]);

        // Tag this as a Book-of-Business contact
        $validated['contact_type'] = 'book';

        // These columns exist on contacts and are required in your DB
        $validated['tenant_id']  = 1;  // adjust if you later add multi-tenant logic
        $validated['created_by'] = 1;  // adjust if you later use auth()->id()

        // Create the client
        $client = Contact::create($validated);

        // Go back to Book index and auto-select the new client
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
            return view('book.partials.client-file', compact('client'));
        }

        // No direct navigation to /book/{id} in browser
        return abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT – FULL PAGE EDIT SCREEN (LIKE CONTACTS EDIT)
    |--------------------------------------------------------------------------
    */
    public function edit(Contact $client)
    {
        return view('book.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE – SAVE CHANGES FROM EDIT SCREEN
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Contact $client)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:255',

            // Address
            'address_line1'     => 'nullable|string|max:255',
            'address_line2'     => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:255',
            'postal_code'       => 'nullable|string|max:50',

            // Policy info
            'policy_type'       => 'nullable|string|max:255',
            'face_amount'       => 'nullable|numeric',
            'premium_amount'    => 'nullable|numeric',
            'recurring_due_date'=> 'nullable|date',
            'policy_issue_date' => 'nullable|date',

            // Client info
            'date_of_birth'     => 'nullable|date',
            'anniversary'       => 'nullable|date',
        ]);

        // Make sure it stays a book client
        $validated['contact_type'] = 'book';

        $client->update($validated);

        // Back to index, auto-open this client in the panel
        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTES – ADD NOTE (AJAX)
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

        // Used by JS: expects JSON { success: true }
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | NOTES – UPDATE NOTE (AJAX)
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

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT (STUB)
    |--------------------------------------------------------------------------
    */
    public function import(Request $request)
    {
        // You can wire this up to Laravel-Excel later
        return back()->with('message', 'Import not implemented yet.');
    }
}
