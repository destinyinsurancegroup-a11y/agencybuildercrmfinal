<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use App\Models\ContactRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX – LEFT LIST + RIGHT PANEL
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = Contact::query()
            ->where(function ($q) {
                $q->where('in_book_of_business', true)
                  ->orWhere(function ($q2) {
                      $q2->where('contact_type', 'book')
                         ->orWhere('contact_type', 'client')
                         ->orWhere('status', 'Sold');
                  });
            });

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw("
            CASE
                WHEN contact_type = 'service' AND service_archived_at IS NULL THEN 0
                ELSE 1
            END
        ")
        ->orderBy('last_name')
        ->orderBy('first_name');

        $clients  = $query->get();
        $selected = $request->get('selected');

        return view('book.index', compact('clients', 'selected'));
    }

    public function createPanel()
    {
        return view('book.partials.create');
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ IMPORT – BULK UPLOAD BOOK OF BUSINESS
    |--------------------------------------------------------------------------
    | Route already exists in web.php:
    |   POST /book/import -> BookController@import  (name: book.import)
    |
    | Supports:
    | - XLSX / XLS / CSV
    | - Flexible headers (First Name, first_name, etc.)
    | - Notes column -> creates Note rows
    | - Beneficiaries / Emergency Contacts column (string lists) -> ContactRelation
    |
    | Returns:
    | - JSON if request expects JSON / AJAX
    | - Redirect back otherwise
    */
    public function import(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // Accept multiple possible file input names to avoid "silent no-op"
        $file =
            $request->file('file')
            ?? $request->file('upload')
            ?? $request->file('excel')
            ?? $request->file('spreadsheet');

        if (! $file) {
            return $this->importRespond($request, false, 'No file received. Make sure your input name is file/upload/excel/spreadsheet.', [
                'imported' => 0,
                'skipped'  => 0,
            ], 422);
        }

        // Basic validation (don’t block if mime is weird; we still try to parse)
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (! in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return $this->importRespond($request, false, 'Unsupported file type. Upload .xlsx, .xls, or .csv', [
                'imported' => 0,
                'skipped'  => 0,
            ], 422);
        }

        // PhpSpreadsheet is the most reliable path here
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return $this->importRespond($request, false,
                'Import engine missing (PhpSpreadsheet not installed). Install phpoffice/phpspreadsheet or maatwebsite/excel.',
                ['imported' => 0, 'skipped' => 0],
                500
            );
        }

        try {
            $rows = $this->readSpreadsheetToRows($file->getRealPath(), $ext);
        } catch (\Throwable $e) {
            return $this->importRespond($request, false,
                'Could not read spreadsheet: ' . $e->getMessage(),
                ['imported' => 0, 'skipped' => 0],
                500
            );
        }

        if (count($rows) === 0) {
            return $this->importRespond($request, false,
                'Spreadsheet has no data rows.',
                ['imported' => 0, 'skipped' => 0],
                422
            );
        }

        $imported = 0;
        $skipped  = 0;
        $firstImportedId = null;

        foreach ($rows as $row) {
            // We require at least a name to import
            $firstName = trim((string)($row['first_name'] ?? ''));
            $lastName  = trim((string)($row['last_name'] ?? ''));

            if ($firstName === '' && $lastName === '') {
                $skipped++;
                continue;
            }

            // Build contact payload
            $payload = [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $this->nullIfBlank($row['email'] ?? null),
                'phone'      => $this->nullIfBlank($row['phone'] ?? null),

                'address_line1' => $this->nullIfBlank($row['address_line1'] ?? $row['address'] ?? null),
                'address_line2' => $this->nullIfBlank($row['address_line2'] ?? null),
                'city'          => $this->nullIfBlank($row['city'] ?? null),
                'state'         => $this->nullIfBlank($row['state'] ?? null),
                'postal_code'   => $this->nullIfBlank($row['postal_code'] ?? $row['zip'] ?? null),

                'date_of_birth' => $this->parseDateOrNull($row['date_of_birth'] ?? $row['dob'] ?? null),
                'anniversary'   => $this->parseDateOrNull($row['anniversary'] ?? null),

                'carrier'           => $this->nullIfBlank($row['carrier'] ?? null),
                'policy_type'       => $this->nullIfBlank($row['policy_type'] ?? null),
                'face_amount'       => $this->parseNumberOrNull($row['face_amount'] ?? null),
                'premium_amount'    => $this->parseNumberOrNull($row['premium_amount'] ?? $row['monthly_premium'] ?? null),
                'premium_due_date'  => $this->parseDateOrNull($row['premium_due_date'] ?? null),
                'policy_issue_date' => $this->parseDateOrNull($row['policy_issue_date'] ?? $row['initial_draft_date'] ?? null),
                'premium_due_text'  => $this->nullIfBlank($row['premium_due_text'] ?? $row['monthly_due_text'] ?? null),

                'contact_type' => 'book',
                'created_by'   => $user->id,
            ];

            // If your DB requires tenant_id in places, keep it safe
            if (array_key_exists('tenant_id', (new Contact())->getAttributes())) {
                $payload['tenant_id'] = $user->tenant_id ?? 1;
            }

            // Create contact
            $client = Contact::create($payload);
            $client->in_book_of_business = true;
            $client->save();

            if (! $firstImportedId) {
                $firstImportedId = $client->id;
            }

            // Notes
            $noteText = trim((string)($row['notes'] ?? $row['note'] ?? ''));
            if ($noteText !== '') {
                $tenantId = $client->tenant_id ?? ($user->tenant_id ?? 1);
                Note::create([
                    'contact_id' => $client->id,
                    'note'       => $noteText,
                    'created_by' => $user->id,
                    'tenant_id'  => $tenantId,
                ]);
            }

            // Beneficiaries / Emergency contacts
            // Supports columns like:
            // - beneficiaries
            // - beneficiary_names
            // - emergency_contacts
            // - emergency_contact_names
            $benefStr = (string)($row['beneficiaries'] ?? $row['beneficiary_names'] ?? '');
            $emerStr  = (string)($row['emergency_contacts'] ?? $row['emergency_contact_names'] ?? '');

            $this->createRelationsFromString($client, 'beneficiary', $benefStr, $user);
            $this->createRelationsFromString($client, 'emergency', $emerStr, $user);

            $imported++;
        }

        // If the UI is AJAX, return JSON so the front-end can refresh list.
        // If not AJAX, redirect to book.index and auto-select first imported record.
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => "Imported {$imported} contacts. Skipped {$skipped}.",
                'imported' => $imported,
                'skipped'  => $skipped,
                'select'   => $firstImportedId,
            ]);
        }

        return redirect()->route('book.index', [
            'selected' => $firstImportedId,
        ])->with('success', "Imported {$imported} contacts. Skipped {$skipped}.");
    }

    private function importRespond(Request $request, bool $success, string $message, array $meta = [], int $code = 200)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(array_merge([
                'success' => $success,
                'message' => $message,
            ], $meta), $code);
        }

        if ($success) {
            return back()->with('success', $message);
        }
        return back()->with('error', $message);
    }

    private function readSpreadsheetToRows(string $path, string $ext): array
    {
        $readerType = match ($ext) {
            'csv'  => 'Csv',
            'xls'  => 'Xls',
            default => 'Xlsx',
        };

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($readerType);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $raw = $sheet->toArray(null, true, true, true);
        if (count($raw) < 2) {
            return [];
        }

        // Header row
        $headerRow = array_shift($raw);
        $headers = [];
        foreach ($headerRow as $col => $val) {
            $headers[$col] = $this->normalizeHeader((string)$val);
        }

        // Convert data rows to associative arrays keyed by normalized headers
        $rows = [];
        foreach ($raw as $r) {
            $assoc = [];
            foreach ($headers as $col => $h) {
                if ($h === '') continue;
                $assoc[$h] = $r[$col] ?? null;
            }

            // Map common aliases into canonical keys
            $assoc = $this->applyHeaderAliases($assoc);

            $rows[] = $assoc;
        }

        return $rows;
    }

    private function normalizeHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = preg_replace('/[^a-z0-9]+/i', '_', $h) ?? '';
        $h = trim($h, '_');
        return $h;
    }

    private function applyHeaderAliases(array $row): array
    {
        $alias = function (array $candidates) use ($row) {
            foreach ($candidates as $k) {
                if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                    return $row[$k];
                }
            }
            return null;
        };

        // Canonical keys expected by importer
        $row['first_name'] = $row['first_name'] ?? $alias(['firstname', 'first', 'first_name']);
        $row['last_name']  = $row['last_name']  ?? $alias(['lastname', 'last', 'last_name']);
        $row['email']      = $row['email']      ?? $alias(['e_mail', 'email_address']);
        $row['phone']      = $row['phone']      ?? $alias(['phone_number', 'mobile', 'cell']);

        $row['postal_code'] = $row['postal_code'] ?? $alias(['zip', 'zipcode']);
        $row['address']     = $row['address']     ?? $alias(['address_line1', 'street']);

        $row['date_of_birth'] = $row['date_of_birth'] ?? $alias(['dob', 'birthdate']);

        $row['policy_type']    = $row['policy_type'] ?? $alias(['policy', 'type']);
        $row['face_amount']    = $row['face_amount'] ?? $alias(['face', 'coverage', 'coverage_amount']);
        $row['premium_amount'] = $row['premium_amount'] ?? $alias(['monthly_premium', 'premium']);

        $row['premium_due_text'] = $row['premium_due_text'] ?? $alias(['monthly_due_text', 'due_text']);

        // Beneficiary / emergency columns
        $row['beneficiaries'] = $row['beneficiaries'] ?? $alias(['beneficiary', 'beneficiary_names']);
        $row['emergency_contacts'] = $row['emergency_contacts'] ?? $alias(['emergency', 'emergency_contact_names']);

        // Notes
        $row['notes'] = $row['notes'] ?? $alias(['note', 'comments']);

        return $row;
    }

    private function createRelationsFromString(Contact $client, string $type, string $raw, $user): void
    {
        $raw = trim($raw);
        if ($raw === '') return;

        // Split on semicolons or pipes first (more reliable than commas)
        $parts = preg_split('/[;|]+/', $raw) ?: [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') continue;

            // Accept formats like:
            // "Sabrina Metz - Daughter - 231-342-2332"
            // "Sabrina Metz (Daughter, 231-342-2332)"
            $name = $p;
            $relationship = null;
            $phone = null;

            if (preg_match('/^(.*?)\((.*?)\)\s*$/', $p, $m)) {
                $name = trim($m[1]);
                $inside = trim($m[2]);
                $insideParts = array_map('trim', preg_split('/[,]+/', $inside) ?: []);
                $relationship = $insideParts[0] ?? null;
                $phone = $insideParts[1] ?? null;
            } elseif (str_contains($p, '-')) {
                $dashParts = array_map('trim', explode('-', $p));
                $name = $dashParts[0] ?? $p;
                $relationship = $dashParts[1] ?? null;
                $phone = $dashParts[2] ?? null;
            }

            if (trim((string)$name) === '') continue;

            ContactRelation::create([
                'contact_id'   => $client->id,
                'type'         => $type,
                'name'         => $name,
                'relationship' => $relationship,
                'phone'        => $phone,
                'contacted'    => 0,
                'tenant_id'    => $client->tenant_id ?? ($user?->tenant_id ?? 1),
                'created_by'   => $user?->id ?? $client->created_by,
                'agency_id'    => $client->agency_id,
            ]);
        }
    }

    private function nullIfBlank($v)
    {
        $v = is_string($v) ? trim($v) : $v;
        return ($v === '' || $v === null) ? null : $v;
    }

    private function parseNumberOrNull($v)
    {
        if ($v === null) return null;
        if (is_numeric($v)) return $v;
        $s = trim((string)$v);
        if ($s === '') return null;
        $s = preg_replace('/[^0-9.\-]/', '', $s);
        if ($s === '' || $s === null) return null;
        return is_numeric($s) ? (float)$s : null;
    }

    private function parseDateOrNull($v)
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;

        // Try strtotime formats
        $ts = strtotime($s);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | STORE – NEW BOOK CLIENT
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

        $user = Auth::user();

        $validated['contact_type'] = 'book';
        $validated['created_by']   = $user?->id;

        $client = Contact::create($validated);

        $client->in_book_of_business = true;
        $client->save();

        $this->saveRelations($request, $client, 'beneficiary');
        $this->saveRelations($request, $client, 'emergency');

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    public function show(Contact $client)
    {
        if (request()->ajax()) {
            return view('book.partials.details', compact('client'));
        }
        return abort(404);
    }

    public function editPanel(Contact $client)
    {
        return view('book.partials.edit', compact('client'));
    }

    public function edit(Contact $client)
    {
        return view('book.edit', compact('client'));
    }

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

        $client->contact_type        = 'book';
        $client->in_book_of_business = true;
        $client->save();

        $this->saveRelations($request, $client, 'beneficiary');
        $this->saveRelations($request, $client, 'emergency');

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    private function saveRelations(Request $request, Contact $client, string $type)
    {
        $key  = $type === 'beneficiary' ? 'beneficiaries' : 'emergency_contacts';
        $user = Auth::user();

        if (! $request->has($key)) {
            return;
        }

        foreach ($request->$key as $row) {
            if (! isset($row['name']) || trim($row['name']) === '') {
                continue;
            }

            if (! empty($row['id'])) {
                $relation = ContactRelation::where('id', $row['id'])
                    ->where('contact_id', $client->id)
                    ->where('type', $type)
                    ->first();

                if ($relation) {
                    $relation->update([
                        'name'         => $row['name'],
                        'relationship' => $row['relationship'] ?? null,
                        'phone'        => $row['phone'] ?? null,
                        'contacted'    => $row['contacted'] ?? 0,
                    ]);
                }
                continue;
            }

            ContactRelation::create([
                'contact_id'   => $client->id,
                'type'         => $type,
                'name'         => $row['name'],
                'relationship' => $row['relationship'] ?? null,
                'phone'        => $row['phone'] ?? null,
                'contacted'    => $row['contacted'] ?? 0,
                'tenant_id'    => $client->tenant_id ?? $user?->tenant_id ?? 1,
                'created_by'   => $user?->id ?? $client->created_by,
                'agency_id'    => $client->agency_id,
            ]);
        }
    }

    public function storeNote(Request $request, Contact $client)
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $tenantId = $client->tenant_id
            ?? (Auth::user()->tenant_id ?? 1);

        $note = Note::create([
            'contact_id' => $client->id,
            'note'       => trim($data['body']),
            'created_by' => Auth::id() ?? $client->created_by,
            'tenant_id'  => $tenantId,
        ]);

        return response()->json([
            'success' => true,
            'note'    => $note,
        ], 201);
    }

    public function updateNote(Request $request, Contact $client, Note $note)
    {
        if ($note->contact_id !== $client->id) {
            abort(404);
        }

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $note->update([
            'note' => trim($data['body']),
        ]);

        return response()->json([
            'success' => true,
            'note'    => $note->fresh(),
        ]);
    }

    public function destroyNote(Contact $client, Note $note)
    {
        if ($note->contact_id !== $client->id) {
            abort(404);
        }

        $note->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function sendToService(Contact $client)
    {
        $user = Auth::user();

        if ($user && $client->agency_id !== $user->agency_id) {
            abort(403, 'Unauthorized');
        }

        $client->contact_type        = 'service';
        $client->in_book_of_business = true;
        $client->service_status      = null;
        $client->service_archived_at = null;
        $client->save();

        return redirect()->route('service.index', ['selected' => $client->id]);
    }

    public function deleteRelation(Request $request, Contact $client, ContactRelation $relation)
    {
        if ($relation->contact_id !== $client->id) {
            abort(403);
        }

        $relation->delete();

        return response()->json(['success' => true]);
    }
}
