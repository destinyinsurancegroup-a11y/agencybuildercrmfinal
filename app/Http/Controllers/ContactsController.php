<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    /**
     * Display the contacts index page.
     */
    public function index(Request $request)
    {
        // For now just load the view.
        // Later we can add search, pagination, etc.
        return view('contacts.index');
    }

    /**
     * Show the form for creating a new contact.
     */
    public function create()
    {
        return view('contacts.create');
    }

    /**
     * Store a newly created contact in storage.
     */
    public function store(Request $request)
    {
        // Placeholder — we will implement after your views are ready.
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact)
    {
        return view('contacts.show', compact('contact'));
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(Contact $contact)
    {
        return view('contacts.edit', compact('contact'));
    }

    /**
     * Update the specified contact in storage.
     */
    public function update(Request $request, Contact $contact)
    {
        // Placeholder — we will implement soon.
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Contact $contact)
    {
        // Placeholder — we will implement soon.
    }
}
