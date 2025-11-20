<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX — LEFT LIST + RIGHT PANEL
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $clients = Contact::where('contact_type', 'book')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $selected = request('selected'); // for auto-select after save/update

        return view('book.index', compact('clients', 'selected'));
    }


    /*
    |--------------------------------------------------------------------------
    | AJAX — LOAD CREATE CLIENT PANEL
    |--------------------------------------------------------------------------
    */
    public function createPanel()
    {
        return view('book.partials.create');
    }


    /*
    |--------------------------------------------------------------------------
    | STORE NEW CLIENT
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:255',
            'address'      => 'nullable|string|max:500',
        ]);

        $validated['contact_type'] = 'book';

        $validated['tenant_id'] = 1;       // REQUIRED
        $validated['created_by'] = 1;      // REQUIRED

        $client = Contact::create($validated);

        return redirect()->route('book.index', ['selected' => $client->id]);
    }


    /*
    |--------------------------------------------------------------------------
    | AJAX — LOAD CLIENT FILE PANEL
    |--------------------------------------------------------------------------
    */
    public function show(Contact $client)
    {
        if (request()->ajax()) {
            return view('book.partials.client-file', compact('client'));
        }

        return abort(404);
    }


    /*
    |--------------------------------------------------------------------------
    | AJAX — LOAD EDIT PANEL
    |--------------------------------------------------------------------------
    */
    public function editPanel(Contact $client)
    {
        return view('book.partials.edit', compact('client'));
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE CLIENT
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Contact $client)
    {
        $validated = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:255',
            'address'      => 'nullable|string|max:500',

            // POLICY FIELDS
            'policy_type'        => 'nullable|string|max:255',
            'face_amount'        => 'nullable|string|max:255',
            'premium_amount'     => 'nullable|string|max:255',
            'recurring_due_date' => 'nullable|date',
            'policy_issue_date'  => 'nullable|date',

            // CLIENT INFO
            'date_of_birth'  => 'nullable|date',
            'anniversary'    => 'nullable|date',
        ]);

        $client->update($validated);

        return redirect()->route('book.index', ['selected' => $client->id]);
    }


    /*
    |--------------------------------------------------------------------------
    | STORE NOTE (AJAX)
    |--------------------------------------------------------------------------
    */
    public function storeNote(Request $request, Contact $client)
    {
        $request->validate([
            'body' => 'required|string'
        ]);

        Note::create([
            'contact_id' => $client->id,
            'body'       => $request->body
        ]);

        return response()->json(['success' => true]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE NOTE (AJAX)
    |--------------------------------------------------------------------------
    */
    public function updateNote(Request $request, Contact $client, Note $note)
    {
        $request->validate([
            'body' => 'required|string'
        ]);

        $note->update(['body' => $request->body]);

        return response()->json(['success' => true]);
    }


    /*
    |--------------------------------------------------------------------------
    | IMPORT (CSV / EXCEL)
    |--------------------------------------------------------------------------
    */
    public function import(Request $request)
    {
        return back()->with('message', 'Import not implemented yet.');
    }
}
