<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Note;

class BookController extends Controller
{
    /**
     * INDEX — loads the two-panel page.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $clients = Client::when($search, function ($query, $search) {
            $query->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
        })
        ->orderBy('last_name')
        ->get();

        return view('book.index', [
            'clients'  => $clients,
            'selected' => $request->selected ?? null
        ]);
    }



    /**
     * CREATE PANEL — loads inside right pane via AJAX.
     */
    public function createPanel()
    {
        return view('book.partials.create');
    }



    /**
     * STORE — saves a new client.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'email'             => 'nullable|string|max:255',
            'phone'             => 'nullable|string|max:255',

            // address
            'address_line1'     => 'nullable|string|max:255',
            'address_line2'     => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:50',
            'postal_code'       => 'nullable|string|max:50',

            // dates
            'date_of_birth'        => 'nullable|date',
            'anniversary'          => 'nullable|date',

            // policy info
            'policy_type'          => 'nullable|string|max:255',
            'face_amount'          => 'nullable|string|max:255',
            'premium_amount'       => 'nullable|string|max:255',
            'recurring_due_date'   => 'nullable|date',
            'policy_issue_date'    => 'nullable|date',
        ]);

        $client = Client::create($data);

        return redirect()
            ->route('book.index', ['selected' => $client->id]);
    }



    /**
     * SHOW — loads client file into the right panel.
     */
    public function show(Client $client)
    {
        if (request()->ajax()) {
            return view('book.partials.client-file', compact('client'));
        }

        return redirect()->route('book.index', ['selected' => $client->id]);
    }



    /**
     * EDIT — loads form inside right AJAX panel.
     */
    public function edit(Client $client)
    {
        return view('book.partials.edit', compact('client'));
    }



    /**
     * UPDATE — saves client changes.
     */
    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'email'             => 'nullable|string|max:255',
            'phone'             => 'nullable|string|max:255',

            // address
            'address_line1'     => 'nullable|string|max:255',
            'address_line2'     => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:50',
            'postal_code'       => 'nullable|string|max:50',

            // dates
            'date_of_birth'        => 'nullable|date',
            'anniversary'          => 'nullable|date',

            // policy info
            'policy_type'          => 'nullable|string|max:255',
            'face_amount'          => 'nullable|string|max:255',
            'premium_amount'       => 'nullable|string|max:255',
            'recurring_due_date'   => 'nullable|date',
            'policy_issue_date'    => 'nullable|date',
        ]);

        $client->update($data);

        return redirect()
            ->route('book.index', ['selected' => $client->id]);
    }



    /**
     * NOTES — STORE NOTE (AJAX).
     */
    public function storeNote(Request $request, Client $client)
    {
        $request->validate([
            'body' => 'required|string'
        ]);

        $note = new Note();
        $note->body = $request->body;
        $note->client_id = $client->id;
        $note->save();

        return response()->json(['success' => true]);
    }



    /**
     * NOTES — UPDATE NOTE (AJAX).
     */
    public function updateNote(Request $request, Client $client, Note $note)
    {
        $request->validate([
            'body' => 'required|string'
        ]);

        $note->update([
            'body' => $request->body
        ]);

        return response()->json(['success' => true]);
    }



    /**
     * IMPORT — placeholder
     */
    public function import(Request $request)
    {
        return back()->with('success', 'Import feature coming soon.');
    }
}
