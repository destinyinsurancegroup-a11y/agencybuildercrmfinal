<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeadController extends Controller
{
    /**
     * LIST ALL ACTIVE LEADS
     *
     * Shows only leads that are still being worked.
     * Sold leads are now clients (contact_type = 'client') and are thus excluded
     * automatically. We explicitly hide Not Interested as well.
     *
     * Multi-tenancy:
     *  - Contact model uses TenantScoped, so all queries are automatically
     *    filtered by agency_id for the currently logged-in user.
     */
    public function index()
    {
        $leads = Contact::query()
            ->where('contact_type', 'lead')
            ->where('status', '!=', 'Not Interested') // hide archived leads
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('leads.index', [
            'leads'           => $leads,
            'showingArchived' => false,
        ]);
    }

    /**
     * LIST ARCHIVED LEADS (Sold + Not Interested)
     *
     * This is your "archive" / outcomes view and is used for
     * conversion tracking later (Sold vs Not Interested).
     *
     * NOTE: We do NOT restrict by contact_type here so that
     * Sold records (now contact_type = 'client') also appear.
     *
     * Multi-tenancy:
     *  - Contact::query() is TenantScoped, so only current agency's records
     *    will be included.
     */
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

    /**
     * SHOW LEAD DETAILS
     */
    public function show($id)
    {
        // TenantScoped ensures we only ever load a lead from the current agency.
        $contact = Contact::query()
            ->where('id', $id)
            ->where('contact_type', 'lead')
            ->firstOrFail();

        return view('leads.partials.details', compact('contact'));
    }

    /**
     * CREATE LEAD FORM PANEL
     *
     * Note: actual storage of the lead record may be handled by
     * ContactsController::store or a dedicated lead store route.
     */
    public function create()
    {
        return view('leads.partials.create');
    }

    /**
     * ✅ BULK IMPORT LEADS (Excel/CSV) → CREATE ONE CONTACT CARD PER ROW
     *
     * Desired behavior:
     *  - Each row creates its own lead contact card.
     *  - Missing/blank fields do NOT block import (they stay null/blank).
     *  - Row-level errors are recorded and skipped, without failing the whole import.
     *  - TenantScoped protects reads; we also set agency_id explicitly on create
     *    as a defense-in-depth measure (in case TenantScoped only filters).
     *
     * NOTE:
     *  - Requires maatwebsite/excel package.
     *  - Form should use enctype="multipart/form-data" and input name="file"
     *    (we also accept excel/upload/spreadsheet for resilience).
     */
    public function import(Request $request)
    {
        $file =
            $request->file('file')
            ?? $request->file('excel')
            ?? $request->file('upload')
            ?? $request->file('spreadsheet');

        if (!$file) {
            return back()->withErrors([
                'file' => 'No file was uploaded. Ensure the form uses enctype="multipart/form-data" and the input name matches.',
            ]);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?? '');
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return back()->withErrors([
                'file' => 'Invalid file type. Please upload an .xlsx, .xls, or .csv file.',
            ]);
        }

        $user = Auth::user();
        $agencyId = $user->agency_id ?? $user->tenant_id ?? null;

        if (!$agencyId) {
            return back()->withErrors([
                'file' => 'Agency context missing (agency_id). Import blocked for safety.',
            ]);
        }

        // Store temporarily so Excel reader can reliably access it
        $path = $file->store('imports/leads');

        $created = 0;
        $skipped = 0;
        $rowErrors = [];

        try {
            DB::beginTransaction();

            // Detect and normalize headings (row 1)
            $headingRows = (new HeadingRowImport())->toArray($path);
            $headings = $headingRows[0][0] ?? []; // first sheet, first row headings
            $normalizedHeadings = $this->normalizeHeadings(is_array($headings) ? $headings : []);

            if (empty($normalizedHeadings)) {
                DB::rollBack();
                return back()->withErrors([
                    'file' => 'Import failed: Could not detect header row. Make sure the first row contains column headings.',
                ]);
            }

            // Read all rows as arrays (by default, Laravel Excel will map to heading keys if using ToArray with heading)
            // We use Excel::toCollection with HeadingRowImport for robustness, then map ourselves.
            $sheets = Excel::toCollection(null, $path);
            /** @var Collection<int, Collection<int, mixed>> $sheet */
            $sheet = $sheets->first() ?? collect();

            if ($sheet->count() < 2) {
                // Only header present, or empty file
                DB::commit();
                return back()->with('import_summary', [
                    'created' => 0,
                    'skipped' => 0,
                    'row_errors' => [
                        ['row' => 0, 'error' => 'No data rows found. The file contains only headers or is empty.'],
                    ],
                ]);
            }

            // First row is headings; remaining are data rows
            $dataRows = $sheet->slice(1)->values();

            $rowNumber = 1; // 1 = header row; data starts at 2
            foreach ($dataRows as $row) {
                $rowNumber++;

                // Row can be array-like; normalize to array of cells indexed numerically
                $cells = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : []);
                if (!is_array($cells)) $cells = [];

                $data = $this->mapRowByHeadings($normalizedHeadings, $cells);
                $data = $this->normalizeLeadRow($data);

                if ($this->rowIsEmpty($data)) {
                    $skipped++;
                    continue;
                }

                // We do NOT require optional fields. But we do require at least *some* identity:
                // - If you want to allow fully anonymous rows, remove this block.
                $hasAnyIdentity =
                    !empty($data['first_name']) ||
                    !empty($data['last_name']) ||
                    !empty($data['email']) ||
                    !empty($data['phone']) ||
                    !empty($data['company']);

                if (!$hasAnyIdentity) {
                    $skipped++;
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'error' => 'Row skipped: no identifying fields (name/email/phone/company) were provided.',
                    ];
                    continue;
                }

                // Minimal format checks (do not fail entire import)
                if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'error' => 'Invalid email format.',
                    ];
                    continue;
                }

                // ALWAYS create a new lead contact card per row (per your desired outcome).
                // If you later decide to dedupe/upsert, we can change this safely.
                $payload = [
                    // tenant/agency safety
                    'agency_id'     => $agencyId,

                    // this record is a lead
                    'contact_type'  => 'lead',
                    'status'        => $data['status'] ?? 'New',

                    // common contact card fields (blank is fine)
                    'first_name'    => $data['first_name'] ?? null,
                    'last_name'     => $data['last_name'] ?? null,
                    'email'         => $data['email'] ?? null,
                    'phone'         => $data['phone'] ?? null,

                    // optional fields if your Contact model has them
                    'address'       => $data['address'] ?? null,
                    'city'          => $data['city'] ?? null,
                    'state'         => $data['state'] ?? null,
                    'zip'           => $data['zip'] ?? null,

                    // common CRM fields (if present in your schema)
                    'company'       => $data['company'] ?? null,
                    'lead_source'   => $data['lead_source'] ?? null,
                    'notes'         => $data['notes'] ?? null,
                ];

                try {
                    Contact::create($payload);
                    $created++;
                } catch (\Throwable $rowEx) {
                    $skipped++;
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'error' => 'Row insert failed: ' . $rowEx->getMessage(),
                    ];
                }
            }

            DB::commit();

            // Clean up stored file (optional)
            try { Storage::delete($path); } catch (\Throwable $e) { /* ignore */ }

            Log::info('Leads bulk import completed', [
                'agency_id' => $agencyId,
                'user_id' => $user->id,
                'created' => $created,
                'skipped' => $skipped,
                'errors' => count($rowErrors),
            ]);

            return back()->with('import_summary', [
                'created' => $created,
                'skipped' => $skipped,
                'row_errors' => array_slice($rowErrors, 0, 50),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Leads bulk import failed', [
                'agency_id' => $agencyId,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 4000),
            ]);

            return back()->withErrors([
                'file' => 'Import failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Download a CSV template with recommended headings.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $tmp = storage_path('app/imports/leads-template.csv');

        if (!file_exists($tmp)) {
            @mkdir(dirname($tmp), 0775, true);

            $headers = [
                'first_name',
                'last_name',
                'email',
                'phone',
                'company',
                'lead_source',
                'status',
                'address',
                'city',
                'state',
                'zip',
                'notes',
            ];

            file_put_contents($tmp, implode(',', $headers) . PHP_EOL);
        }

        return response()->download($tmp, 'leads_import_template.csv');
    }

    /**
     * ⭐ CONVERT LEAD → CLIENT + MOVE TO BOOK OF BUSINESS ⭐
     *
     * 1. Removes from Leads tab (no longer contact_type = 'lead')
     * 2. Appears in All Contacts (ContactsController excludes only 'lead')
     * 3. Appears in Book of Business (BookController includes 'client' / 'Sold')
     * 4. Appears in Archived Leads (status = 'Sold')
     *
     * Multi-tenancy:
     *  - Route model binding + TenantScoped guarantee that $contact already
     *    belongs to the current agency. No manual tenant_id checks needed.
     */
    public function markSold(Request $request, Contact $contact)
    {
        // Make sure this is actually a lead (case-insensitive)
        if (strtolower($contact->contact_type ?? '') !== 'lead') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'This record is not a lead.'], 400);
            }

            return redirect()
                ->route('leads.index')
                ->with('error', 'This record is not a lead.');
        }

        // 1️⃣ UPDATE CONTACT TYPE → client (lowercase)
        $contact->contact_type = 'client';
        $contact->status       = 'Sold'; // mark outcome as Sold
        // Optional: archive metadata could go here (sold_at, archived_at, etc.)

        $contact->save();

        // If the request is AJAX / fetch → return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success'     => true,
                'message'     => 'Lead converted to client successfully.',
                'contact_id'  => $contact->id,
                'redirect'    => route('book.index'),
            ]);
        }

        // Fallback: standard form POST → redirect
        return redirect()
            ->route('book.index')
            ->with('success', 'Lead converted to client successfully.');
    }

    /**
     * ARCHIVE LEAD → NOT INTERESTED
     *
     * - Sets status = 'Not Interested'
     * - Lead is removed from active list (index)
     * - Lead appears in Archived view (/leads/archived)
     *
     * Multi-tenancy:
     *  - Route model binding + TenantScoped ensure the lead belongs
     *    to the current agency.
     */
    public function archive(Request $request, Contact $contact)
    {
        // Must be a lead to archive this way
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
     * -------------------------
     * Helpers for import mapping
     * -------------------------
     */

    /**
     * Convert raw headings into normalized canonical keys.
     * Supports common variants (e.g., "First Name", "firstname", etc.).
     *
     * @param array<int, mixed> $headings
     * @return array<int, string>
     */
    private function normalizeHeadings(array $headings): array
    {
        $out = [];

        foreach ($headings as $h) {
            $key = is_string($h) ? trim(strtolower($h)) : '';
            $key = preg_replace('/\s+/', ' ', $key ?? '');
            $key = str_replace(['-', '.'], ' ', $key);
            $key = trim($key);

            if ($key === '') continue;

            // map variants -> canonical
            $map = [
                'first name' => 'first_name',
                'firstname' => 'first_name',
                'last name' => 'last_name',
                'lastname' => 'last_name',
                'e mail' => 'email',
                'e-mail' => 'email',
                'email address' => 'email',
                'mobile' => 'phone',
                'cell' => 'phone',
                'phone number' => 'phone',
                'lead source' => 'lead_source',
                'source' => 'lead_source',
                'street' => 'address',
                'postal' => 'zip',
                'zipcode' => 'zip',
                'zip code' => 'zip',
            ];

            $out[] = $map[$key] ?? str_replace(' ', '_', $key);
        }

        return $out;
    }

    /**
     * Map a numeric cell array into an associative array keyed by headings.
     *
     * @param array<int, string> $headings
     * @param array<int, mixed> $cells
     * @return array<string, mixed>
     */
    private function mapRowByHeadings(array $headings, array $cells): array
    {
        $assoc = [];

        foreach ($headings as $i => $key) {
            $value = $cells[$i] ?? null;

            // Normalize strings
            if (is_string($value)) {
                $value = trim($value);
            }

            $assoc[$key] = $value;
        }

        return $assoc;
    }

    /**
     * Normalize lead row keys and values.
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeLeadRow(array $data): array
    {
        // Ensure canonical keys exist even if missing
        $canonical = [
            'first_name', 'last_name', 'email', 'phone',
            'company', 'lead_source', 'status',
            'address', 'city', 'state', 'zip',
            'notes',
        ];

        $out = [];
        foreach ($canonical as $k) {
            $out[$k] = $data[$k] ?? null;
        }

        // Soft-normalize phone: keep digits/+() - (don't over-format)
        if (is_string($out['phone'])) {
            $out['phone'] = trim($out['phone']);
        }

        // Normalize status empty -> null (we default later)
        if (is_string($out['status']) && trim($out['status']) === '') {
            $out['status'] = null;
        }

        return $out;
    }

    /**
     * Determine if row is effectively empty.
     * @param array<string, mixed> $data
     */
    private function rowIsEmpty(array $data): bool
    {
        foreach ($data as $v) {
            if (is_string($v) && trim($v) !== '') return false;
            if (is_numeric($v)) return false;
            if ($v !== null && $v !== '') return false;
        }
        return true;
    }
}
