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

        $selected = request('selected'); // for auto-opening panel

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
    | STORE NEW CLIENT (FORM SUBMISSION)
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

        // MUST be book type
        $validated['contact_type'] = 'book';

        // Save the client
        $client = Contact::create($validated);

        // Redirect back to Book index and auto-select new client
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

        // No direct navigation allowed
        return abort(404);
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
