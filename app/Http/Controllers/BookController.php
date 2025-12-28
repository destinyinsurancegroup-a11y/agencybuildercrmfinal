<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use App\Models\ContactRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

// ✅ Spreadsheet reader (XLSX/CSV)
use PhpOffice\PhpSpreadsheet\IOFactory;

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
    | ✅ IMPORT – BULK UPLOAD CSV/XLSX INTO BOOK OF BUSINESS
    |--------------------------------------------------------------------------
    |
    | What it does:
    | - Reads header row
    | - Maps columns by common names (case-insensitive)
    | - Creates/updates contacts as Book records
    | - Adds a Note if "Notes" column exists
    |
    | Expected columns (any subset is OK):
    | First Name, Last Name, Email, Phone,
    | Address Line1, Address Line2, City, State, Postal Code,
    | Date of Birth, Anniversary,
    | Carrier, Policy Type, Face Amount, Premium Amount,
    | Premium Due Date, Policy Issue Date, Premium Due (Text),
    | Notes
    |
    | Extra columns are ignored.
    */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $file = $request->file('file');

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true); // keyed by column letters
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not read spreadsheet. Make sure it is a valid CSV/XLSX file.');
        }

        if (count($rows) < 2) {
            return back()->with('error', 'Spreadsheet is empty or missing rows.');
        }

        // ---- Build header map (A,B,C...) => normalized header key
        $headerRow = array_shift($rows);
        $headers = [];
        foreach ($headerRow as $col => $headerLabel) {
            $norm = $this->normalizeHeader($headerLabel);
            if ($norm !== '') {
                $headers[$col] = $norm;
            }
        }

        if (count($headers) === 0) {
            return back()->with('error', 'Could not detect header row. Row 1 must contain column names.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $firstImportedId = null;

        foreach ($rows as $r) {
            // Convert row from [A=>...,B=>...] to [normalized_header=>value]
            $data = [];
            foreach ($headers as $col => $key) {
                $data[$key] = $r[$col] ?? null;
            }

            // Require at least a name
            $firstName = trim((string)($data['first_name'] ?? ''));
            $lastName  = trim((string)($data['last_name'] ?? ''));

            if ($firstName === '' && $lastName === '') {
                $skipped++;
                continue;
            }

            // Map to Contact fields (only if columns exist)
            $payload = [];

            $payload['first_name'] = $firstName ?: null;
            $payload['last_name']  = $lastName ?: null;

            if (!empty($data['email'])) $payload['email'] = trim((string)$data['email']);
            if (!empty($data['phone'])) $payload['phone'] = trim((string)$data['phone']);

            if (!empty($data['address_line1'])) $payload['address_line1'] = trim((string)$data['address_line1']);
            if (!empty($data['address_line2'])) $payload['address_line2'] = trim((string)$data['address_line2']);
            if (!empty($data['city'])) $payload['city'] = trim((string)$data['city']);
            if (!empty($data['state'])) $payload['state'] = trim((string)$data['state']);
            if (!empty($data['postal_code'])) $payload['postal_code'] = trim((string)$data['postal_code']);

            if (!empty($data['date_of_birth'])) {
                $dob = $this->parseDate($data['date_of_birth']);
                if ($dob) $payload['date_of_birth'] = $dob;
            }

            if (!empty($data['anniversary'])) {
                $ann = $this->parseDate($data['anniversary']);
                if ($ann) $payload['anniversary'] = $ann;
            }

            if (!empty($data['carrier'])) $payload['carrier'] = trim((string)$data['carrier']);
            if (!empty($data['policy_type'])) $payload['policy_type'] = trim((string)$data['policy_type']);

            if (!empty($data['face_amount'])) $payload['face_amount'] = $this->parseNumber($data['face_amount']);
            if (!empty($data['premium_amount'])) $payload['premium_amount'] = $this->parseNumber($data['premium_amount']);

            if (!empty($data['premium_due_date'])) {
                $pdd = $this->parseDate($data['premium_due_date']);
                if ($pdd) $payload['premium_due_date'] = $pdd;
            }

            if (!empty($data['policy_issue_date'])) {
                $pid = $this->parseDate($data['policy_issue_date']);
                if ($pid) $payload['policy_issue_date'] = $pid;
            }

            if (!empty($data['premium_due_text'])) $payload['premium_due_text'] = trim((string)$data['premium_due_text']);

            // Force Book tagging
            $payload['contact_type'] = 'book';
            $payload['in_book_of_business'] = true;
            $payload['created_by'] = $user->id;

            // ✅ Upsert strategy:
            // Prefer email match; otherwise phone; otherwise create new.
            $query = Contact::query();
            if (!empty($payload['email'])) {
                $query->where('email', $payload['email']);
            } elseif (!empty($payload['phone'])) {
                $query->where('phone', $payload['phone']);
            } else {
                $query = null;
            }

            if ($query) {
                $existing = $query->first();
                if ($existing) {
                    // Update only non-empty fields
                    foreach ($payload as $k => $v) {
                        if ($v !== null && $v !== '') {
                            $existing->{$k} = $v;
                        }
                    }
                    $existing->save();
                    $client = $existing;
                    $updated++;
                } else {
                    $client = Contact::create($payload);
                    $created++;
                }
            } else {
                $client = Contact::create($payload);
                $created++;
            }

            if (!$firstImportedId && $client) {
                $firstImportedId = $client->id;
            }

            // Notes (optional)
            $noteText = trim((string)($data['notes'] ?? ''));
            if ($noteText !== '') {
                $tenantId = $client->tenant_id ?? ($user->tenant_id ?? 1);

                Note::create([
                    'contact_id' => $client->id,
                    'note'       => $noteText,
                    'created_by' => $user->id,
                    'tenant_id'  => $tenantId,
                ]);
            }
        }

        // Redirect back to Book and auto-open first imported record
        return redirect()
            ->route('book.index', ['selected' => $firstImportedId])
            ->with('success', "Import complete. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}");
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

        if (!$request->has($key)) {
            return;
        }

        foreach ($request->$key as $row) {
            if (!isset($row['name']) || trim($row['name']) === '') {
                continue;
            }

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

    // =========================================================
    // Helpers
    // =========================================================

    private function normalizeHeader($value): string
    {
        $v = trim((string)$value);
        if ($v === '') return '';

        $v = Str::lower($v);
        $v = preg_replace('/[^a-z0-9]+/i', '_', $v);
        $v = trim($v, '_');

        // Map common variants => canonical keys
        $map = [
            'first' => 'first_name',
            'first_name' => 'first_name',
            'firstname' => 'first_name',

            'last' => 'last_name',
            'last_name' => 'last_name',
            'lastname' => 'last_name',

            'email' => 'email',
            'e_mail' => 'email',

            'phone' => 'phone',
            'mobile' => 'phone',
            'cell' => 'phone',

            'address' => 'address_line1',
            'address_line1' => 'address_line1',
            'address1' => 'address_line1',

            'address_line2' => 'address_line2',
            'address2' => 'address_line2',

            'city' => 'city',
            'state' => 'state',
            'postal' => 'postal_code',
            'zip' => 'postal_code',
            'postal_code' => 'postal_code',

            'dob' => 'date_of_birth',
            'date_of_birth' => 'date_of_birth',

            'anniversary' => 'anniversary',

            'carrier' => 'carrier',
            'policy' => 'policy_type',
            'policy_type' => 'policy_type',

            'face_amount' => 'face_amount',
            'face' => 'face_amount',

            'premium' => 'premium_amount',
            'premium_amount' => 'premium_amount',

            'premium_due_date' => 'premium_due_date',
            'policy_issue_date' => 'policy_issue_date',
            'premium_due_text' => 'premium_due_text',

            'notes' => 'notes',
            'note' => 'notes',
        ];

        return $map[$v] ?? $v;
    }

    private function parseNumber($value): ?float
    {
        $v = trim((string)$value);
        if ($v === '') return null;

        // remove $ and commas
        $v = str_replace([',', '$'], '', $v);
        if (!is_numeric($v)) return null;

        return (float)$v;
    }

    private function parseDate($value): ?string
    {
        // Excel date numeric
        if (is_numeric($value)) {
            try {
                // PhpSpreadsheet stores Excel dates as serial numbers
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $v = trim((string)$value);
        if ($v === '') return null;

        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
