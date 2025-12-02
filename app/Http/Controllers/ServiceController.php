<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Beneficiary;
use App\Models\EmergencyContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TENANT & BOOK-OF-BUSINESS HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Extra safety: ensure this contact belongs to the same tenant as the user.
     * (Assumes contacts table has tenant_id and auth is in place.)
     */
    protected function assertTenant(Contact $client): void
    {
        $user = auth()->user();

        // If no user, we skip the check (e.g., during early setup or CLI),
        // but in normal operation all access should be behind auth.
        if ($user && $client->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Ensure the contact is marked as in Book of Business.
     * Called when a serviced policy is Saved / Back on Books.
     *
     * NOTE: requires an `in_book_of_business` boolean column on contacts
     * which we'll add via migration in a later step.
     */
    protected function ensureInBookOfBusinessForContact(Contact $client): void
    {
        if (!isset($client->in_book_of_business) || !$client->in_book_of_business) {
            $client->in_book_of_business = true;
            $client->save();
        }
    }

    /**
     * Remove contact from Book of Business when business could not be saved,
     * according to your rule.
     *
     * NOTE: for now we simply flip the flag off. If you later add more
     * complex logic (multiple policies, etc.) we can refine this.
     */
    protected function removeFromBookOfBusinessForContact(Contact $client): void
    {
        if (isset($client->in_book_of_business) && $client->in_book_of_business) {
            $client->in_book_of_business = false;
            $client->save();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX – LEFT LIST + RIGHT PANEL
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = Contact::where('contact_type', 'service');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $clients = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $selected = $request->get('selected');

        return view('service.index', compact('clients', 'selected'));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PANEL (AJAX)
    |--------------------------------------------------------------------------
    */
    public function createPanel()
    {
        return view('service.partials.create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE – NEW / EXISTING SERVICE CLIENT
    |--------------------------------------------------------------------------
    |
    | NEW BEHAVIOR:
    | - If this person already exists in this tenant (by email/phone/name+dob),
    |   reuse that Contact row, update details, and convert it to a service case.
    | - If not found, create a brand-new Contact as service + in_book_of_business.
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:255',

            'address_line1'     => 'nullable|string|max:255',
            'address_line2'     => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:255',
            'postal_code'       => 'nullable|string|max:50',

            'date_of_birth'     => 'nullable|date',
            'anniversary'       => 'nullable|date',

            'carrier'           => 'nullable|string|max:255',
            'policy_type'       => 'nullable|string|max:255',
            'face_amount'       => 'nullable|numeric',
            'premium_amount'    => 'nullable|numeric',
            'premium_due_date'  => 'nullable|date',
            'policy_issue_date' => 'nullable|date',
            'premium_due_text'  => 'nullable|string|max:255',

            'notes'             => 'nullable|string',
        ]);

        $user      = auth()->user();
        $tenantId  = $user->tenant_id ?? 1;
        $createdBy = $user->id ?? 1;

        // Try to find an existing contact in this tenant that matches this person.
        // Priority: email / phone, then name (+ DOB if provided).
        $existing = Contact::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($validated) {
                if (!empty($validated['email'])) {
                    $q->orWhere('email', $validated['email']);
                }
                if (!empty($validated['phone'])) {
                    $q->orWhere('phone', $validated['phone']);
                }

                $q->orWhere(function ($q2) use ($validated) {
                    $q2->where('first_name', $validated['first_name'])
                       ->where('last_name', $validated['last_name']);

                    if (!empty($validated['date_of_birth'])) {
                        $q2->where('date_of_birth', $validated['date_of_birth']);
                    }
                });
            })
            ->first();

        if ($existing) {
            // Reuse this contact and convert it into a service case
            foreach ($validated as $key => $value) {
                if ($value !== null && $value !== '') {
                    $existing->{$key} = $value;
                }
            }

            $existing->contact_type        = 'service';
            $existing->service_status      = null;  // open service
            $existing->service_archived_at = null;  // not resolved yet
            $existing->in_book_of_business = true;  // must show in BoB (red/urgent)
            $existing->save();

            $client = $existing;
        } else {
            // No existing record – create a brand new service contact
            $validated['contact_type']        = 'service';
            $validated['tenant_id']           = $tenantId;
            $validated['created_by']          = $createdBy;
            $validated['in_book_of_business'] = true; // also in Book of Business

            $client = Contact::create($validated);
        }

        return redirect()->route('service.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW PANEL (AJAX ONLY)
    |--------------------------------------------------------------------------
    */
    public function show(Contact $client)
    {
        if (request()->ajax()) {
            return view('service.partials.details', compact('client'));
        }

        abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT PANEL (AJAX)
    |--------------------------------------------------------------------------
    */
    public function editPanel(Contact $client)
    {
        return view('service.partials.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | FULL PAGE EDIT (OPTIONAL)
    |--------------------------------------------------------------------------
    */
    public function edit(Contact $client)
    {
        return view('service.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE – SAME AS BOOK, BUT contact_type = 'service'
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Contact $client)
    {
        $validated = $request->validate([
            'first_name'        => 'nullable|string|max:255',
            'last_name'         => 'nullable|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:255',

            'address_line1'     => 'nullable|string|max:255',
            'address_line2'     => 'nullable|string|max:255',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:255',
            'postal_code'       => 'nullable|string|max:50',

            'date_of_birth'     => 'nullable|date',
            'anniversary'       => 'nullable|date',

            'carrier'           => 'nullable|string|max:255',
            'policy_type'       => 'nullable|string|max:255',
            'face_amount'       => 'nullable|numeric',
            'premium_amount'    => 'nullable|numeric',
            'premium_due_date'  => 'nullable|date',
            'policy_issue_date' => 'nullable|date',
            'premium_due_text'  => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null && $value !== '') {
                $client->{$key} = $value;
            }
        }

        $client->contact_type = 'service';
        $client->save();

        // Reuse same beneficiaries/emergency update logic as Book
        if ($request->has('beneficiaries')) {
            foreach ($request->beneficiaries as $row) {
                if (!isset($row['name']) || $row['name'] === '') continue;

                if (isset($row['id'])) {
                    $b = Beneficiary::where('id', $row['id'])
                        ->where('contact_id', $client->id)
                        ->first();
                    if ($b) {
                        $b->update([
                            'name'        => $row['name'],
                            'relationship'=> $row['relationship'] ?? null,
                            'phone'       => $row['phone'] ?? null,
                            'contacted'   => $row['contacted'] ?? 0,
                        ]);
                    }
                } else {
                    Beneficiary::create([
                        'contact_id'  => $client->id,
                        'name'        => $row['name'],
                        'relationship'=> $row['relationship'] ?? null,
                        'phone'       => $row['phone'] ?? null,
                        'contacted'   => $row['contacted'] ?? 0,
                    ]);
                }
            }
        }

        if ($request->has('emergency_contacts')) {
            foreach ($request->emergency_contacts as $row) {
                if (!isset($row['name']) || $row['name'] === '') continue;

                if (isset($row['id'])) {
                    $e = EmergencyContact::where('id', $row['id'])
                        ->where('contact_id', $client->id)
                        ->first();
                    if ($e) {
                        $e->update([
                            'name'        => $row['name'],
                            'relationship'=> $row['relationship'] ?? null,
                            'phone'       => $row['phone'] ?? null,
                            'contacted'   => $row['contacted'] ?? 0,
                        ]);
                    }
                } else {
                    EmergencyContact::create([
                        'contact_id'  => $client->id,
                        'name'        => $row['name'],
                        'relationship'=> $row['relationship'] ?? null,
                        'phone'       => $row['phone'] ?? null,
                        'contacted'   => $row['contacted'] ?? 0,
                    ]);
                }
            }
        }

        return redirect()->route('service.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | FOLLOW-UP – OPEN CALENDAR PRE-FILLED FROM SERVICE RECORD
    |--------------------------------------------------------------------------
    |
    | Route: GET /service/{client}/follow-up  (service.follow-up)
    |--------------------------------------------------------------------------
    */
    public function followUp(Contact $client)
    {
        $this->assertTenant($client);

        $name = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));

        $query = [
            'contact_id' => $client->id,
            'name'       => $name,
            'phone'      => $client->phone ?? null,
            'email'      => $client->email ?? null,
            'source'     => 'service', // so calendar can differentiate if needed
        ];

        return redirect()->to('/calendar?' . http_build_query(array_filter($query)));
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE OUTCOMES – SAVED / BACK ON BOOKS / NOT INTERESTED / CANCELLED
    |--------------------------------------------------------------------------
    |
    | These rely on two new columns on contacts:
    |   - service_status (string: Saved, Back on Books, Not Interested, Cancelled, etc.)
    |   - service_archived_at (timestamp)
    |--------------------------------------------------------------------------
    */

    public function markSaved(Contact $client)
    {
        $this->assertTenant($client);

        DB::transaction(function () use ($client) {
            $client->service_status      = 'Saved';
            $client->service_archived_at = now();
            $client->save();

            $this->ensureInBookOfBusinessForContact($client);
        });

        return back()->with('status', 'Service marked Saved and archived. Client remains in Book of Business.');
    }

    public function markBackOnBooks(Contact $client)
    {
        $this->assertTenant($client);

        DB::transaction(function () use ($client) {
            $client->service_status      = 'Back on Books';
            $client->service_archived_at = now();
            $client->save();

            $this->ensureInBookOfBusinessForContact($client);
        });

        return back()->with('status', 'Service marked Back on Books and archived. Client is in Book of Business.');
    }

    public function markNotInterested(Contact $client)
    {
        $this->assertTenant($client);

        DB::transaction(function () use ($client) {
            $client->service_status      = 'Not Interested';
            $client->service_archived_at = now();
            $client->save();

            $this->removeFromBookOfBusinessForContact($client);
        });

        return back()->with('status', 'Service marked Not Interested and archived.');
    }

    public function markCancelled(Contact $client)
    {
        $this->assertTenant($client);

        DB::transaction(function () use ($client) {
            $client->service_status      = 'Cancelled';
            $client->service_archived_at = now();
            $client->save();

            $this->removeFromBookOfBusinessForContact($client);
        });

        return back()->with('status', 'Service marked Cancelled and archived.');
    }

    public function archiveSingle(Contact $client)
    {
        $this->assertTenant($client);

        if (is_null($client->service_archived_at)) {
            $client->service_archived_at = now();
            $client->save();
        }

        return back()->with('status', 'Service record archived for this client.');
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE ARCHIVE VIEWS
    |--------------------------------------------------------------------------
    */
    public function archive(Request $request)
    {
        $user = auth()->user();

        $clients = Contact::query()
            ->where('contact_type', 'service')
            ->when($user, function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id);
            })
            ->whereNotNull('service_archived_at')
            ->orderByDesc('service_archived_at')
            ->paginate(25);

        return view('service.archive', [
            'clients' => $clients,
            'filter'  => 'all',
        ]);
    }

    public function notSavedArchive(Request $request)
    {
        $user = auth()->user();

        $clients = Contact::query()
            ->where('contact_type', 'service')
            ->when($user, function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id);
            })
            ->whereNotNull('service_archived_at')
            ->whereIn('service_status', ['Not Interested', 'Cancelled'])
            ->orderByDesc('service_archived_at')
            ->paginate(25);

        return view('service.archive', [
            'clients' => $clients,
            'filter'  => 'not-saved',
        ]);
    }
}
