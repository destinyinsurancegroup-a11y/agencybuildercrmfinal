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
     * NOTE: requires an `in_book_of_business` boolean column on contacts.
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
            // Only show ACTIVE service cases (not yet archived)
            ->whereNull('service_archived_at')
            ->when($user, function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id);
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

        // TODO: replace fallback tenant/user with strict auth once multi-tenant auth is fully wired
        $user = auth()->user();

        $validated['contact_type'] = 'service';
        $validated['tenant_id']    = $user->tenant_id ?? 1;
        $validated['created_by']   = $user->id ?? 1;

        // Create the service client
        $client = Contact::create($validated);

        // 🔴 Make sure they appear in Book of Business AND are flagged as needing service
        $client->in_book_of_business = true;            // so Book of Business includes them
        $client->service_status      = 'Needs Service'; // initial status for active service
        $client->service_archived_at = null;            // explicitly "still active"
        $client->save();

        return redirect()->route('service.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ BULK IMPORT – SERVICE (Book-of-Business style)
    |--------------------------------------------------------------------------
    | Creates 1 service contact card per row.
    | Accepts CSV/TXT/XLSX/XLS
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

        // If they upload XLSX/XLS and PhpSpreadsheet isn't installed, fail loudly.
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

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // IDENTIFIERS (any one is enough)
                $first = $this->getRowVal($row, ['first_name','first name','firstname','fname']);
                $last  = $this->getRowVal($row, ['last_name','last name','lastname','lname']);
                $email = $this->getRowVal($row, ['email','e-mail','email_address']);
                $phone = $this->getRowVal($row, ['phone','phone_number','mobile','cell']);

                if ($this->isBlankIdentifierRow($first, $last, $email, $phone)) {
                    $skipped++;
                    continue;
                }

                // Base payload (keep it compatible with your contacts schema)
                $payload = [
                    'first_name'  => $this->cleanStr($first),
                    'last_name'   => $this->cleanStr($last),
                    'city'        => $this->cleanStr($this->getRowVal($row, ['city'])),
                    'state'       => $this->cleanStr($this->getRowVal($row, ['state'])),
                    'phone'       => $this->cleanStr($phone),
                    'email'       => $this->cleanStr($email),

                    'contact_type'        => 'service',
                    'in_book_of_business' => true,
                    'created_by'          => $user->id,
                ];

                // Try to set tenant/agency if those columns exist
                $this->setIfHasColumn($payload, 'tenant_id', $user->tenant_id ?? null);
                $this->setIfHasColumn($payload, 'agency_id', $user->agency_id ?? null);

                // Service flags
                $payload['service_status']      = 'Needs Service';
                $payload['service_archived_at'] = null;

                // Optional: last contacted mapping if your contacts table has a suitable column
                $lastContacted = $this->toDate($this->getRowVal($row, ['last_contacted', 'last contacted']));
                if ($lastContacted) {
                    if (Schema::hasColumn('contacts', 'last_contacted_at')) {
                        $payload['last_contacted_at'] = $lastContacted;
                    } elseif (Schema::hasColumn('contacts', 'last_contacted')) {
                        $payload['last_contacted'] = $lastContacted;
                    }
                }

                // Remove null/empty
                foreach ($payload as $k => $v) {
                    if ($v === '' || $v === null) unset($payload[$k]);
                }

                // Dedupe: prefer email, else phone (same pattern as BookController)
                $client = null;
                if (!empty($payload['email'])) {
                    $client = Contact::query()->where('email', $payload['email'])->first();
                } elseif (!empty($payload['phone'])) {
                    $client = Contact::query()->where('phone', $payload['phone'])->first();
                }

                if ($client) {
                    // If you want to be strict multi-tenant, only update if tenant matches.
                    // Otherwise, it will update the first matching email/phone found.
                    $client->fill($payload);
                    $client->contact_type        = 'service';
                    $client->in_book_of_business = true;
                    $client->service_status      = 'Needs Service';
                    $client->service_archived_at = null;
                    $client->save();
                    $updated++;
                } else {
                    $client = Contact::create($payload);
                    $client->contact_type        = 'service';
                    $client->in_book_of_business = true;
                    $client->service_status      = 'Needs Service';
                    $client->service_archived_at = null;
                    $client->save();
                    $created++;
                }

                if (!$firstId) $firstId = $client->id;

                // Notes (optional from sheet)
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

        $processed = $created + $updated;

        if ($processed === 0) {
            return redirect()
                ->route('service.index')
                ->with('import_error', "Upload worked, but 0 contacts were created/updated. Skipped {$skipped} empty rows.");
        }

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

    // ======================================================================
    // IMPORT HELPERS (same pattern as BookController)
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

        // strip UTF-8 BOM
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

    private function setIfHasColumn(array &$payload, string $column, $value): void
    {
        if ($value === null) return;
        try {
            if (Schema::hasColumn('contacts', $column) && !array_key_exists($column, $payload)) {
                $payload[$column] = $value;
            }
        } catch (\Throwable $e) {
            // ignore
        }
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
