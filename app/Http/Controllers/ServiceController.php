<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $serviceCases = Contact::where('contact_type', 'service')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('service.index', compact('serviceCases'));
    }

    public function show($id)
    {
        $contact = Contact::findOrFail($id);

        return view('service.partials.details', compact('contact'));
    }

    public function create()
    {
        return view('service.partials.create');
    }
}
