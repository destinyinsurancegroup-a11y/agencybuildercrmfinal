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
        $query = Contact::where('contact_type', 'book');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%");
            });
        }

        $clients = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $selected = request('selected'); // auto-open after update

        return view('book.index', compact('clients', 'selected'));
    }


    /*
    |--------------------------------------------------------------------------
    | AJAX — CREATE PANEL
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
            'notes'        => 'nullable|string',
        ]);

        $validated['contact_type'] = 'book';

        $client = Contact::create($validated);

        return redirect()->route('book.index', ['selected' => $client->id]);
    }


    /*
    |--------------------------------------------------------------------------
    | AJAX — SHOW CLIENT FILE PANEL
    |--------------------------------------------------------------------------
    */
    public function show(Contact $client)
    {
        if (request()->ajax()) {
            return view('book.partials.client-file', compact('client'));
        }

        abort(404);
    }


    /*
    |--------------------------------------------------------------------------
    | AJAX — EDIT CLIENT PANEL
    |--------------------------------------------------------------------------
    */
    public function editPanel(Contact $client)
    {
        return view('book.partials.edit', compact('client'));
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE CLIENT (PUT)
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Contact $client)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:255',
            'date_of_birth'     => 'nullable|date',
            'anniversary'       => 'nullable|date',
            'address_line1'     => 'nullable|string|max:255',
            'address_line2'     => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:255',
            'postal_code'       => 'nullable|string|max:20',
            'policy_type'       => 'nullable|string|max:255',
            'face_amount'       => 'nullable|string|max:255',
            'premium_amount'    => 'nullable|string|max:255',
            'recurring_due_date'=> 'nullable|date',
            'policy_issue_date' => 'nullable|date',
            'notes'             => 'nullable|string',
        ]);

        // ALWAYS keep type book
        $validated['contact_type'] = 'book';

        $client->update($validated);

        return redirect()->route('book.index', ['selected' => $client->id]);
    }


    /*
    |--------------------------------------------------------------------------
    | NOTES — ADD (AJAX)
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
    | NOTES — UPDATE (AJAX)
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
    | IMPORT (LATER)
    |--------------------------------------------------------------------------
    */
    public function import(Request $request)
    {
        return back()->with('message', 'Import not implemented yet.');
    }
}
