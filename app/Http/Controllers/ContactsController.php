<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    /**
     * Display the contacts index page (master-detail layout).
     * NOTE: Leads (contact_type = 'lead') and Service cases (contact_type = 'service')
     *       are EXCLUDED from this view to avoid duplicates with Leads/Service tabs.
     */
    public function index(Request $request)
    {
        $tenantId = 1; // TODO: replace with auth()->user()->tenant_id when multi-tenant is wired

        $contacts = Contact::where('tenant_id', $tenantId)
            // 👇 do NOT treat leads or service cases as generic contacts
            ->where(function ($q) {
                $q->whereNull('contact_type')
                  ->orWhereNotIn('contact_type', ['lead', 'service']);
            })
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;

                // group the OR conditions so they don't break tenant/contact filters
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
        $tenantId = 1;

        $contact = Contact::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

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
     * Store a newly created contact.
     * Returns to contacts.index with auto-selected ID.
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

        $validated['tenant_id']  = 1;
        $validated['created_by'] = 1;

        $contact = Contact::create($validated);

        return redirect()
            ->route('contacts.index', ['selected' => $contact->id])
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Load the edit partial.
     */
    public function edit(Contact $contact)
    {
        return view('contacts.partials.edit', compact('contact'));
    }

    /**
     * Update contact and return either:
     *  - back to Leads tab (with this lead selected) if return_to=leads
     *  - or back to Contacts tab (default behavior)
     */
    public function update(Request $request, Contact $contact)
    {
        $tenantId = 1;
        if ($contact->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized tenant access.');
        }

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

        $returnTo = $request->input('return_to');

        // If this update came from the Leads tab, go back to leads.index
        if ($returnTo === 'leads') {
            return redirect()
                ->route('leads.index', ['selected' => $contact->id])
                ->with('success', 'Lead updated successfully.');
        }

        // Default: behave like before (Contacts tab)
        return redirect()
            ->route('contacts.index', ['selected' => $contact->id])
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Delete a contact.
     */
    public function destroy(Contact $contact)
    {
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
