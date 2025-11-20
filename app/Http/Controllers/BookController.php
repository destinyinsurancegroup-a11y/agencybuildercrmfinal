<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index()
    {
        $clients = Contact::where('contact_type', 'book')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('book.index', compact('clients'));
    }

    public function show($id)
    {
        $contact = Contact::findOrFail($id);

        return view('book.partials.details', compact('contact'));
    }

    public function create()
    {
        return view('book.partials.create');
    }
}
