<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use App\Models\ContactRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BookController extends Controller
{
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

        if (!empty($validated['notes'])) {
            $tenantId = $client->tenant_id ?? ($user?->tenant_id ?? 1);
            Note::create([
                'contact_id' => $client->id,
                'note'       => trim($validated['notes']),
                'created_by' => $user?->id ?? $client->created_by,
                'tenant_id'  => $tenantId,
            ]);
        }

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

    /**
     * ✅ BULK IMPORT (CSV/XLSX/XLS) for Book of Business.
     * Requirements:
     * - Create 1 contact card per row.
     * - Blank/optional sections must NOT fail import.
     * - Skip only truly empty rows OR rows with no identifier at all.
     * - Count created + updated so success reflects actual work.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $user = Auth::user();
        if (!$user) abort(403);

        $file = $request->file('file');
        $rows = $this->parseSpreadsheetToRows($file->getRealPath(), $file->getClientOriginalExtension());

        if (count($rows) === 0) {
            $ext = strtolower((string)$file->getClientOriginalExtension());
            $hint = ($ext === 'xlsx' || $ext === 'xls')
                ? 'No rows were parsed. If this was an Excel file, confirm PhpSpreadsheet is installed (phpoffice/phpspreadsheet).'
                : 'No rows were parsed. Confirm the file has a header row and at least one data row.';
            return redirect()
                ->route('book.index')
                ->with('import_error', "Upload worked, but no rows were found in the file. {$hint}");
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $firstId = null;

        foreach ($rows as $rowIndex => $row) {
            // Flexible header mapping (ALL optional)
            $first = $this->getRowVal($row, ['first_name','firstname','first name','fname']);
            $last  = $this->getRowVal($row, ['last_name','lastname','last name','lname']);
            $email = $this->getRowVal($row, ['email','e-mail','email_address']);
            $phone = $this->getRowVal($row, ['phone','mobile','cell','phone_number']);

            // Skip truly empty / unusable row (no identifier at all)
            if ($this->isBlankIdentifierRow($first, $last, $email, $phone)) {
                $skipped++;
                continue;
            }

            $payload = [
                'first_name'    => $this->cleanStr($first),
                'last_name'     => $this->cleanStr($last),
                'email'         => $this->cleanStr($email),
                'phone'         => $this->cleanStr($phone),

                'city'          => $this->cleanStr($this->getRowVal($row, ['city'])),
                'state'         => $this->cleanStr($this->getRowVal($row, ['state'])),
                'postal_code'   => $this->cleanStr($this->getRowVal($row, ['postal_code','zip','zipcode','zip code'])),
                'address_line1' => $this->cleanStr($this->getRowVal($row, ['address','address_line1','address 1','street','street address'])),
                'address_line2' => $this->cleanStr($this->getRowVal($row, ['address_line2','address 2','apt','unit'])),

                // Book/policy fields (optional)
                'carrier'          => $this->cleanStr($this->getRowVal($row, ['carrier'])),
                'policy_type'      => $this->cleanStr($this->getRowVal($row, ['policy_type','policy type','type'])),
                'face_amount'      => $this->toNumber($this->getRowVal($row, ['face_amount','face amount','coverage','coverage amount'])),
                'premium_amount'   => $this->toNumber($this->getRowVal($row, ['premium_amount','premium','monthly premium','premium amount'])),
                'premium_due_text' => $this->cleanStr($this->getRowVal($row, ['premium_due_text','premium due','due','draft day'])),
                'policy_issue_date'=> $this->toDate($this->getRowVal($row, ['policy_issue_date','issue date','policy issue'])),
                'premium_due_date' => $this->toDate($this->getRowVal($row, ['premium_due_date','draft date','due date'])),

                'date_of_birth' => $this->toDate($this->getRowVal($row, ['date_of_birth','dob','birthdate','date of birth'])),
                'anniversary'   => $this->toDate($this->getRowVal($row, ['anniversary'])),
            ];

            // Remove empty strings (keep nulls out too)
            foreach ($payload as $k => $v) {
                if ($v === '' || $v === null) unset($payload[$k]);
            }

            $payload['contact_type']        = 'book';
            $payload['created_by']          = $user->id;
            $payload['in_book_of_business'] = true;

            // If schema has these, set them safely
            $this->setIfHasColumn($payload, 'agency_id', $user->agency_id ?? null);
            $this->setIfHasColumn($payload, 'tenant_id', $user->tenant_id ?? null);

            // Find existing: prefer email match, else phone match (if present)
            $client = null;

            if (!empty($payload['email'])) {
                $client = Contact::query()->where('email', $payload['email'])->first();
            } elseif (!empty($payload['phone'])) {
                $client = Contact::query()->where('phone', $payload['phone'])->first();
            }

            if ($client) {
                $client->fill($payload);
                $client->contact_type = 'book';
                $client->in_book_of_business = true;
                $client->save();
                $updated++;
            } else {
                $client = Contact::create($payload);
                $client->in_book_of_business = true;
                $client->save();
                $created++;
            }

            if (!$firstId) $firstId = $client->id;

            // Notes column (optional)
            $noteText = $this->getRowVal($row, ['notes','note']);
            if ($noteText) {
                $tenantId = $client->tenant_id ?? ($user->tenant_id ?? 1);
                Note::create([
                    'contact_id' => $client->id,
                    'note'       => trim((string)$noteText),
                    'created_by' => $user->id,
                    'tenant_id'  => $tenantId,
                ]);
            }

            // Optional relations from row
            $this->importRelationsFromRow($client, $row, $user);
        }

        $processed = $created + $updated;

        if ($processed === 0) {
            return redirect()
                ->route('book.index')
                ->with('import_error', "Upload worked, but 0 contacts were created/updated. Skipped {$skipped} rows (usually blank or no identifier).");
        }

        return redirect()
            ->route('book.index', $firstId ? ['selected' => $firstId] : [])
            ->with('import_success', "Import complete: {$created} created, {$updated} updated. Skipped {$skipped} rows.");
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
            // If schema call fails for any reason, do nothing
        }
    }

    private function importRelationsFromRow(Contact $client, array $row, $user): void
    {
        // Beneficiaries: up to 4
        for ($i = 1; $i <= 4; $i++) {
            $name = $this->getRowVal($row, ["beneficiary_{$i}_name", "beneficiary {$i} name", "beneficiary{$i} name"]);
            if ($name) {
                ContactRelation::create([
                    'contact_id'   => $client->id,
                    'type'         => 'beneficiary',
                    'name'         => $name,
                    'relationship' => $this->getRowVal($row, ["beneficiary_{$i}_relationship", "beneficiary {$i} relationship"]),
                    'phone'        => $this->getRowVal($row, ["beneficiary_{$i}_phone", "beneficiary {$i} phone"]),
                    'contacted'    => 0,
                    'tenant_id'    => $client->tenant_id ?? ($user?->tenant_id),
                    'created_by'   => $user?->id ?? $client->created_by,
                    'agency_id'    => $client->agency_id,
                ]);
            }
        }

        // Emergency: up to 3
        for ($i = 1; $i <= 3; $i++) {
            $name = $this->getRowVal($row, ["emergency_{$i}_name", "emergency {$i} name", "emergency{$i} name"]);
            if ($name) {
                ContactRelation::create([
                    'contact_id'   => $client->id,
                    'type'         => 'emergency',
                    'name'         => $name,
                    'relationship' => $this->getRowVal($row, ["emergency_{$i}_relationship", "emergency {$i} relationship"]),
                    'phone'        => $this->getRowVal($row, ["emergency_{$i}_phone", "emergency {$i} phone"]),
                    'contacted'    => 0,
                    'tenant_id'    => $client->tenant_id ?? ($user?->tenant_id),
                    'created_by'   => $user?->id ?? $client->created_by,
                    'agency_id'    => $client->agency_id,
                ]);
            }
        }
    }

    private function saveRelations(Request $request, Contact $client, string $type)
    {
        $key  = $type === 'beneficiary' ? 'beneficiaries' : 'emergency_contacts';
        $user = Auth::user();

        if (!$request->has($key)) return;

        foreach ($request->$key as $row) {
            if (!isset($row['name']) || trim($row['name']) === '') continue;

            if (!empty($row['id'])) {
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
                'tenant_id'    => $client->tenant_id ?? $user?->tenant_id,
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

        $tenantId = $client->tenant_id ?? (Auth::user()->tenant_id ?? 1);

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
        if ($note->contact_id !== $client->id) abort(404);

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
        if ($note->contact_id !== $client->id) abort(404);

        $note->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function sendToService(Contact $client)
    {
        $user = Auth::user();
        if ($user && $client->agency_id !== $user->agency_id) abort(403, 'Unauthorized');

        $client->contact_type        = 'service';
        $client->in_book_of_business = true;
        $client->service_status      = null;
        $client->service_archived_at = null;
        $client->save();

        return redirect()->route('service.index', ['selected' => $client->id]);
    }

    public function deleteRelation(Request $request, Contact $client, ContactRelation $relation)
    {
        if ($relation->contact_id !== $client->id) abort(403);

        $relation->delete();

        return response()->json(['success' => true]);
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

        // XLSX/XLS via PhpSpreadsheet if available
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

        // If you get here, xlsx/xls support isn't installed
        return [];
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) return [];

        $headers = fgetcsv($handle);
        if (!$headers) return [];

        // Strip UTF-8 BOM from first header cell if present
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

    private function toNumber($val): ?float
    {
        if ($val === null || $val === '') return null;
        $clean = preg_replace('/[^0-9\.\-]/', '', (string)$val);
        if ($clean === '' || $clean === null) return null;
        $n = (float)$clean;
        return is_nan($n) ? null : $n;
    }

    private function toDate($val): ?string
    {
        if ($val === null || $val === '') return null;

        if (is_numeric($val)) {
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
