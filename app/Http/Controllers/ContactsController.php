<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactPolicy; // ✅ ADD
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactsController extends Controller
{
    /**
     * Display the contacts index page (master-detail layout).
     */
    public function index(Request $request)
    {
        $contacts = Contact::query()
            ->where(function ($q) {
                $q->whereNull('contact_type')
                  ->orWhereNotIn('contact_type', ['lead', 'service']);
            })
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;
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
            'selected' => $request->selected,
        ]);
    }

    /**
     * AJAX contact loader for right panel.
     */
    public function show(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);
        return view('contacts.partials.details', compact('contact'));
    }

    public function createAjax(Request $request)
    {
        return view('contacts.partials.create');
    }

    public function create()
    {
        return view('contacts.create');
    }

    /**
     * Store a newly created contact OR lead.
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

        $validated['created_by'] = Auth::id();

        $contact = Contact::create($validated);

        $type = strtolower($contact->contact_type ?? '');

        if ($type === 'lead') {
            return redirect()
                ->route('leads.index', ['selected' => $contact->id])
                ->with('success', 'Lead created successfully.');
        }

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
     * Update contact AND policies.
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

        // ✅ SAVE POLICIES (like beneficiaries/emergency contacts)
        $this->savePolicies($contact, $request);

        $type = strtolower($contact->contact_type ?? '');

        if ($type === 'lead') {
            return redirect()
                ->route('leads.index', ['selected' => $contact->id])
                ->with('success', 'Lead updated successfully.');
        }

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

    public function import(Request $request)
    {
        return back()->with('success', 'Import placeholder working.');
    }

    /* ============================================================
     |  POLICY SAVE LOGIC (mirrors beneficiary behavior)
     * ============================================================ */

    private function savePolicies(Contact $contact, Request $request): void
    {
        $policies = $request->input('policies', []);

        // Remove empty rows
        $policies = array_values(array_filter($policies, function ($p) {
            return !empty(array_filter($p));
        }));

        $keepIds = collect($policies)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        // Delete removed policies
        ContactPolicy::where('contact_id', $contact->id)
            ->where('agency_id', $contact->agency_id)
            ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
            ->delete();

        foreach ($policies as $p) {
            $data = [
                'agency_id'         => $contact->agency_id,
                'contact_id'        => $contact->id,
                'carrier'           => $p['carrier'] ?? null,
                'policy_type'       => $p['policy_type'] ?? null,
                'face_amount'       => $p['face_amount'] ?? null,
                'premium_amount'    => $p['premium_amount'] ?? null,
                'policy_issue_date' => $p['policy_issue_date'] ?? null,
                'premium_due_date'  => $p['premium_due_date'] ?? null,
                'premium_due_text'  => $p['premium_due_text'] ?? null,
            ];

            if (!empty($p['id'])) {
                ContactPolicy::where('id', $p['id'])
                    ->where('contact_id', $contact->id)
                    ->where('agency_id', $contact->agency_id)
                    ->update($data);
            } else {
                ContactPolicy::create($data);
            }
        }
    }
}
