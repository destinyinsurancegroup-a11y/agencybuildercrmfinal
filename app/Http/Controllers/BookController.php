<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use App\Models\ContactRelation;
use App\Models\ContactPolicy; // ✅ multi-policy rows
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

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

        $clients = $query->get();

        // ✅ allow both ?selected= and ?contact_id=
        $selected = $request->get('selected') ?: $request->get('contact_id');

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

            // Legacy single-policy fields (keep for backward compatibility)
            'carrier'           => 'nullable|string|max:255',
            'policy_type'       => 'nullable|string|max:255',
            'face_amount'       => 'nullable|numeric',
            'premium_amount'    => 'nullable|numeric',
            'premium_due_date'  => 'nullable|date', // legacy field on contacts table (if you still use it)
            'policy_issue_date' => 'nullable|date',
            'premium_due_text'  => 'nullable|string|max:255',

            'notes'             => 'nullable|string',

            // ✅ multi-policy array from Book edit UI (NOT required)
            'policies'                      => 'nullable|array',
            'policies.*.id'                 => 'nullable|integer',
            'policies.*.carrier'            => 'nullable|string|max:255',
            'policies.*.policy_type'        => 'nullable|string|max:255',
            'policies.*.face_amount'        => 'nullable|numeric',
            'policies.*.premium_amount'     => 'nullable|numeric',
            // ✅ REMOVED: policies.*.premium_due_date (you removed it from UI)
            'policies.*.policy_issue_date'  => 'nullable|date',
            'policies.*.premium_due_text'   => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        // ✅ NEVER pass policies/notes into Contact::create()
        $contactData = Arr::except($validated, ['policies', 'notes']);

        $contactData['contact_type'] = 'book';
        $contactData['created_by']   = $user?->id;

        $client = Contact::create($contactData);
        $client->in_book_of_business = true;
        $client->save();

        $this->saveRelations($request, $client, 'beneficiary');
        $this->saveRelations($request, $client, 'emergency');

        // ✅ Save multi policies ONLY for Book of Business
        $this->savePolicies($request, $client);

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
        if (!request()->ajax()) {
            return redirect()->route('book.index', ['selected' => $client->id]);
        }

        return view('book.partials.details', compact('client'));
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

            // Legacy single-policy fields (keep for backward compatibility)
            'carrier'           => 'nullable|string|max:255',
            'policy_type'       => 'nullable|string|max:255',
            'face_amount'       => 'nullable|numeric',
            'premium_amount'    => 'nullable|numeric',
            'premium_due_date'  => 'nullable|date', // legacy field on contacts table (if you still use it)
            'policy_issue_date' => 'nullable|date',
            'premium_due_text'  => 'nullable|string|max:255',

            // ✅ multi-policy array from Book edit UI (NOT required)
            'policies'                      => 'nullable|array',
            'policies.*.id'                 => 'nullable|integer',
            'policies.*.carrier'            => 'nullable|string|max:255',
            'policies.*.policy_type'        => 'nullable|string|max:255',
            'policies.*.face_amount'        => 'nullable|numeric',
            'policies.*.premium_amount'     => 'nullable|numeric',
            // ✅ REMOVED: policies.*.premium_due_date
            'policies.*.policy_issue_date'  => 'nullable|date',
            'policies.*.premium_due_text'   => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            if ($key === 'policies') continue;

            if ($value !== null && $value !== '') {
                $client->{$key} = $value;
            }
        }

        $client->contact_type        = 'book';
        $client->in_book_of_business = true;
        $client->save();

        $this->saveRelations($request, $client, 'beneficiary');
        $this->saveRelations($request, $client, 'emergency');

        // ✅ Save multi policies ONLY for Book of Business
        $this->savePolicies($request, $client);

        return redirect()->route('book.index', ['selected' => $client->id]);
    }

    /**
     * ==========================================================
     * PHASE 2 — MESSAGE QUEUE (DB STORAGE ONLY)
     * ==========================================================
     */
    public function storeContactMessage(Request $request, Contact $contact)
    {
        $user = Auth::user();
        if (!$user) abort(403);

        if (!empty($contact->agency_id) && !empty($user->agency_id) && (int)$contact->agency_id !== (int)$user->agency_id) {
            abort(403, 'Unauthorized');
        }

        if (!Schema::hasTable('messages')) {
            return response()->json([
                'message' => 'Messages table not found. Run Phase 2 migration to create message storage.',
            ], 501);
        }

        $data = $request->validate([
            'channel' => 'required|string|in:sms,email',
            'subject' => 'nullable|string|max:255',
            'body'    => 'required|string|max:5000',
        ]);

        if ($data['channel'] === 'sms') {
            $data['subject'] = null;
        } else {
            if (empty($data['subject'])) {
                return response()->json(['message' => 'Subject is required for email.'], 422);
            }
        }

        $tenantId = $contact->tenant_id ?? ($user->tenant_id ?? 1);
        $agencyId = $contact->agency_id ?? ($user->agency_id ?? null);

        $id = DB::table('messages')->insertGetId([
            'contact_id'   => $contact->id,
            'tenant_id'    => $tenantId,
            'agency_id'    => $agencyId,
            'channel'      => $data['channel'],
            'to_address'   => $data['channel'] === 'sms' ? ($contact->phone ?? null) : ($contact->email ?? null),
            'subject'      => $data['subject'],
            'body'         => trim($data['body']),
            'status'       => 'queued',
            'provider'     => null,
            'provider_ref' => null,
            'created_by'   => $user->id,
            'sent_at'      => null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return response()->json([
            'success'    => true,
            'message_id' => $id,
            'status'     => 'queued',
        ], 201);
    }

    /**
     * ✅ BULK IMPORT
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $user = Auth::user();
        if (!$user) abort(403);

        $file = $request->file('file');
        $ext  = strtolower((string)$file->getClientOriginalExtension());

        if (in_array($ext, ['xlsx', 'xls'], true) && !class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return redirect()
                ->route('book.index')
                ->with('import_error', 'Excel upload requires PhpSpreadsheet. Run: composer require phpoffice/phpspreadsheet سپس دوباره تلاش کنید.');
        }

        $rows = $this->parseSpreadsheetToRows($file->getRealPath(), $ext);

        if (count($rows) === 0) {
            return redirect()
                ->route('book.index')
                ->with('import_error', 'Upload worked, but no rows were found in the file (no data rows parsed). Confirm there is a header row and at least one contact row.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $firstId = null;

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

                'contact_type'        => 'book',
                'in_book_of_business' => true,
                'created_by'          => $user->id,
            ];

            $lastContacted = $this->toDate($this->getRowVal($row, ['last_contacted', 'last contacted']));
            if ($lastContacted) {
                if (Schema::hasColumn('contacts', 'last_contacted_at')) {
                    $payload['last_contacted_at'] = $lastContacted;
                } elseif (Schema::hasColumn('contacts', 'last_contacted')) {
                    $payload['last_contacted'] = $lastContacted;
                }
            }

            $this->setIfHasColumn($payload, 'agency_id', $user->agency_id ?? null);
            $this->setIfHasColumn($payload, 'tenant_id', $user->tenant_id ?? null);

            foreach ($payload as $k => $v) {
                if ($v === '' || $v === null) unset($payload[$k]);
            }

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

            $noteText = $this->getRowVal($row, ['notes']);
            if ($noteText) {
                $tenantId = $client->tenant_id ?? ($user->tenant_id ?? 1);
                Note::create([
                    'contact_id' => $client->id,
                    'note'       => trim((string)$noteText),
                    'created_by' => $user->id,
                    'tenant_id'  => $tenantId,
                ]);
            }
        }

        $processed = $created + $updated;

        if ($processed === 0) {
            return redirect()
                ->route('book.index')
                ->with('import_error', "Upload worked, but 0 contacts were created/updated. Skipped {$skipped} empty rows.");
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
            // ignore
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

    /**
     * ✅ Save multiple policies for Book of Business ONLY.
     * NOTE: premium_due_date has been removed from UI and is NOT processed here.
     */
    private function savePolicies(Request $request, Contact $client): void
    {
        if (!Schema::hasTable('contact_policies')) {
            \Log::warning('contact_policies table missing. Policies not saved.');
            return;
        }

        $items = $request->input('policies', []);
        if (!is_array($items)) $items = [];

        // Drop empty rows
        $items = array_values(array_filter($items, function ($p) {
            if (!is_array($p)) return false;
            $copy = $p;
            unset($copy['id']);
            foreach ($copy as $v) {
                if (is_string($v) && trim($v) !== '') return true;
                if (is_numeric($v)) return true;
                if ($v instanceof \DateTimeInterface) return true;
                if (!empty($v)) return true;
            }
            return false;
        }));

        $keepIds = collect($items)
            ->pluck('id')
            ->filter()
            ->map(fn($id) => (int)$id)
            ->all();

        // Delete removed policies
        $del = ContactPolicy::query()->where('contact_id', $client->id);

        // only add agency_id filter if column exists AND client has agency_id
        if (Schema::hasColumn('contact_policies', 'agency_id') && !empty($client->agency_id)) {
            $del->where('agency_id', $client->agency_id);
        }

        if (!empty($keepIds)) {
            $del->whereNotIn('id', $keepIds);
        }

        $del->delete();

        $user = Auth::user();

        foreach ($items as $p) {
            $payload = [
                'contact_id'        => $client->id,
                'carrier'           => $p['carrier'] ?? null,
                'policy_type'       => $p['policy_type'] ?? null,
                'face_amount'       => $p['face_amount'] ?? null,
                'premium_amount'    => $p['premium_amount'] ?? null,
                'policy_issue_date' => $p['policy_issue_date'] ?? null,
                'premium_due_text'  => $p['premium_due_text'] ?? null,
            ];

            if (Schema::hasColumn('contact_policies', 'agency_id') && !empty($client->agency_id)) {
                $payload['agency_id'] = $client->agency_id;
            }

            if (Schema::hasColumn('contact_policies', 'tenant_id')) {
                $payload['tenant_id'] = $client->tenant_id ?? ($user?->tenant_id ?? 1);
            }

            if (Schema::hasColumn('contact_policies', 'created_by') && empty($p['id'])) {
                $payload['created_by'] = $user?->id ?? $client->created_by;
            }

            if (!empty($p['id'])) {
                $upd = ContactPolicy::query()
                    ->where('id', (int)$p['id'])
                    ->where('contact_id', $client->id);

                if (Schema::hasColumn('contact_policies', 'agency_id') && !empty($client->agency_id)) {
                    $upd->where('agency_id', $client->agency_id);
                }

                $upd->update($payload);
            } else {
                ContactPolicy::create($payload);
            }
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
