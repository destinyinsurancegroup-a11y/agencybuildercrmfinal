<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactsController extends Controller
{
    /**
     * Display the contacts index page (master-detail layout).
     * NOTE: Leads (contact_type = 'lead') and Service cases (contact_type = 'service')
     *       are EXCLUDED from this view to avoid duplicates with Leads/Service tabs.
     *
     * Multi-tenancy:
     *  - Contact model uses TenantScoped, so all queries are automatically filtered
     *    by agency_id for the currently logged-in user.
     */
    public function index(Request $request)
    {
        $contacts = Contact::query()
            // 👇 do NOT treat leads or service cases as generic contacts
            ->where(function ($q) {
                $q->whereNull('contact_type')
                  ->orWhereNotIn('contact_type', ['lead', 'service']);
            })
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;

                // group the OR conditions so they don't break filters
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('contacts.index', [
            'contacts' => $contacts,
            'selected' => $request->selected, // Auto-open after create/update
        ]);
    }

    /**
     * AJAX contact loader for right panel.
     */
    public function show(Request $request, $id)
    {
        // TenantScoped on Contact ensures only current agency's contact can be found
        $contact = Contact::findOrFail($id);

        return view('contacts.partials.details', compact('contact'));
    }

    /**
     * AJAX "create contact" panel loader.
     */
    public function createAjax(Request $request)
    {
        return view('contacts.partials.create');
    }

    /**
     * Standalone full-page create (legacy)
     */
    public function create()
    {
        return view('contacts.create');
    }

    /**
     * Store a newly created contact OR lead.
     *
     * Multi-tenancy:
     *  - agency_id is automatically set by TenantScoped::creating()
     *  - created_by is set to the current user
     *
     * Redirect behavior:
     *  - If contact_type = 'lead'  → go back to Leads tab with that lead selected
     *  - Otherwise                → go back to Contacts tab with that contact selected
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'contact_type'   => 'nullable|string|max:50',
            'status'         => 'nullable|string|max:50',
            'source'         => 'nullable|string|max:100',
            'address_line1'  => 'nullable|string|max:255',
            'address_line2'  => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:50',
            'postal_code'    => 'nullable|string|max:20',
            'date_of_birth'  => 'nullable|date',
            'anniversary'    => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);

        // Multi-tenant: created_by is the current user
        $validated['created_by'] = Auth::id();

        // agency_id will be auto-filled by TenantScoped creating hook
        $contact = Contact::create($validated);

        // Decide where to send the user based on contact type
        $type = strtolower($contact->contact_type ?? '');

        if ($type === 'lead') {
            // This is a LEAD → go back to Leads tab with this lead selected
            return redirect()
                ->route('leads.index', ['selected' => $contact->id])
                ->with('success', 'Lead created successfully.');
        }

        // All other contacts → stay on Contacts tab
        return redirect()
            ->route('contacts.index', ['selected' => $contact->id])
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Load the edit partial.
     */
    public function edit(Contact $contact)
    {
        // Route model binding + TenantScoped ensure this contact
        // already belongs to the current agency.
        return view('contacts.partials.edit', compact('contact'));
    }

    /**
     * Update contact and return to the correct tab:
     *  - If this contact is a LEAD -> go back to Leads tab
     *  - Otherwise -> go back to Contacts tab
     */
    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'contact_type'   => 'nullable|string|max:50',
            'status'         => 'nullable|string|max:50',
            'source'         => 'nullable|string|max:100',
            'address_line1'  => 'nullable|string|max:255',
            'address_line2'  => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:50',
            'postal_code'    => 'nullable|string|max:20',
            'date_of_birth'  => 'nullable|date',
            'anniversary'    => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);

        $contact->update($validated);

        // Decide where to send the user based on contact type
        $type = strtolower($contact->contact_type ?? '');

        if ($type === 'lead') {
            // This is a lead → go back to Leads tab with this lead selected
            return redirect()
                ->route('leads.index', ['selected' => $contact->id])
                ->with('success', 'Lead updated successfully.');
        }

        // All other contacts → stay on Contacts tab
        return redirect()
            ->route('contacts.index', ['selected' => $contact->id])
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Delete a contact.
     */
    public function destroy(Contact $contact)
    {
        // TenantScoped already ensures this belongs to the current agency
        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contact deleted.');
    }

    /**
     * CSV/Excel Import placeholder.
     */
    public function import(Request $request)
    {
        return back()->with('success', 'Import placeholder working.');
    }
}
