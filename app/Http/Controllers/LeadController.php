<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    /**
     * LIST ALL ACTIVE LEADS
     */
    public function index(Request $request)
    {
        $leads = Contact::query()
            ->where('contact_type', 'lead')
            ->where('status', '!=', 'Not Interested')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $selected = $request->get('selected');

        return view('leads.index', [
            'leads'           => $leads,
            'showingArchived' => false,
            'selected'        => $selected,
        ]);
    }

    /**
     * LIST ARCHIVED LEADS (Sold + Not Interested)
     */
    public function archived(Request $request)
    {
        $leads = Contact::query()
            ->whereIn('status', ['Sold', 'Not Interested'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $selected = $request->get('selected');

        return view('leads.index', [
            'leads'           => $leads,
            'showingArchived' => true,
            'selected'        => $selected,
        ]);
    }

    /**
     * SHOW LEAD DETAILS
     */
    public function show($id)
    {
        $contact = Contact::query()
            ->where('id', $id)
            ->where('contact_type', 'lead')
            ->firstOrFail();

        return view('leads.partials.details', compact('contact'));
    }

    /**
     * CREATE LEAD FORM PANEL
     */
    public function create()
    {
        return view('leads.partials.create');
    }

    /**
     * CONVERT LEAD → CLIENT
     */
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

    /**
     * ARCHIVE LEAD → NOT INTERESTED
     */
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
     * ✅ BULK IMPORT LEADS (Book-of-Business style)
     * - One row => one lead Contact
     * - Blank fields allowed
     * - CSV/TXT always supported
     * - XLSX/XLS supported ONLY if PhpSpreadsheet is installed
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

        // Match BookController behavior: XLSX/XLS requires PhpSpreadsheet
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

        foreach ($rows as $row) {
            // Identifiers (any one is enough)
            $first = $this->getRowVal($row, ['first_name','first name','firstname','fname','first']);
            $last  = $this->getRowVal($row, ['last_name','last name','lastname','lname','last']);
            $email = $this->getRowVal($row, ['email','e-mail','email_address','email address']);
            $phone = $this->getRowVal($row, ['phone','phone_number','mobile','cell','phone number']);

            if ($this->isBlankIdentifierRow($first, $last, $email, $phone)) {
                $skipped++;
                continue;
            }

            // Payload (blank OK)
            $payload = [
                'first_name'   => $this->cleanStr($first),
                'last_name'    => $this->cleanStr($last),
                'email'        => $this->cleanStr($email),
                'phone'        => $this->cleanStr($phone),

                // Optional common columns if they exist in your schema:
                'company'      => $this->cleanStr($this->getRowVal($row, ['company','business'])),
                'lead_source'  => $this->cleanStr($this->getRowVal($row, ['lead_source','lead source','source'])),
                'status'       => $this->cleanStr($this->getRowVal($row, ['status'])),

                'contact_type' => 'lead',
                'created_by'   => $user->id,
            ];

            // Default status if blank
            if (empty($payload['status'])) {
                $payload['status'] = 'New';
            }

            // Multi-tenant fields if present
            $this->setIfHasColumn($payload, 'agency_id', $user->agency_id ?? null);
            $this->setIfHasColumn($payload, 'tenant_id', $user->tenant_id ?? null);

            // Remove null/empty
            foreach ($payload as $k => $v) {
                if ($v === '' || $v === null) unset($payload[$k]);
            }

            // Deduping like Book: prefer email, else phone (optional but practical)
            $lead = null;
            if (!empty($payload['email'])) {
                $lead = Contact::query()
                    ->where('contact_type', 'lead')
                    ->where('email', $payload['email'])
                    ->first();
            } elseif (!empty($payload['phone'])) {
                $lead = Contact::query()
                    ->where('contact_type', 'lead')
                    ->where('phone', $payload['phone'])
                    ->first();
            }

            if ($lead) {
                $lead->fill($payload);
                $lead->contact_type = 'lead';
                $lead->save();
                $updated++;
            } else {
                $lead = Contact::create($payload);
                $created++;
            }

            if (!$firstId) $firstId = $lead->id;
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
    // IMPORT HELPERS (copied in spirit from BookController)
    // ======================================================================

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

        // Avoid "Array to string conversion"
        if (is_array($v) || is_object($v)) return null;

        $s = trim((string) $v);
        if ($s === '') return null;

        // Strip control chars
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

                // skip empty row
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

        // Strip UTF-8 BOM
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        }

        $headers = array_map(fn($h) => $this->normalizeHeader((string) $h), $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $i => $h) {
                if ($h === '') continue;
                $row[$h] = isset($data[$i]) ? trim((string) $data[$i]) : null;
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
}
