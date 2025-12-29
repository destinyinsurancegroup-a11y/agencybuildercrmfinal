<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function index()
    {
        $leads = Contact::query()
            ->where('contact_type', 'lead')
            ->where('status', '!=', 'Not Interested')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('leads.index', [
            'leads'           => $leads,
            'showingArchived' => false,
        ]);
    }

    public function archived()
    {
        $leads = Contact::query()
            ->whereIn('status', ['Sold', 'Not Interested'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('leads.index', [
            'leads'           => $leads,
            'showingArchived' => true,
        ]);
    }

    public function show($id)
    {
        $contact = Contact::query()
            ->where('id', $id)
            ->where('contact_type', 'lead')
            ->firstOrFail();

        return view('leads.partials.details', compact('contact'));
    }

    public function create()
    {
        return view('leads.partials.create');
    }

    public function markSold(Request $request, Contact $contact)
    {
        if (strtolower($contact->contact_type ?? '') !== 'lead') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'This record is not a lead.'], 400);
            }

            return redirect()
                ->route('leads.index')
                ->with('error', 'This record is not a lead.');
        }

        $contact->contact_type = 'client';
        $contact->status       = 'Sold';
        $contact->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success'     => true,
                'message'     => 'Lead converted to client successfully.',
                'contact_id'  => $contact->id,
                'redirect'    => route('book.index'),
            ]);
        }

        return redirect()
            ->route('book.index')
            ->with('success', 'Lead converted to client successfully.');
    }

    public function archive(Request $request, Contact $contact)
    {
        if (strtolower($contact->contact_type ?? '') !== 'lead') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'This record is not a lead.'], 400);
            }

            return redirect()
                ->route('leads.index')
                ->with('error', 'This record is not a lead.');
        }

        $contact->status = 'Not Interested';
        $contact->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead archived as Not Interested.',
            ]);
        }

        return redirect()
            ->route('leads.index')
            ->with('success', 'Lead archived as Not Interested and removed from active leads.');
    }

    /**
     * ✅ BULK IMPORT LEADS (EXACT same engine as BookController)
     *
     * - One row => one lead Contact card
     * - Blank fields allowed
     * - CSV always supported
     * - XLSX/XLS supported ONLY if PhpSpreadsheet is installed
     * - Dedupe by email, else phone (same as Book)
     * - Notes create Note records (same as Book)
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

        // If they upload XLSX/XLS and PhpSpreadsheet isn't installed, fail loudly (same as Book).
        if (in_array($ext, ['xlsx', 'xls'], true) && !class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return redirect()
                ->route('leads.index')
                ->with('import_error', 'Excel upload requires PhpSpreadsheet. Run: composer require phpoffice/phpspreadsheet then try again.');
        }

        $rows = $this->parseSpreadsheetToRows($file->getRealPath(), $ext);

        if (count($rows) === 0) {
            return redirect()
                ->route('leads.index')
                ->with('import_error', 'Upload worked, but no rows were found in the file (no data rows parsed). Confirm there is a header row and at least one lead row.');
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

                // Build payload (same pattern as Book) — but as LEAD
                $payload = [
                    'first_name'   => $this->cleanStr($first),
                    'last_name'    => $this->cleanStr($last),
                    'city'         => $this->cleanStr($this->getRowVal($row, ['city'])),
                    'state'        => $this->cleanStr($this->getRowVal($row, ['state'])),
                    'phone'        => $this->cleanStr($phone),
                    'email'        => $this->cleanStr($email),

                    // LEAD specifics
                    'contact_type' => 'lead',
                    'status'       => $this->cleanStr($this->getRowVal($row, ['status'])) ?? 'New',
                    'created_by'   => $user->id,
                ];

                // Optional columns (only set if exist in schema)
                $this->setIfHasColumn($payload, 'company', $this->cleanStr($this->getRowVal($row, ['company','business'])));
                $this->setIfHasColumn($payload, 'lead_source', $this->cleanStr($this->getRowVal($row, ['lead_source','lead source','source'])));
                $this->setIfHasColumn($payload, 'address', $this->cleanStr($this->getRowVal($row, ['address','street','street_address','street address'])));
                $this->setIfHasColumn($payload, 'zip', $this->cleanStr($this->getRowVal($row, ['zip','zipcode','postal','postal_code','postal code'])));

                // Optional: last contacted mapping if your contacts table has a suitable column
                $lastContacted = $this->toDate($this->getRowVal($row, ['last_contacted', 'last contacted']));
                if ($lastContacted) {
                    if (Schema::hasColumn('contacts', 'last_contacted_at')) {
                        $payload['last_contacted_at'] = $lastContacted;
                    } elseif (Schema::hasColumn('contacts', 'last_contacted')) {
                        $payload['last_contacted'] = $lastContacted;
                    }
                }

                // Set agency/tenant if those columns exist (same as Book)
                $this->setIfHasColumn($payload, 'agency_id', $user->agency_id ?? null);
                $this->setIfHasColumn($payload, 'tenant_id', $user->tenant_id ?? null);

                // Remove null/empty
                foreach ($payload as $k => $v) {
                    if ($v === '' || $v === null) unset($payload[$k]);
                }

                // Dedupe: prefer email, else phone (same as Book)
                $lead = null;
                if (!empty($payload['email'])) {
                    $lead = Contact::query()->where('email', $payload['email'])->first();
                } elseif (!empty($payload['phone'])) {
                    $lead = Contact::query()->where('phone', $payload['phone'])->first();
                }

                if ($lead) {
                    // Ensure it stays a lead
                    $lead->fill($payload);
                    $lead->contact_type = 'lead';
                    $lead->save();
                    $updated++;
                } else {
                    $lead = Contact::create($payload);
                    $created++;
                }

                if (!$firstId) $firstId = $lead->id;

                // Notes (creates Note record, same as Book)
                $noteText = $this->getRowVal($row, ['notes', 'note']);
                if ($noteText) {
                    $tenantId = $lead->tenant_id ?? ($user->tenant_id ?? 1);
                    Note::create([
                        'contact_id' => $lead->id,
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
                ->route('leads.index')
                ->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        $processed = $created + $updated;

        if ($processed === 0) {
            return redirect()
                ->route('leads.index')
                ->with('import_error', "Upload worked, but 0 leads were created/updated. Skipped {$skipped} empty rows.");
        }

        return redirect()
            ->route('leads.index', $firstId ? ['selected' => $firstId] : [])
            ->with('import_success', "Import complete: {$created} created, {$updated} updated. Skipped {$skipped} rows.");
    }

    // ======================================================================
    // IMPORT HELPERS (COPIED from BookController)
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
                $headers[$col] = $this->normalizeHeader((string) $val);
            }

            $out = [];
            foreach ($rows as $r) {
                $assoc = [];
                foreach ($headers as $col => $h) {
                    if ($h === '') continue;
                    $assoc[$h] = isset($r[$col]) ? trim((string) $r[$col]) : null;
                }
                if (count(array_filter($assoc, fn ($v) => $v !== null && $v !== '')) === 0) continue;
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
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        }

        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $i => $h) {
                if ($h === '') continue;
                $row[$h] = isset($data[$i]) ? trim((string) $data[$i]) : null;
            }
            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) continue;
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
        return trim((string) $h, '_');
    }

    private function getRowVal(array $row, array $keys)
    {
        foreach ($keys as $k) {
            $k = $this->normalizeHeader((string) $k);
            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                return $row[$k];
            }
        }
        return null;
    }

    private function isBlankIdentifierRow($first, $last, $email, $phone): bool
    {
        $first = trim((string) $first);
        $last  = trim((string) $last);
        $email = trim((string) $email);
        $phone = trim((string) $phone);

        return ($first === '' && $last === '' && $email === '' && $phone === '');
    }

    private function cleanStr($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string) $v);
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
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $val);
                return $date->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $val = trim((string) $val);
        try {
            return \Carbon\Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
