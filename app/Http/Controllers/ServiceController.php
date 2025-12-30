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
    | TENANT & BOOK-OF-BUSINESS HELPERS
    |--------------------------------------------------------------------------
    */

    protected function assertTenant(Contact $client): void
    {
        $user = auth()->user();
        if ($user && isset($client->tenant_id) && $client->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized');
        }
    }

    protected function ensureInBookOfBusinessForContact(Contact $client): void
    {
        if (Schema::hasColumn('contacts', 'in_book_of_business')) {
            if (!$client->in_book_of_business) {
                $client->in_book_of_business = true;
                $client->save();
            }
        }
    }

    protected function removeFromBookOfBusinessForContact(Contact $client): void
    {
        if (Schema::hasColumn('contacts', 'in_book_of_business')) {
            if ($client->in_book_of_business) {
                $client->in_book_of_business = false;
                $client->save();
            }
        }
    }

    /**
     * Find an existing contact in THIS tenant by email/phone.
     * Preference: email, then phone.
     */
    protected function findExistingByEmailOrPhone(?string $email, ?string $phone): ?Contact
    {
        $user = auth()->user();

        $email = $email ? trim((string)$email) : null;
        $phone = $phone ? trim((string)$phone) : null;

        $q = Contact::query();
        if ($user && Schema::hasColumn('contacts', 'tenant_id')) {
            $q->where('tenant_id', $user->tenant_id);
        }

        if ($email) {
            $match = (clone $q)->where('email', $email)->first();
            if ($match) return $match;
        }

        if ($phone) {
            $match = (clone $q)->where('phone', $phone)->first();
            if ($match) return $match;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX – LEFT LIST + RIGHT PANEL
    |--------------------------------------------------------------------------
    |
    | IMPORTANT CHANGE:
    | Service tab should show *active service cases* regardless of contact_type.
    | So we filter on service_status/service_archived_at, not contact_type.
    |
    */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Contact::query()
            // Active service cases
            ->whereNull('service_archived_at')
            ->where('service_status', 'Needs Service')
            ->when($user, function ($q) use ($user) {
                if (Schema::hasColumn('contacts', 'tenant_id')) {
                    $q->where('tenant_id', $user->tenant_id);
                }
            });

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
    | STORE – NEW SERVICE CLIENT
    |--------------------------------------------------------------------------
    |
    | IMPORTANT CHANGE:
    | - If contact exists in Book (email/phone), do NOT create duplicate.
    | - Just flag existing contact for service.
    | - Only create a new contact if none exists.
    | - New Service-only contacts should NOT automatically appear in Book.
    |
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

        $email = $validated['email'] ?? null;
        $phone = $validated['phone'] ?? null;

        DB::beginTransaction();
        try {
            $existing = $this->findExistingByEmailOrPhone($email, $phone);

            if ($existing) {
                // ✅ No duplicate: just flag the existing Book contact
                $this->assertTenant($existing);

                // Fill missing fields (optional, safe)
                foreach ($validated as $k => $v) {
                    if ($v !== null && $v !== '') {
                        $existing->{$k} = $v;
                    }
                }

                $existing->service_status      = 'Needs Service';
                $existing->service_archived_at = null;
                $existing->save();

                DB::commit();

                return redirect()->route('service.index', ['selected' => $existing->id]);
            }

            // ✅ Create a true Service-only contact (NOT automatically in Book)
            $payload = $validated;

            if (Schema::hasColumn('contacts', 'tenant_id')) {
                $payload['tenant_id'] = $user->tenant_id ?? 1;
            }
            if (Schema::hasColumn('contacts', 'created_by')) {
                $payload['created_by'] = $user->id ?? 1;
            }

            $payload['contact_type'] = 'service';

            // 🔥 Key fix: do NOT force into Book
            if (Schema::hasColumn('contacts', 'in_book_of_business')) {
                $payload['in_book_of_business'] = false;
            }

            $payload['service_status']      = 'Needs Service';
            $payload['service_archived_at'] = null;

            $client = Contact::create($payload);

            DB::commit();

            return redirect()->route('service.index', ['selected' => $client->id]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('status', 'Error creating service client: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ BULK IMPORT – SERVICE
    |--------------------------------------------------------------------------
    |
    | IMPORTANT CHANGE:
    | - Match existing Book contacts by email/phone -> flag them (no new row).
    | - Only create new row when no match.
    | - New rows created as contact_type='service' and NOT in Book.
    |
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
                ->with('import_error', 'Upload worked, but no rows were found. Confirm there is a header row and at least one contact row.');
        }

        $created = 0;
        $flaggedExisting = 0;
        $skipped = 0;
        $firstId = null;

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

                $emailClean = $this->cleanStr($email);
                $phoneClean = $this->cleanStr($phone);

                $existing = $this->findExistingByEmailOrPhone($emailClean, $phoneClean);

                if ($existing) {
                    // ✅ Flag existing Book contact (NO duplicate)
                    $this->assertTenant($existing);

                    // Optionally fill in missing core fields
                    $fill = [
                        'first_name' => $this->cleanStr($first),
                        'last_name'  => $this->cleanStr($last),
                        'email'      => $emailClean,
                        'phone'      => $phoneClean,
                        'city'       => $this->cleanStr($this->getRowVal($row, ['city'])),
                        'state'      => $this->cleanStr($this->getRowVal($row, ['state'])),
                    ];

                    foreach ($fill as $k => $v) {
                        if ($v !== null && $v !== '' && empty($existing->{$k})) {
                            $existing->{$k} = $v;
                        }
                    }

                    $existing->service_status      = 'Needs Service';
                    $existing->service_archived_at = null;
                    $existing->save();

                    $flaggedExisting++;
                    if (!$firstId) $firstId = $existing->id;

                } else {
                    // ✅ Create Service-only contact (NOT in Book)
                    $payload = [
                        'first_name'  => $this->cleanStr($first),
                        'last_name'   => $this->cleanStr($last),
                        'city'        => $this->cleanStr($this->getRowVal($row, ['city'])),
                        'state'       => $this->cleanStr($this->getRowVal($row, ['state'])),
                        'phone'       => $phoneClean,
                        'email'       => $emailClean,
                        'contact_type'=> 'service',
                        'service_status'      => 'Needs Service',
                        'service_archived_at' => null,
                    ];

                    if (Schema::hasColumn('contacts', 'tenant_id')) {
                        $payload['tenant_id'] = $user->tenant_id ?? 1;
                    }
                    if (Schema::hasColumn('contacts', 'agency_id')) {
                        $payload['agency_id'] = $user->agency_id ?? null;
                    }
                    if (Schema::hasColumn('contacts', 'created_by')) {
                        $payload['created_by'] = $user->id;
                    }
                    if (Schema::hasColumn('contacts', 'in_book_of_business')) {
                        $payload['in_book_of_business'] = false;
                    }

                    foreach ($payload as $k => $v) {
                        if ($v === '' || $v === null) unset($payload[$k]);
                    }

                    $client = Contact::create($payload);

                    $created++;
                    if (!$firstId) $firstId = $client->id;

                    // Notes (optional from sheet)
                    $noteText = $this->getRowVal($row, ['notes', 'note']);
                    if ($noteText) {
                        Note::create([
                            'contact_id' => $client->id,
                            'note'       => trim((string) $noteText),
                            'created_by' => $user->id,
                            'tenant_id'  => $client->tenant_id ?? ($user->tenant_id ?? 1),
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()
                ->route('service.index')
                ->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        $processed = $created + $flaggedExisting;

        if ($processed === 0) {
            return redirect()
                ->route('service.index')
                ->with('import_error', "Upload worked, but 0 contacts were processed. Skipped {$skipped} empty rows.");
        }

        return redirect()
            ->route('service.index', $firstId ? ['selected' => $firstId] : [])
            ->with('import_success', "Import complete: {$created} created, {$flaggedExisting} matched existing (flagged for service). Skipped {$skipped} rows.");
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW PANEL (AJAX ONLY)
    |-------------------------------------------------------------------------- */
    public function show(Contact $client)
    {
        $this->assertTenant($client);

        if (request()->ajax()) {
            return view('service.partials.details', compact('client'));
        }

        abort(404);
    }

    public function editPanel(Contact $client)
    {
        $this->assertTenant($client);
        return view('service.partials.edit', compact('client'));
    }

    public function edit(Contact $client)
    {
        $this->assertTenant($client);
        return view('service.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE – DO NOT FORCE contact_type='service'
    |--------------------------------------------------------------------------
    |
    | IMPORTANT CHANGE:
    | - Book contacts edited from Service screen should stay book contacts.
    | - Service-only contacts stay service.
    |
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

        // Keep them as an active service case
        $client->service_status      = 'Needs Service';
        $client->service_archived_at = null;

        // Only force contact_type to 'service' if they are NOT in book (service-only contact)
        if (Schema::hasColumn('contacts', 'in_book_of_business') && !$client->in_book_of_business) {
            $client->contact_type = 'service';
        }

        $client->save();

        // Beneficiaries / Emergency Contacts (same as your existing logic)
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
    | FOLLOW-UP
    |-------------------------------------------------------------------------- */
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
    |-------------------------------------------------------------------------- */
    public function markSaved(Contact $client)
    {
        $this->assertTenant($client);

        DB::transaction(function () use ($client) {
            $client->service_status      = 'Saved';
            $client->service_archived_at = now();
            $client->save();

            // Saved stays in Book (if it is a Book client, keep it; if service-only, this will add it)
            $this->ensureInBookOfBusinessForContact($client);
        });

        return back()->with('status', 'Service marked Saved and archived.');
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

        return back()->with('status', 'Service marked Back on Books and archived.');
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
    |-------------------------------------------------------------------------- */
    public function archive(Request $request)
    {
        $user = auth()->user();

        $clients = Contact::query()
            ->when($user, function ($q) use ($user) {
                if (Schema::hasColumn('contacts', 'tenant_id')) {
                    $q->where('tenant_id', $user->tenant_id);
                }
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
            ->when($user, function ($q) use ($user) {
                if (Schema::hasColumn('contacts', 'tenant_id')) {
                    $q->where('tenant_id', $user->tenant_id);
                }
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

    // ======================================================================
    // IMPORT HELPERS (same pattern as your existing file)
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
