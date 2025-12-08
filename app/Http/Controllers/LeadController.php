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
}
