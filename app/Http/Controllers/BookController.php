<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use App\Models\ContactRelation;   // Unified relations table
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    /*
    |--------------------------------------------------------------------------
    | CREATE PANEL (AJAX)
    |--------------------------------------------------------------------------
    */
    public function createPanel()
    {
        return view('book.partials.create');
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

    /*
    |--------------------------------------------------------------------------
    | ✅ IMPORT – UPLOAD EXCEL INTO BOOK OF BUSINESS
    |--------------------------------------------------------------------------
    | Fixes: "BookController::import does not exist" 500
    |
    | Expects: <input type="file" name="file">
    |--------------------------------------------------------------------------
    */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $user = Auth::user();
        if (! $user) abort(403);

        // If you don't already have phpspreadsheet installed, this will show a clear message.
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return back()->with('error', 'Spreadsheet importer not installed. Run: composer require phpoffice/phpspreadsheet');
        }

        $tenantId = $user->tenant_id ?? 1;

        $created = 0;
        $updated = 0;
        $notesCreated = 0;

        $filePath = $request->file('file')->getRealPath();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

        // Use first worksheet for Book import
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, true, true);

        if (!is_array($rows) || count($rows) < 2) {
            return back()->with('error', 'Spreadsheet appears empty or missing data rows.');
        }

        // Map headers -> column letters (normalized)
        $headerRow = $rows[1] ?? [];
        $headerMap = [];
        foreach ($headerRow as $col => $val) {
            $norm = $this->normalizeHeader($val);
            if ($norm !== '') $headerMap[$norm] = $col;
        }

        $get = function(array $row, array $candidates) use ($headerMap) {
            foreach ($candidates as $name) {
                $norm = $this->normalizeHeader($name);
                if (isset($headerMap[$norm])) {
                    $col = $headerMap[$norm];
                    $v = $row[$col] ?? null;
                    $v = is_string($v) ? trim($v) : $v;
                    return ($v === '' ? null : $v);
                }
            }
            return null;
        };

        DB::beginTransaction();
        try {
            for ($i = 2; $i <= count($rows); $i++) {
                $row = $rows[$i] ?? null;
                if (!is_array($row)) continue;

                $firstName = $get($row, ['first_name','firstname','first']);
                $lastName  = $get($row, ['last_name','lastname','last']);
                $email     = $get($row, ['email']);
                $phone     = $get($row, ['phone','phone_number']);

                // Skip blank rows
                if (!$firstName && !$lastName && !$email && !$phone) continue;

                // Upsert key: email > phone > name combo
                $query = Contact::query();
                if ($email) {
                    $query->where('email', $email);
                } elseif ($phone) {
                    $query->where('phone', $phone);
                } else {
                    $query->where('first_name', $firstName ?? '')
                          ->where('last_name', $lastName ?? '');
                }

                $contact = $query->first();

                $payload = [
                    'first_name'        => $firstName,
                    'last_name'         => $lastName,
                    'email'             => $email,
                    'phone'             => $phone,

                    'address_line1'     => $get($row, ['address_line1','address1','street','address']),
                    'address_line2'     => $get($row, ['address_line2','address2','apt','unit']),
                    'city'              => $get($row, ['city']),
                    'state'             => $get($row, ['state']),
                    'postal_code'       => $get($row, ['postal_code','zip','zipcode']),

                    'date_of_birth'     => $this->coerceDate($get($row, ['date_of_birth','dob','birthdate'])),
                    'anniversary'       => $this->coerceDate($get($row, ['anniversary'])),

                    'carrier'           => $get($row, ['carrier']),
                    'policy_type'       => $get($row, ['policy_type','policy','policytype']),
                    'face_amount'       => $this->coerceNumber($get($row, ['face_amount','face','coverage','death_benefit'])),
                    'premium_amount'    => $this->coerceNumber($get($row, ['premium_amount','premium','monthly_premium'])),
                    'premium_due_date'  => $this->coerceDate($get($row, ['premium_due_date','due_date'])),
                    'policy_issue_date' => $this->coerceDate($get($row, ['policy_issue_date','issue_date'])),
                    'premium_due_text'  => $get($row, ['premium_due_text','due_text','draft_day']),
                ];

                // Enforce Book flags on import
                $payload['contact_type'] = 'book';
                $payload['in_book_of_business'] = true;
                $payload['created_by'] = $user->id;

                // Remove nulls so we don't overwrite existing values with blanks
                $payload = array_filter($payload, fn($v) => $v !== null);

                if ($contact) {
                    $contact->fill($payload);
                    $contact->save();
                    $updated++;
                } else {
                    $contact = Contact::create($payload);
                    $created++;
                }

                // ✅ Import relations if present (Beneficiaries / Emergency Contacts)
                $this->importRelationsFromRow($row, $headerMap, $contact, $user->id, $tenantId);

                // ✅ Optional note (Notes column)
                $notesBody = $get($row, ['notes','note']);
                if ($notesBody) {
                    Note::create([
                        'contact_id' => $contact->id,
                        'note'       => trim((string)$notesBody),
                        'created_by' => $user->id,
                        'tenant_id'  => $tenantId,
                    ]);
                    $notesCreated++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        return back()->with('success', "Book import complete. Created: {$created}, Updated: {$updated}, Notes: {$notesCreated}");
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW PANEL (AJAX ONLY)
    |--------------------------------------------------------------------------
    */
    public function show(Contact $client)
    {
        if (request()->ajax()) {
            return view('book.partials.details', compact('client'));
        }

        return abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT PANEL (AJAX)
    |--------------------------------------------------------------------------
    */
    public function editPanel(Contact $client)
    {
        return view('book.partials.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | FULL PAGE EDIT
    |--------------------------------------------------------------------------
    */
    public function edit(Contact $client)
    {
        return view('book.edit', compact('client'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE – SAVES EVERYTHING INCLUDING DESTINY RELATIONS
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

        $client->contact_type        = 'book';
        $client->in_book_of_business = true;
        $client->save();

        $this->saveRelations($request, $client, 'beneficiary');
        $this->saveRelations($request, $client, 'emergency');

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE RELATIONS (Unified Destiny Logic)
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | NOTES – ADD (BOOK + SERVICE + LEADS)
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | NOTES – UPDATE
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | NOTES – DELETE
    |--------------------------------------------------------------------------
    */
    public function destroyNote(Contact $client, Note $note)
    {
        if ($note->contact_id !== $client->id) abort(404);

        $note->delete();

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | SEND TO SERVICE
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | DELETE RELATIONS
    |--------------------------------------------------------------------------
    */
    public function deleteRelation(Request $request, Contact $client, ContactRelation $relation)
    {
        if ($relation->contact_id !== $client->id) abort(403);

        $relation->delete();

        return response()->json(['success' => true]);
    }

    // ======================================================================
    // Import Helpers
    // ======================================================================

    private function normalizeHeader($v): string
    {
        $s = strtolower(trim((string)$v));
        $s = preg_replace('/[^a-z0-9]+/', '_', $s);
        $s = trim($s, '_');
        return $s ?: '';
    }

    private function coerceNumber($v): ?float
    {
        if ($v === null) return null;
        if (is_numeric($v)) return (float)$v;

        $s = preg_replace('/[^0-9.\-]/', '', (string)$v);
        if ($s === '' || !is_numeric($s)) return null;

        return (float)$s;
    }

    private function coerceDate($v): ?string
    {
        if ($v === null || $v === '') return null;

        if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v;
        }

        if (is_numeric($v)) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $ts = strtotime((string)$v);
        if ($ts === false) return null;

        return date('Y-m-d', $ts);
    }

    /**
     * Import named relations from spreadsheet row.
     * Supports up to 5 each:
     *  beneficiary_1_name, beneficiary_1_phone, beneficiary_1_relationship
     *  emergency_contact_1_name, emergency_contact_1_phone, emergency_contact_1_relationship
     */
    private function importRelationsFromRow(array $row, array $headerMap, Contact $client, int $userId, int $tenantId): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $bName = $this->getByHeader($row, $headerMap, [
                "beneficiary_{$i}_name", "beneficiary{$i}_name", "beneficiary_{$i}", "beneficiary{$i}",
            ]);
            $bPhone = $this->getByHeader($row, $headerMap, [
                "beneficiary_{$i}_phone", "beneficiary{$i}_phone",
            ]);
            $bRel = $this->getByHeader($row, $headerMap, [
                "beneficiary_{$i}_relationship", "beneficiary{$i}_relationship", "beneficiary_{$i}_relation", "beneficiary{$i}_relation",
            ]);

            if ($bName) {
                ContactRelation::create([
                    'contact_id'   => $client->id,
                    'type'         => 'beneficiary',
                    'name'         => (string)$bName,
                    'relationship' => $bRel ? (string)$bRel : null,
                    'phone'        => $bPhone ? (string)$bPhone : null,
                    'contacted'    => 0,
                    'tenant_id'    => $client->tenant_id ?? $tenantId,
                    'created_by'   => $userId,
                    'agency_id'    => $client->agency_id,
                ]);
            }

            $eName = $this->getByHeader($row, $headerMap, [
                "emergency_contact_{$i}_name", "emergencycontact_{$i}_name", "emergency_{$i}_name", "emergency{$i}_name",
                "emergency_contact_{$i}", "emergency{$i}",
            ]);
            $ePhone = $this->getByHeader($row, $headerMap, [
                "emergency_contact_{$i}_phone", "emergencycontact_{$i}_phone", "emergency_{$i}_phone", "emergency{$i}_phone",
            ]);
            $eRel = $this->getByHeader($row, $headerMap, [
                "emergency_contact_{$i}_relationship", "emergencycontact_{$i}_relationship", "emergency_{$i}_relationship", "emergency{$i}_relationship",
            ]);

            if ($eName) {
                ContactRelation::create([
                    'contact_id'   => $client->id,
                    'type'         => 'emergency',
                    'name'         => (string)$eName,
                    'relationship' => $eRel ? (string)$eRel : null,
                    'phone'        => $ePhone ? (string)$ePhone : null,
                    'contacted'    => 0,
                    'tenant_id'    => $client->tenant_id ?? $tenantId,
                    'created_by'   => $userId,
                    'agency_id'    => $client->agency_id,
                ]);
            }
        }
    }

    private function getByHeader(array $row, array $headerMap, array $possibleHeaders)
    {
        foreach ($possibleHeaders as $h) {
            $norm = $this->normalizeHeader($h);
            if (!isset($headerMap[$norm])) continue;

            $col = $headerMap[$norm];
            $v = $row[$col] ?? null;
            $v = is_string($v) ? trim($v) : $v;

            if ($v === '' || $v === null) continue;
            return $v;
        }
        return null;
    }
}
