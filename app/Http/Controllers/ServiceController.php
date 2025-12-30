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
    | TENANT / AGENCY SCOPING (AUTO-DETECT)
    |--------------------------------------------------------------------------
    | Your app has used tenant_id in some places and agency_id in others.
    | We auto-detect which column exists on contacts, and scope everything
    | consistently so imports/listing match.
    */

    private function scopeColumn(): ?string
    {
        if (Schema::hasColumn('contacts', 'tenant_id')) return 'tenant_id';
        if (Schema::hasColumn('contacts', 'agency_id')) return 'agency_id';
        return null;
    }

    private function currentScopeValue()
    {
        $user = auth()->user();
        if (!$user) return null;

        $col = $this->scopeColumn();
        if (!$col) return null;

        // Prefer exact property if present, else fallback to 1 to avoid "created but not visible"
        $val = $user->{$col} ?? null;
        return $val ?? 1;
    }

    protected function assertTenant(Contact $client): void
    {
        $user = auth()->user();
        if (!$user) return;

        $col = $this->scopeColumn();
        if (!$col) return;

        $expected = $this->currentScopeValue();
        if ($expected === null) return;

        if ((string)($client->{$col} ?? '') !== (string)$expected) {
            abort(403, 'Unauthorized');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BOOK HELPERS
    |--------------------------------------------------------------------------
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
    | SERVICE FLAG HELPERS
    |--------------------------------------------------------------------------
    */

    private function markAsActiveService(Contact $c): void
    {
        // Service record should show in Service tab
        if (Schema::hasColumn('contacts', 'service_status')) {
            $c->service_status = $c->service_status ?: 'Needs Service';
        }

        if (Schema::hasColumn('contacts', 'service_archived_at')) {
            $c->service_archived_at = null;
        }

        // Optional: for Book ordering "move to top" if you later add this column
        if (Schema::hasColumn('contacts', 'service_flagged_at')) {
            $c->service_flagged_at = now();
        }

        // DO NOT force into book here (new service-only contacts should NOT appear in book)
        $c->save();
    }

    private function serviceBaseQuery()
    {
        $q = Contact::query();

        // Scope by tenant/agency if possible
        $col = $this->scopeColumn();
        $scopeVal = $this->currentScopeValue();
        if ($col && $scopeVal !== null) {
            $q->where($col, $scopeVal);
        }

        // IMPORTANT:
        // Service tab should show:
        //  - Contacts created as contact_type='service'
        //  - OR Contacts that were in Book but got flagged for service (service_status set)
        $q->where(function ($qq) {
            $qq->where('contact_type', 'service');

            if (Schema::hasColumn('contacts', 'service_status')) {
                $qq->orWhereNotNull('service_status');
            }
        });

        return $q;
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = $this->serviceBaseQuery();

        // Only ACTIVE service cases
        if (Schema::hasColumn('contacts', 'service_archived_at')) {
            $query->whereNull('service_archived_at');
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Ordering: flagged-at first if you have it; otherwise alphabetical
        if (Schema::hasColumn('contacts', 'service_flagged_at')) {
            $query->orderByDesc('service_flagged_at');
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
    | CREATE PANEL
    |--------------------------------------------------------------------------
    */
    public function createPanel()
    {
        return view('service.partials.create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE (DEDUPES - DOES NOT CREATE BOOK DUPLICATES)
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
        if (!$user) abort(403);

        $col = $this->scopeColumn();
        $scopeVal = $this->currentScopeValue();

        // Find existing contact in same tenant/agency (prevent duplicates in Book)
        $existing = null;
        if (!empty($validated['email'])) {
            $existing = Contact::query()
                ->when($col && $scopeVal !== null, fn ($q) => $q->where($col, $scopeVal))
                ->where('email', $validated['email'])
                ->first();
        }

        if (!$existing && !empty($validated['phone'])) {
            $existing = Contact::query()
                ->when($col && $scopeVal !== null, fn ($q) => $q->where($col, $scopeVal))
                ->where('phone', $validated['phone'])
                ->first();
        }

        if ($existing) {
            // Update existing, DO NOT change book-ness, DO NOT create duplicates
            $this->assertTenant($existing);

            $existing->fill(array_filter($validated, fn ($v) => $v !== null && $v !== ''));
            $this->markAsActiveService($existing);

            return redirect()->route('service.index', ['selected' => $existing->id]);
        }

        // Create brand-new service-only contact
        $payload = $validated;
        $payload['contact_type'] = 'service';
        $payload['created_by']   = $user->id;

        if ($col && Schema::hasColumn('contacts', $col)) {
            $payload[$col] = $scopeVal ?? 1;
        }

        // DO NOT force into Book for service-only contacts
        if (Schema::hasColumn('contacts', 'in_book_of_business')) {
            $payload['in_book_of_business'] = false;
        }

        $client = Contact::create($payload);

        $this->markAsActiveService($client);

        return redirect()->route('service.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | BULK IMPORT – SERVICE (NO BOOK DUPLICATES)
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
                ->with('import_error', 'Upload worked, but no rows were found in the file. Confirm there is a header row and at least one contact row.');
        }

        $created = 0;
        $matchedExisting = 0;
        $skipped = 0;
        $firstId = null;

        $col = $this->scopeColumn();
        $scopeVal = $this->currentScopeValue();

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
                    'first_name' => $this->cleanStr($first),
                    'last_name'  => $this->cleanStr($last),
                    'city'       => $this->cleanStr($this->getRowVal($row, ['city'])),
                    'state'      => $this->cleanStr($this->getRowVal($row, ['state'])),
                    'phone'      => $this->cleanStr($phone),
                    'email'      => $this->cleanStr($email),

                    // Service-only by default
                    'contact_type' => 'service',
                    'created_by'   => $user->id,
                ];

                // Scope value always set (prevents “created but not visible”)
                if ($col && Schema::hasColumn('contacts', $col)) {
                    $payload[$col] = $scopeVal ?? 1;
                }

                // IMPORTANT: do NOT force into Book for new service-only contacts
                if (Schema::hasColumn('contacts', 'in_book_of_business')) {
                    $payload['in_book_of_business'] = false;
                }

                // Remove null/empty
                foreach ($payload as $k => $v) {
                    if ($v === '' || $v === null) unset($payload[$k]);
                }

                // Dedupe inside tenant/agency: email first, else phone
                $client = null;
                if (!empty($payload['email'])) {
                    $client = Contact::query()
                        ->when($col && $scopeVal !== null, fn ($q) => $q->where($col, $scopeVal))
                        ->where('email', $payload['email'])
                        ->first();
                }

                if (!$client && !empty($payload['phone'])) {
                    $client = Contact::query()
                        ->when($col && $scopeVal !== null, fn ($q) => $q->where($col, $scopeVal))
                        ->where('phone', $payload['phone'])
                        ->first();
                }

                if ($client) {
                    // Match existing (likely Book contact) => FLAG for service, NO DUPLICATE
                    $this->assertTenant($client);

                    $client->fill($payload);

                    // Do NOT override in_book_of_business if they already were in book
                    // Do NOT force contact_type if they were book (keep original)
                    // but if it was blank/unknown, it can remain as-is.
                    $this->markAsActiveService($client);

                    $matchedExisting++;
                } else {
                    $client = Contact::create($payload);
                    $this->markAsActiveService($client);
                    $created++;
                }

                if (!$firstId) $firstId = $client->id;

                // Optional notes from sheet
                $noteText = $this->getRowVal($row, ['notes', 'note']);
                if ($noteText) {
                    $tenantId = null;
                    if ($col && Schema::hasColumn('notes', $col)) {
                        $tenantId = $scopeVal ?? 1;
                    }

                    Note::create(array_filter([
                        'contact_id' => $client->id,
                        'note'       => trim((string) $noteText),
                        'created_by' => $user->id,
                        // only set if that column exists on notes
                        $col ? $col : null => $tenantId,
                    ], fn ($v, $k) => $k !== null && $v !== null, ARRAY_FILTER_USE_BOTH));
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
                ->with('import_error', "Upload worked, but 0 contacts were created/matched. Skipped {$skipped} empty rows.");
        }

        return redirect()
            ->route('service.index', $firstId ? ['selected' => $firstId] : [])
            ->with('import_success', "Import complete: {$created} created, {$matchedExisting} matched existing (flagged for service). Skipped {$skipped} rows.");
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW / EDIT / UPDATE
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

        $client->fill(array_filter($validated, fn ($v) => $v !== null && $v !== ''));

        // Ensure it remains active service (does not force into book)
        $this->markAsActiveService($client);

        // Beneficiaries / emergency contacts (unchanged)
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
    | OUTCOMES
    |--------------------------------------------------------------------------
    */
    public function markSaved(Contact $client)
    {
        $this->assertTenant($client);

        DB::transaction(function () use ($client) {
            if (Schema::hasColumn('contacts', 'service_status')) {
                $client->service_status = 'Saved';
            }
            if (Schema::hasColumn('contacts', 'service_archived_at')) {
                $client->service_archived_at = now();
            }
            $client->save();

            // Saved remains on books (your original logic)
            $this->ensureInBookOfBusinessForContact($client);
        });

        return back()->with('status', 'Service marked Saved and archived. Client remains in Book of Business.');
    }

    public function markBackOnBooks(Contact $client)
    {
        $this->assertTenant($client);

        DB::transaction(function () use ($client) {
            if (Schema::hasColumn('contacts', 'service_status')) {
                $client->service_status = 'Back on Books';
            }
            if (Schema::hasColumn('contacts', 'service_archived_at')) {
                $client->service_archived_at = now();
            }
            $client->save();

            $this->ensureInBookOfBusinessForContact($client);
        });

        return back()->with('status', 'Service marked Back on Books and archived. Client is in Book of Business.');
    }

    public function markNotInterested(Contact $client)
    {
        $this->assertTenant($client);

        DB::transaction(function () use ($client) {
            if (Schema::hasColumn('contacts', 'service_status')) {
                $client->service_status = 'Not Interested';
            }
            if (Schema::hasColumn('contacts', 'service_archived_at')) {
                $client->service_archived_at = now();
            }
            $client->save();

            $this->removeFromBookOfBusinessForContact($client);
        });

        return back()->with('status', 'Service marked Not Interested and archived.');
    }

    public function markCancelled(Contact $client)
    {
        $this->assertTenant($client);

        DB::transaction(function () use ($client) {
            if (Schema::hasColumn('contacts', 'service_status')) {
                $client->service_status = 'Cancelled';
            }
            if (Schema::hasColumn('contacts', 'service_archived_at')) {
                $client->service_archived_at = now();
            }
            $client->save();

            $this->removeFromBookOfBusinessForContact($client);
        });

        return back()->with('status', 'Service marked Cancelled and archived.');
    }

    public function archiveSingle(Contact $client)
    {
        $this->assertTenant($client);

        if (Schema::hasColumn('contacts', 'service_archived_at') && is_null($client->service_archived_at)) {
            $client->service_archived_at = now();
            $client->save();
        }

        return back()->with('status', 'Service record archived for this client.');
    }

    /*
    |--------------------------------------------------------------------------
    | ARCHIVE VIEWS
    |--------------------------------------------------------------------------
    */
    public function archive(Request $request)
    {
        $query = $this->serviceBaseQuery();

        if (Schema::hasColumn('contacts', 'service_archived_at')) {
            $query->whereNotNull('service_archived_at')
                ->orderByDesc('service_archived_at');
        }

        $clients = $query->paginate(25);

        return view('service.archive', [
            'clients' => $clients,
            'filter'  => 'all',
        ]);
    }

    public function notSavedArchive(Request $request)
    {
        $query = $this->serviceBaseQuery();

        if (Schema::hasColumn('contacts', 'service_archived_at')) {
            $query->whereNotNull('service_archived_at');
        }

        if (Schema::hasColumn('contacts', 'service_status')) {
            $query->whereIn('service_status', ['Not Interested', 'Cancelled']);
        }

        if (Schema::hasColumn('contacts', 'service_archived_at')) {
            $query->orderByDesc('service_archived_at');
        }

        $clients = $query->paginate(25);

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
