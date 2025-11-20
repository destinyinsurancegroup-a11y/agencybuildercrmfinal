<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index()
    {
        // Load only contacts marked as "lead"
        $leads = Contact::where('contact_type', 'lead')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('leads.index', compact('leads'));
    }

    public function show($id)
    {
        $contact = Contact::findOrFail($id);

        return view('leads.partials.details', compact('contact'));
    }

    public function create()
    {
        // Loads the create form into the right panel
        return view('leads.partials.create');
    }
}
