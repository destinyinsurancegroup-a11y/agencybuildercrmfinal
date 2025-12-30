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
    | TENANT / AGENCY HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Determine which ownership column exists on contacts.
     * Your app sometimes uses tenant_id, sometimes agency_id (TenantScoped).
     */
    protected function ownerColumn(): ?string
    {
        try {
            if (Schema::hasColumn('contacts', 'tenant_id')) return 'tenant_id';
            if (Schema::hasColumn('contacts', 'agency_id')) return 'agency_id';
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
    }

    /**
     * Apply tenant/agency constraint to a query builder.
     */
    protected function scopeToOwner($query, $user)
    {
        $col = $this->ownerColumn();
        if (!$col || !$user) return $query;

        $val = ($col === 'tenant_id') ? ($user->tenant_id ?? null) : ($user->agency_id ?? null);
        if ($val === null) return $query;

        return $query->where($col, $val);
    }

    /**
     * Extra safety: ensure this contact belongs to the same tenant/agency as the user.
     */
    protected function assertTenant(Contact $client): void
    {
        $user = auth()->user();
        if (!$user) return;

        $col = $this->ownerColumn();
        if (!$col) return;

        $expected = ($col === 'tenant_id') ? ($user->tenant_id ?? null) : ($user->agency_id ?? null);
        if ($expected === null) return;

        if ((string)($client->{$col} ?? '') !== (string)$expected) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Ensure the contact is marked as in Book of Business.
     */
    protected function ensureInBookOfBusinessForContact(Contact $client): void
    {
        if (!isset($client->in_book_of_business) || !$client->in_book_of_business) {
            $client->in_book_of_business = true;
            $client->save();
        }
    }

    /**
     * Remove contact from Book of Business.
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
        $user = auth()->user();

        $query = Contact::query()
            ->where('contact_type', 'service')
            ->whereNull('service_archived_at');

        $query = $this->scopeToOwner($query, $user);

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

        $validated['contact_type'] = 'service';
        $validated['created_by']   = $user->id ?? 1;

        // Set owner column correctly (tenant_id OR agency_id)
        $col = $this->ownerColumn();
        if ($col === 'tenant_id') {
            $validated['tenant_id'] = $user->tenant_id ?? 1;
        } elseif ($col === 'agency_id') {
            $validated['agency_id'] = $user->agency_id ?? 1;
        }

        $client = Contact::create($validated);

        $client->in_book_of_business = true;
        $client->service_status      = 'Needs Service';
        $client->service_archived_at = null;
        $client->contact_type        = 'service';
        $client->save();

        return redirect()->route('service.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ BULK IMPORT – SERVICE
    |--------------------------------------------------------------------------
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
                ->with('import_error', 'Upload worked, but no rows were found in the file (no data rows parsed). Confirm there is a header row and at least one contact row.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $firstId = null;

        $ownerCol = $this->ownerColumn();
        $ownerVal = null;
        if ($ownerCol === 'tenant_id') $ownerVal = $user->tenant_id ?? null;
        if ($ownerCol === 'agency_id') $ownerVal = $user->agency_id ?? null;

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
                    'first_name'  => $this->cleanStr($first),
                    'last_name'   => $this->cleanStr($last),
                    'city'        => $this->cleanStr($this->getRowVal($row, ['city'])),
                    'state'       => $this->cleanStr($this->getRowVal($row, ['state'])),
                    'phone'       => $this->cleanStr($phone),
                    'email'       => $this->cleanStr($email),

                    // ✅ FORCE service identity
                    'contact_type'        => 'service',
                    'in_book_of_business' => true,
                    'service_status'      => 'Needs Service',
                    'service_archived_at' => null,
                    'created_by'          => $user->id,
                ];

                // ✅ FORCE owner column so Service tab can see them
                if ($ownerCol && $ownerVal !== null && Schema::hasColumn('contacts', $ownerCol)) {
                    $payload[$ownerCol] = $ownerVal;
                }

                // Optional last_contacted mapping
                $lastContacted = $this->toDate($this->getRowVal($row, ['last_contacted', 'last contacted']));
                if ($lastContacted) {
                    if (Schema::hasColumn('contacts', 'last_contacted_at')) {
                        $payload['last_contacted_at'] = $lastContacted;
                    } elseif (Schema::hasColumn('contacts', 'last_contacted')) {
                        $payload['last_contacted'] = $lastContacted;
                    }
                }

                foreach ($payload as $k => $v) {
                    if ($v === '' || $v === null) unset($payload[$k]);
                }

                // ✅ Dedupe WITH owner constraint
                $clientQuery = Contact::query();
                if ($ownerCol && $ownerVal !== null && Schema::hasColumn('contacts', $ownerCol)) {
                    $clientQuery->where($ownerCol, $ownerVal);
                }

                $client = null;
                if (!empty($payload['email'])) {
                    $client = (clone $clientQuery)->where('email', $payload['email'])->first();
                } elseif (!empty($payload['phone'])) {
                    $client = (clone $clientQuery)->where('phone', $payload['phone'])->first();
                }

                if ($client) {
                    $client->fill($payload);

                    // ✅ HARD-SET critical fields in case fillable blocks something
                    $client->contact_type        = 'service';
                    $client->in_book_of_business = true;
                    $client->service_status      = 'Needs Service';
                    $client->service_archived_at = null;

                    if ($ownerCol && $ownerVal !== null && Schema::hasColumn('contacts', $ownerCol)) {
                        $client->{$ownerCol} = $ownerVal;
                    }

                    $client->save();
                    $updated++;
                } else {
                    $client = new Contact();
                    $client->fill($payload);

                    // ✅ HARD-SET critical fields
                    $client->contact_type        = 'service';
                    $client->in_book_of_business = true;
                    $client->service_status      = 'Needs Service';
                    $client->service_archived_at = null;

                    if ($ownerCol && $ownerVal !== null && Schema::hasColumn('contacts', $ownerCol)) {
                        $client->{$ownerCol} = $ownerVal;
                    }

                    $client->created_by = $user->id;
                    $client->save();
                    $created++;
                }

                if (!$firstId) $firstId = $client->id;

                $noteText = $this->getRowVal($row, ['notes', 'note']);
                if ($noteText) {
                    $tenantId = null;
                    if (Schema::hasColumn('notes', 'tenant_id')) {
                        // prefer tenant_id if your notes table expects it
                        $tenantId = $user->tenant_id ?? ($client->tenant_id ?? null);
                    }

                    $noteData = [
                        'contact_id' => $client->id,
                        'note'       => trim((string) $noteText),
                        'created_by' => $user->id,
                    ];
                    if ($tenantId !== null && Schema::hasColumn('notes', 'tenant_id')) {
                        $noteData['tenant_id'] = $tenantId;
                    }

                    Note::create($noteData);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()
                ->route('service.index')
                ->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        $processed = $created + $updated;

        if ($processed === 0) {
            return redirect()
                ->route('service.index')
                ->with('import_error', "Upload worked, but 0 contacts were created/updated. Skipped {$skipped} empty rows.");
        }

        // ✅ redirect with selected only if we really have an ID
        return redirect()
            ->route('service.index', $firstId ? ['selected' => $firstId] : [])
            ->with('import_success', "Import complete: {$created} created, {$updated} updated. Skipped {$skipped} rows.");
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW PANEL (AJAX ONLY)
    |--------------------------------------------------------------------------
    */
    public function show(Contact $client)
    {
        if (request()->ajax()) {
            $this->assertTenant($client);
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
    | UPDATE
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

        $client->contact_type = 'service';
        $client->save();

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
    | FOLLOW-UP
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
            ->whereNotNull('service_archived_at');

        $clients = $this->scopeToOwner($clients, $user);

        $clients = $clients
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
            ->whereNotNull('service_archived_at')
            ->whereIn('service_status', ['Not Interested', 'Cancelled']);

        $clients = $this->scopeToOwner($clients, $user);

        $clients = $clients
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

    private function toDate($val): ?string
    {
        if ($val === null || $val === '') return null;

        if (is_numeric($val) && class_exists(\PhpOffice\PhpSpreadsheet\Shared\Date::class)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$val);
                return $date->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $val = trim((string)$val);
        try {
            return \Carbon\Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
