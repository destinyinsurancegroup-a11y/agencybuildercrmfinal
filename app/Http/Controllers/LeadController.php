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

    /**
     * Convert a LEAD into a CLIENT (Sold button)
     */
    public function markSold(Contact $contact, Request $request)
    {
        // Convert record → Client
        $contact->update([
            'contact_type' => 'client',
            'status'       => 'Sold',
        ]);

        // If request was AJAX
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead converted to client successfully.',
                'contact' => $contact
            ]);
        }

        // Standard redirect fallback
        return redirect()
            ->route('leads.index')
            ->with('success', 'Lead converted to client successfully.');
    }
}
