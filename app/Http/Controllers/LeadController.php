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
     * - Hardened against Excel objects/arrays causing "Array to string conversion"
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
        ]);

        try {
            $file = $request->file('file');

            // Import rows as a Collection (first sheet)
            $sheets = Excel::toCollection(
                new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                    public function collection(Collection $rows) {}
                },
                $file
            );

            $rows = $sheets->first() ?? collect();

            if ($rows->count() < 2) {
                return back()->with('import_error', 'Import failed: file appears empty (needs header + at least one row).');
            }

            // Build header map from row 0 (unique + normalized)
            $headerRow = $rows->get(0);
            $headerMap = $this->normalizeHeaders($headerRow);

            $created = 0;
            $skipped = 0;

            DB::transaction(function () use ($rows, $headerMap, &$created, &$skipped) {
                for ($i = 1; $i < $rows->count(); $i++) {
                    $row = $rows->get($i);

                    $data = $this->extractLeadRow($row, $headerMap);

                    if ($this->rowIsEmpty($data)) {
                        $skipped++;
                        continue;
                    }

                    $contact = new Contact();

                    // If TenantScoped does not auto-set agency_id, uncomment:
                    // $contact->agency_id = auth()->user()->agency_id;

                    $contact->contact_type = 'lead';
                    $contact->status       = $data['status'] ?: 'New';

                    $contact->first_name   = $data['first_name'] ?: null;
                    $contact->last_name    = $data['last_name'] ?: null;
                    $contact->email        = $data['email'] ?: null;
                    $contact->phone        = $data['phone'] ?: null;

                    // Optional fields (safe scalar strings)
                    if ($data['address'] !== '')     $contact->address     = $data['address'];
                    if ($data['city'] !== '')        $contact->city        = $data['city'];
                    if ($data['state'] !== '')       $contact->state       = $data['state'];
                    if ($data['zip'] !== '')         $contact->zip         = $data['zip'];
                    if ($data['company'] !== '')     $contact->company     = $data['company'];
                    if ($data['lead_source'] !== '') $contact->lead_source = $data['lead_source'];

                    /**
                     * NOTES WARNING:
                     * Some CRMs store notes in a separate table (you do have NoteController).
                     * If your contacts table does NOT have a "notes" column, remove the next line.
                     * If it DOES have it, we keep it but safely as a string.
                     */
                    if ($data['notes'] !== '') {
                        $contact->notes = $data['notes'];
                    }

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
     * Normalize header names into canonical keys (and make duplicates unique).
     * Prevents edge cases where duplicate columns or blank headers break mapping.
     */
    private function normalizeHeaders($headerRow): array
    {
        $values = $this->rowToArray($headerRow);

        $map = [];
        $seen = [];

        foreach ($values as $idx => $raw) {
            $key = Str::of($this->toScalarString($raw))
                ->lower()
                ->trim()
                ->replace(['*', '#', '.', ','], '')
                ->replace(['-', '_'], ' ')
                ->squish()
                ->toString();

            if ($key === '') {
                $key = 'column';
            }

            // Make duplicates unique: email, email_2, email_3...
            if (isset($seen[$key])) {
                $seen[$key]++;
                $key = $key . '_' . $seen[$key];
            } else {
                $seen[$key] = 1;
            }

            $map[$idx] = $key;
        }

        return $map;
    }

    /**
     * Extract data from a row using the normalized header map.
     * All values are forced into safe scalar strings.
     */
    private function extractLeadRow($row, array $headerMap): array
    {
        $values = $this->rowToArray($row);

        $raw = [];
        foreach ($values as $idx => $val) {
            $header = $headerMap[$idx] ?? '';
            if ($header === '') continue;

            // Force safe scalar strings to prevent "Array to string conversion"
            $raw[$header] = $this->toScalarString($val);
        }

        $get = function (array $keys) use ($raw) {
            foreach ($keys as $k) {
                if (array_key_exists($k, $raw) && $raw[$k] !== '') {
                    return $raw[$k];
                }
            }
            return '';
        };

        return [
            'first_name'  => $get(['first name', 'firstname', 'first']),
            'last_name'   => $get(['last name', 'lastname', 'last']),
            'email'       => $get(['email', 'email address', 'e-mail']),
            'phone'       => $get(['phone', 'phone number', 'mobile', 'cell']),
            'address'     => $get(['address', 'street', 'street address']),
            'city'        => $get(['city']),
            'state'       => $get(['state']),
            'zip'         => $get(['zip', 'zipcode', 'postal', 'postal code']),
            'company'     => $get(['company', 'business']),
            'lead_source' => $get(['lead source', 'source']),
            'notes'       => $get(['notes', 'note']),
            'status'      => $get(['status']),
        ];
    }

    private function rowIsEmpty(array $data): bool
    {
        $keys = ['first_name', 'last_name', 'email', 'phone', 'company'];
        foreach ($keys as $k) {
            if (!empty($data[$k])) return false;
        }
        return true;
    }

    /**
     * Convert a Laravel-Excel row to a plain array.
     * Handles Collection rows and array rows safely.
     */
    private function rowToArray($row): array
    {
        if ($row instanceof Collection) {
            return $row->toArray();
        }

        if (is_array($row)) {
            return $row;
        }

        // Some sheet rows can be Arrayable-like objects
        if (is_object($row) && method_exists($row, 'toArray')) {
            return $row->toArray();
        }

        return (array) $row;
    }

    /**
     * Force any Excel cell value into a safe scalar string.
     * Prevents "Array to string conversion" and object issues.
     */
    private function toScalarString($val): string
    {
        if ($val === null) return '';

        // If we somehow got an array, flatten it.
        if (is_array($val)) {
            $flat = [];
            foreach ($val as $v) {
                if (is_scalar($v)) $flat[] = (string) $v;
            }
            return trim(implode(' ', $flat));
        }

        // Handle objects (RichText, DateTime, etc.)
        if (is_object($val)) {
            // PhpSpreadsheet RichText often casts cleanly via __toString
            if (method_exists($val, '__toString')) {
                return trim((string) $val);
            }

            // DateTime-like objects
            if ($val instanceof \DateTimeInterface) {
                return $val->format('Y-m-d');
            }

            // Last resort: safe json encode
            return trim(json_encode($val));
        }

        // Scalar -> string
        $str = trim((string) $val);

        // Normalize non-breaking spaces
        $str = str_replace("\xC2\xA0", ' ', $str);

        return trim($str);
    }
}
