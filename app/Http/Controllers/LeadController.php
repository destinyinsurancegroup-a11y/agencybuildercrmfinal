<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

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
     * ✅ BULK IMPORT LEADS (Book-of-Business style)
     *
     * - One row => one Contact (contact_type=lead)
     * - Blank fields allowed
     * - Tries to map flexible header names
     * - No dependency on HeadingRowFormatter / HeadingRow classes
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
        ]);

        try {
            $file = $request->file('file');

            // Import rows as a Collection (first sheet)
            $sheets = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                public function collection(Collection $rows) {}
            }, $file);

            $rows = $sheets->first() ?? collect();

            if ($rows->count() < 2) {
                return back()->with('import_error', 'Import failed: file appears empty (needs header + at least one row).');
            }

            // Build header map from row 0
            $headerRow = $rows->get(0);
            $headerMap = $this->normalizeHeaders($headerRow);

            $created = 0;
            $skipped = 0;

            DB::transaction(function () use ($rows, $headerMap, &$created, &$skipped) {
                // Process data rows
                for ($i = 1; $i < $rows->count(); $i++) {
                    $row = $rows->get($i);

                    $data = $this->extractLeadRow($row, $headerMap);

                    // Skip completely empty rows
                    if ($this->rowIsEmpty($data)) {
                        $skipped++;
                        continue;
                    }

                    // Create lead contact card (blank fields allowed)
                    $contact = new Contact();

                    // If your TenantScoped sets agency_id automatically, you can omit this.
                    // If NOT, uncomment:
                    // $contact->agency_id = auth()->user()->agency_id;

                    $contact->contact_type = 'lead';
                    $contact->status       = $data['status'] ?? 'New';

                    $contact->first_name   = $data['first_name'] ?? null;
                    $contact->last_name    = $data['last_name'] ?? null;
                    $contact->email        = $data['email'] ?? null;
                    $contact->phone        = $data['phone'] ?? null;

                    // Optional fields (only if these columns exist in your contacts table)
                    if (isset($data['address'])) $contact->address = $data['address'];
                    if (isset($data['city']))    $contact->city    = $data['city'];
                    if (isset($data['state']))   $contact->state   = $data['state'];
                    if (isset($data['zip']))     $contact->zip     = $data['zip'];
                    if (isset($data['company'])) $contact->company = $data['company'];

                    // Lead source / notes fields if present in schema
                    if (isset($data['lead_source'])) $contact->lead_source = $data['lead_source'];
                    if (isset($data['notes']))       $contact->notes       = $data['notes'];

                    $contact->save();
                    $created++;
                }
            });

            return redirect()
                ->route('leads.index')
                ->with('import_success', "Leads import complete. Created: {$created}. Skipped empty rows: {$skipped}.");

        } catch (\Throwable $e) {
            return back()->with('import_error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Normalize header names into canonical keys.
     */
    private function normalizeHeaders($headerRow): array
    {
        $map = [];

        foreach ((array) $headerRow as $idx => $raw) {
            $key = Str::of((string) $raw)
                ->lower()
                ->trim()
                ->replace(['*', '#', '.', ','], '')
                ->replace(['-', '_'], ' ')
                ->squish()
                ->toString();

            $map[$idx] = $key;
        }

        return $map;
    }

    /**
     * Extract data from a row using the normalized header map.
     */
    private function extractLeadRow($row, array $headerMap): array
    {
        $raw = [];
        foreach ((array) $row as $idx => $val) {
            $header = $headerMap[$idx] ?? '';
            $raw[$header] = is_string($val) ? trim($val) : $val;
        }

        // Flexible matching for common column names
        $get = function(array $keys) use ($raw) {
            foreach ($keys as $k) {
                if (array_key_exists($k, $raw) && $raw[$k] !== null && $raw[$k] !== '') {
                    return $raw[$k];
                }
            }
            return null;
        };

        return [
            'first_name'   => $get(['first name', 'firstname', 'first']),
            'last_name'    => $get(['last name', 'lastname', 'last']),
            'email'        => $get(['email', 'email address', 'e-mail']),
            'phone'        => $get(['phone', 'phone number', 'mobile', 'cell']),
            'address'      => $get(['address', 'street', 'street address']),
            'city'         => $get(['city']),
            'state'        => $get(['state']),
            'zip'          => $get(['zip', 'zipcode', 'postal', 'postal code']),
            'company'      => $get(['company', 'business']),
            'lead_source'  => $get(['lead source', 'source']),
            'notes'        => $get(['notes', 'note']),
            'status'       => $get(['status']),
        ];
    }

    private function rowIsEmpty(array $data): bool
    {
        // Treat as empty if no meaningful identifier fields are present
        $keys = ['first_name', 'last_name', 'email', 'phone', 'company'];
        foreach ($keys as $k) {
            if (!empty($data[$k])) return false;
        }
        return true;
    }
}
