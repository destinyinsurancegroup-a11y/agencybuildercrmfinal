<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * INDEX — left list + right AJAX panel
     */
    public function index(Request $request)
    {
        $clients = Contact::where('contact_type', 'book')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('book.index', [
            'clients' => $clients,
            'selected' => $request->selected,
        ]);
    }

    /**
     * AJAX: CREATE PANEL
     */
    public function createPanel()
    {
        return view('book.partials.create');
    }

    /**
     * STORE NEW BOOK CLIENT
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:255',
        ]);

        $validated['tenant_id']  = 1;
        $validated['created_by'] = 1;
        $validated['contact_type'] = 'book';

        $client = Contact::create($validated);

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /**
     * AJAX: LOAD CLIENT DETAILS PANEL
     */
    public function show(Contact $client)
    {
        if (request()->ajax()) {
            return view('book.partials.details', compact('client'));
        }

        return abort(404);
    }

    /**
     * AJAX: LOAD EDIT PANEL
     */
    public function editPanel(Contact $client)
    {
        return view('book.partials.edit', compact('client'));
    }

    /**
     * UPDATE BOOK CLIENT
     */
    public function update(Request $request, Contact $client)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:255',
        ]);

        $validated['contact_type'] = 'book';

        $client->update($validated);

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /**
     * NOTES — ADD
     */
    public function storeNote(Request $request, Contact $client)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        Note::create([
            'contact_id' => $client->id,
            'body' => $request->body,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * NOTES — UPDATE
     */
    public function updateNote(Request $request, Contact $client, Note $note)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $note->update(['body' => $request->body]);

        return response()->json(['success' => true]);
    }
}
