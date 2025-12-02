<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * LIST ALL ACTIVE LEADS
     *
     * Shows only leads that are still being worked.
     * Excludes Sold + Not Interested (Archived) leads.
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        $leads = Contact::where('tenant_id', $tenantId)
            ->where('contact_type', 'lead')
            ->whereNotIn('status', ['Sold', 'Not Interested'])
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
     */
    public function archived()
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        $leads = Contact::where('tenant_id', $tenantId)
            ->where('contact_type', 'lead')
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
        $tenantId = auth()->user()->tenant_id ?? 1;

        $contact = Contact::where('tenant_id', $tenantId)
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
     * ⭐ CONVERT LEAD → CLIENT + MOVE TO BOOK OF BUSINESS ⭐
     *
     * 1. Removes from Leads tab (no longer contact_type = 'lead')
     * 2. Appears in All Contacts (ContactsController excludes only 'lead')
     * 3. Appears in Book of Business (BookController includes 'client' / 'Sold')
     */
    public function markSold(Request $request, Contact $contact)
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        // Tenant safety
        if ($contact->tenant_id !== $tenantId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized tenant.'], 403);
            }
            abort(403, 'Unauthorized tenant.');
        }

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
     */
    public function archive(Request $request, Contact $contact)
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        // Tenant safety
        if ($contact->tenant_id !== $tenantId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized tenant.'], 403);
            }
            abort(403, 'Unauthorized tenant.');
        }

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
}
