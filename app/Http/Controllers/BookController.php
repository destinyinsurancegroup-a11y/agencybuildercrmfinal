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

        $selected = request('selected');

        return view('book.index', compact('clients', 'selected'));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PANEL (right side)
    |--------------------------------------------------------------------------
    */
    public function createPanel()
    {
        return view('book.partials.create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE — SAME FLOW AS CONTACTS
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:255',
            'address'    => 'nullable|string|max:500',
        ]);

        $validated['contact_type'] = 'book';
        $validated['full_name'] = trim($validated['first_name'].' '.$validated['last_name']);

        $client = Contact::create($validated);

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW — LOAD RIGHT PANEL
    |--------------------------------------------------------------------------
    */
    public function show(Contact $client)
    {
        if (request()->ajax()) {
            return view('book.partials.client-file', compact('client'));
        }

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT — MATCHES CONTACTS EDIT FLOW
    |--------------------------------------------------------------------------
    */
    public function edit(Contact $client)
    {
        return view('book.partials.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE — SAME AS CONTACTS UPDATE
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Contact $client)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:255',
            'address'    => 'nullable|string|max:500',
        ]);

        $validated['full_name'] = trim($validated['first_name'].' '.$validated['last_name']);

        $client->update($validated);

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTES — ADD
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
    | NOTES — UPDATE
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
    | IMPORT (future)
    |--------------------------------------------------------------------------
    */
    public function import(Request $request)
    {
        return back()->with('message', 'Import not implemented yet.');
    }
}
