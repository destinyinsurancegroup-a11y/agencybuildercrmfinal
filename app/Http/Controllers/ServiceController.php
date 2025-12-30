<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Beneficiary;
use App\Models\EmergencyContact;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TENANT/AGENCY HELPERS
    |--------------------------------------------------------------------------
    | Your app appears to sometimes use tenant_id and sometimes agency_id.
    | We will scope using agency_id if it exists; otherwise tenant_id.
    */

    /**
     * Returns the scoping column name: agency_id if present, else tenant_id.
     */
    protected function scopeColumn(): string
    {
        try {
            if (Schema::hasColumn('contacts', 'agency_id')) return 'agency_id';
        } catch (\Throwable $e) {
            // ignore
        }
        return 'tenant_id';
    }

    /**
     * Returns the current user's scope value (agency_id or tenant_id) with fallback.
     */
    protected function scopeValue()
    {
        $user = auth()->user();
        $col = $this->scopeColumn();

        if (!$user) return null;

        if ($col === 'agency_id') {
            return $user->agency_id ?? 1;
        }

        return $user->tenant_id ?? 1;
    }

    /**
     * Extra safety: ensure this contact belongs to the same scope as the user.
     */
    protected function assertTenant(Contact $client): void
    {
        $user = auth()->user();
        if (!$user) return;

        $col = $this->scopeColumn();
        $val = $this->scopeValue();

        // If the column exists but client doesn't have a value yet, treat as mismatch
        if (!isset($client->{$col}) || $client->{$col} != $val) {
            abort(403, 'Unauthorized');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BOOK-OF-BUSINESS HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Ensure the contact is marked as in Book of Business.
     * Called when a serviced policy is Saved / Back on Books.
     */
    protected function ensureInBookOfBusinessForContact(Contact $client): void
    {
        if (Schema::hasColumn('contacts', 'in_book_of_business')) {
            if (!$client->in_book_of_business) {
                $client->in_book_of_business = true;
                $client->save();
            }
        }
    }

    /**
     * Remove contact from Book of Business when business could not be saved.
     */
    protected function removeFromBookOfBusinessForContact(Contact $client): void
    {
        if (Schema::hasColumn('contacts', 'in_book_of_business')) {
            if ($client->in_book_of_business) {
                $client->in_book_of_business = false;
                $client->save();
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX – LEFT LIST + RIGHT PANEL
    |--------------------------------------------------------------------------
    | IMPORTANT CHANGE:
    | Service list should include:
    |  - contacts created as service (contact_type='service')
    |  - book contacts that are flagged for service (service_status set, not archived)
    */
    public function index(Request $request)
    {
        $user = auth()->user();
        $col  = $this->scopeColumn();
        $val  = $this->scopeValue();

        $query = Contact::query()
            ->whereNull('service_archived_at')
            ->where(function ($q) {
                $q->where('contact_type', 'service')
                  ->orWhereNotNull('service_status'); // flagged book contacts also show
            })
            ->when($user, function ($q) use ($col, $val) {
                $q->where($col, $val);
            });

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Put "flagged service" (Needs Service etc.) at top
        $clients = $query
            ->orderByRaw("CASE WHEN service_status IS NULL OR service_status='' THEN 1 ELSE 0 END ASC")
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
    | STORE – NEW SERVICE CLIENT
    |--------------------------------------------------------------------------
    | IMPORTANT CHANGE:
    | - Creating a contact in Service should NOT automatically create a Book duplicate.
    | - So we DO NOT set in_book_of_business=true here.
    | - If a matching Book contact exists (email/phone), we flag it for service instead.
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

        $user = auth()->user();
        if (!$user) abort(403);

        $col = $this->scopeColumn();
        $val = $this->scopeValue();

        // 1) If a matching contact already exists in THIS scope, flag it for service
        $existing = null;
        if (!empty($validated['email'])) {
            $existing = Contact::query()
                ->where($col, $val)
                ->where('email', $validated['email'])
                ->first();
        }
        if (!$existing && !empty($validated['phone'])) {
            $existing = Contact::query()
                ->where($col, $val)
                ->where('phone', $validated['phone'])
                ->first();
        }

        if ($existing) {
            $this->assertTenant($existing);

            $existing->service_status      = 'Needs Service';
            $existing->service_archived_at = null;
            $existing->save();

            return redirect()->route('service.index', ['selected' => $existing->id])
                ->with('status', 'Existing contact found. Flagged for Service (no duplicate created).');
        }

        // 2) Otherwise create a NEW service-only contact (NOT in book by default)
        $validated['contact_type'] = 'service';
        $validated['created_by']   = $user->id ?? 1;

        // scope columns
        if (Schema::hasColumn('contacts', $col)) {
            $validated[$col] = $val;
        }

        // Create the service client
        $client = Contact::create($validated);

        // Service flags
        $client->service_status      = 'Needs Service';
        $client->service_archived_at = null;

        // IMPORTANT: do NOT add to book automatically
        if (Schema::hasColumn('contacts', 'in_book_of_business')) {
            $client->in_book_of_business = false;
        }

        $client->save();

        return redirect()->route('service.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | BULK IMPORT – SERVICE
    |--------------------------------------------------------------------------
    | IMPORTANT CHANGE:
    | - If email/phone matches an existing contact in scope:
    |     DO NOT create duplicate; flag existing for service.
    | - New service contacts are NOT added to book automatically.
    */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $user = Auth::user();
        if (!$user) abort(403);

        $file = $request->file('file');
        $ext  = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($ext, ['xlsx', 'xls'], true) && !class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return redirect()
                ->route('service.index')
                ->with('import_error', 'Excel upload requires PhpSpreadsheet. Run: composer require phpoffice/phpspreadsheet then try again.');
        }

        $rows = $this->parseSpreadsheetToRows($file->getRealPath(), $ext);

        if (count($rows) === 0) {
            return redirect()
                ->route('service.index')
                ->with('import_error', 'Upload worked, but no rows were found in the file.');
        }

        $created = 0;
        $matchedExisting = 0;
        $skipped = 0;
        $firstId = null;

        $col = $this->scopeColumn();
        $val = $this->scopeValue();

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $first = $this->getRowVal($row, ['first_name','first name','firstname','fname']);
                $last  = $this->getRowVal($row, ['last_name','last name','lastname','lname']);
                $email = $this->getRowVal($row, ['email','e-mail','email_address']);
                $phone = $this->getRowVal($row, ['phone','phone_number','mobile','cell']);

                if ($this->isBlankIdentifierRow($first, $last, $email, $phone)) {
                    $skipped++;
                    continue;
                }

                $payload = [
                    'first_name'   => $this->cleanStr($first),
                    'last_name'    => $this->cleanStr($last),
                    'city'         => $this->cleanStr($this->getRowVal($row, ['city'])),
                    'state'        => $this->cleanStr($this->getRowVal($row, ['state'])),
                    'phone'        => $this->cleanStr($phone),
                    'email'        => $this->cleanStr($email),
                    'contact_type' => 'service',
                    'created_by'   => $user->id,
                ];

                if (Schema::hasColumn('contacts', $col)) {
                    $payload[$col] = $val;
                }

                // Service flags
                $payload['service_status']      = 'Needs Service';
                $payload['service_archived_at'] = null;

                // IMPORTANT: New service-only contacts are NOT in book by default
                if (Schema::hasColumn('contacts', 'in_book_of_business')) {
                    $payload['in_book_of_business'] = false;
                }

                // Remove null/empty
                foreach ($payload as $k => $v) {
                    if ($v === '' || $v === null) unset($payload[$k]);
                }

                // Find existing contact by email/phone within same scope
                $existing = null;
                if (!empty($payload['email'])) {
                    $existing = Contact::query()
                        ->where($col, $val)
                        ->where('email', $payload['email'])
                        ->first();
                }
                if (!$existing && !empty($payload['phone'])) {
                    $existing = Contact::query()
                        ->where($col, $val)
                        ->where('phone', $payload['phone'])
                        ->first();
                }

                if ($existing) {
                    // Flag existing (book OR service) contact for service — NO DUPLICATE
                    $this->assertTenant($existing);

                    $existing->service_status      = 'Needs Service';
                    $existing->service_archived_at = null;
                    $existing->save();

                    $matchedExisting++;

                    if (!$firstId) $firstId = $existing->id;

                    // Optional note
                    $noteText = $this->getRowVal($row, ['notes', 'note']);
                    if ($noteText) {
                        $tenantId = $existing->tenant_id ?? ($user->tenant_id ?? 1);
                        Note::create([
                            'contact_id' => $existing->id,
                            'note'       => trim((string) $noteText),
                            'created_by' => $user->id,
                            'tenant_id'  => $tenantId,
                        ]);
                    }

                    continue;
                }

                // Create a new service-only contact
                $client = Contact::create($payload);

                // Ensure flags persisted even if fillable blocks
                $client->contact_type        = 'service';
                $client->service_status      = 'Needs Service';
                $client->service_archived_at = null;
                if (Schema::hasColumn('contacts', 'in_book_of_business')) {
                    $client->in_book_of_business = false;
                }
                if (Schema::hasColumn('contacts', $col)) {
                    $client->{$col} = $val;
                }
                $client->save();

                $created++;
                if (!$firstId) $firstId = $client->id;

                // Optional note
                $noteText = $this->getRowVal($row, ['notes', 'note']);
                if ($noteText) {
                    $tenantId = $client->tenant_id ?? ($user->tenant_id ?? 1);
                    Note::create([
                        'contact_id' => $client->id,
                        'note'       => trim((string) $noteText),
                        'created_by' => $user->id,
                        'tenant_id'  => $tenantId,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()
                ->route('service.index')
                ->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        $processed = $created + $matchedExisting;

        if ($processed === 0) {
            return redirect()
                ->route('service.index')
                ->with('import_error', "Upload worked, but 0 contacts were created/flagged. Skipped {$skipped} empty rows.");
        }

        return redirect()
            ->route('service.index', $firstId ? ['selected' => $firstId] : [])
            ->with('import_success', "Import complete: {$created} created, {$matchedExisting} matched existing (flagged for service). Skipped {$skipped} rows.");
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW PANEL (AJAX ONLY)
    |--------------------------------------------------------------------------
    */
    public function show(Contact $client)
    {
        $this->assertTenant($client);

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
        $this->assertTenant($client);

        return view('service.partials.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | FULL PAGE EDIT (OPTIONAL)
    |--------------------------------------------------------------------------
    */
    public function edit(Contact $client)
    {
        $this->assertTenant($client);

        return view('service.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE – Keep record in service flow, but DO NOT force book duplication
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Contact $client)
    {
        $this->assertTenant($client);

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

        // Ensure it remains in service workflow
        $client->service_status      = $client->service_status ?: 'Needs Service';
        $client->service_archived_at = null;

        // If this record is service-only, keep contact_type=service
        // If it is a book contact flagged for service, do NOT force contact_type.
        if ($client->contact_type === 'service') {
            $client->contact_type = 'service';
        }

        $client->save();

        // Beneficiaries/emergency contacts unchanged (kept from your version)
        if ($request->has('beneficiaries')) {
            foreach ($request->beneficiaries as $row) {
                if (!isset($row['name']) || $row['name'] === '') continue;

                if (isset($row['id'])) {
                    $b = Beneficiary::where('id', $row['id'])
                        ->where('contact_id', $client->id)
                        ->first();
                    if ($b) {
                        $b->update([
                            'name'         => $row['name'],
                            'relationship' => $row['relationship'] ?? null,
                            'phone'        => $row['phone'] ?? null,
                            'contacted'    => $row['contacted'] ?? 0,
                        ]);
                    }
                } else {
                    Beneficiary::create([
                        'contact_id'   => $client->id,
                        'name'         => $row['name'],
                        'relationship' => $row['relationship'] ?? null,
                        'phone'        => $row['phone'] ?? null,
                        'contacted'    => $row['contacted'] ?? 0,
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
                            'name'         => $row['name'],
                            'relationship' => $row['relationship'] ?? null,
                            'phone'        => $row['phone'] ?? null,
                            'contacted'    => $row['contacted'] ?? 0,
                        ]);
                    }
                } else {
                    EmergencyContact::create([
                        'contact_id'   => $client->id,
                        'name'         => $row['name'],
                        'relationship' => $row['relationship'] ?? null,
                        'phone'        => $row['phone'] ?? null,
                        'contacted'    => $row['contacted'] ?? 0,
                    ]);
                }
            }
        }

        return redirect()->route('service.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | FOLLOW-UP – OPEN CALENDAR PRE-FILLED
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
            'source'     => 'service',
        ];

        return redirect()->to('/calendar?' . http_build_query(array_filter($query)));
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE OUTCOMES
    |--------------------------------------------------------------------------
    */
    public function markSaved(Contact $client)
    {
        $this->assertTenant($client);

        DB::transaction(function () use ($client) {
            $client->service_status      = 'Saved';
            $client->service_archived_at = now();
            $client->save();

            // Saved means they should be in book
            $this->ensureInBookOfBusinessForContact($client);
        });

        return back()->with('status', 'Service marked Saved and archived. Client remains/added to Book of Business.');
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
        $col  = $this->scopeColumn();
        $val  = $this->scopeValue();

        $clients = Contact::query()
            ->whereNotNull('service_archived_at')
            ->where(function ($q) {
                $q->where('contact_type', 'service')
                  ->orWhereNotNull('service_status');
            })
            ->when($user, function ($q) use ($col, $val) {
                $q->where($col, $val);
            })
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
        $col  = $this->scopeColumn();
        $val  = $this->scopeValue();

        $clients = Contact::query()
            ->whereNotNull('service_archived_at')
            ->whereIn('service_status', ['Not Interested', 'Cancelled'])
            ->when($user, function ($q) use ($col, $val) {
                $q->where($col, $val);
            })
            ->orderByDesc('service_archived_at')
            ->paginate(25);

        return view('service.archive', [
            'clients' => $clients,
            'filter'  => 'not-saved',
        ]);
    }

    // ======================================================================
    // IMPORT HELPERS
    // ======================================================================

    private function parseSpreadsheetToRows(string $path, string $ext): array
    {
        $ext = strtolower($ext);

        if ($ext === 'csv' || $ext === 'txt') {
            return $this->parseCsv($path);
        }

        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) < 2) return [];

            $headerRow = array_shift($rows);
            $headers = [];
            foreach ($headerRow as $col => $val) {
                $headers[$col] = $this->normalizeHeader((string)$val);
            }

            $out = [];
            foreach ($rows as $r) {
                $assoc = [];
                foreach ($headers as $col => $h) {
                    if ($h === '') continue;
                    $assoc[$h] = isset($r[$col]) ? trim((string)$r[$col]) : null;
                }
                if (count(array_filter($assoc, fn($v) => $v !== null && $v !== '')) === 0) continue;
                $out[] = $assoc;
            }
            return $out;
        }

        return [];
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) return [];

        $headers = fgetcsv($handle);
        if (!$headers) return [];

        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headers[0]);
        }

        $headers = array_map(fn($h) => $this->normalizeHeader((string)$h), $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $i => $h) {
                if ($h === '') continue;
                $row[$h] = isset($data[$i]) ? trim((string)$data[$i]) : null;
            }
            if (count(array_filter($row, fn($v) => $v !== null && $v !== '')) === 0) continue;
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function normalizeHeader(string $h): string
    {
        $h = Str::of($h)->trim()->lower()->toString();
        $h = preg_replace('/[^a-z0-9\_ ]/i', '', $h) ?? $h;
        $h = str_replace(' ', '_', $h);
        $h = preg_replace('/_+/', '_', $h) ?? $h;
        return trim((string)$h, '_');
    }

    private function getRowVal(array $row, array $keys)
    {
        foreach ($keys as $k) {
            $k = $this->normalizeHeader((string)$k);
            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                return $row[$k];
            }
        }
        return null;
    }

    private function isBlankIdentifierRow($first, $last, $email, $phone): bool
    {
        $first = trim((string)$first);
        $last  = trim((string)$last);
        $email = trim((string)$email);
        $phone = trim((string)$phone);

        return ($first === '' && $last === '' && $email === '' && $phone === '');
    }

    private function cleanStr($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s);
        return Str::limit($s, 2000, '');
    }
}
