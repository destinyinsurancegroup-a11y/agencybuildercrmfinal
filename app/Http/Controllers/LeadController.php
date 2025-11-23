<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * LIST ALL LEADS
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        $leads = Contact::where('tenant_id', $tenantId)
            ->where('contact_type', 'lead')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('leads.index', compact('leads'));
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
     */
    public function markSold(Contact $contact)
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        if ($contact->tenant_id !== $tenantId) {
            return response()->json(['error' => 'Unauthorized tenant.'], 403);
        }

        if ($contact->contact_type !== 'lead') {
            return response()->json(['error' => 'This record is not a lead.'], 400);
        }

        // 1️⃣ UPDATE CONTACT TYPE → CLIENT
        $contact->contact_type = 'Client';
        $contact->status = 'Sold';

        // Clear any irrelevant lead fields if needed
        // (Optional depending on design)
        // $contact->lead_received_date = null;
        // $contact->lead_assigned_date = null;

        $contact->save();

        // 2️⃣ AUTO-CREATE "Book of Business" entry
        // NOTE: BookController normally uses the same Contact model.
        // Therefore: no duplication — just classification.
        // If Book of Business later becomes a separate table, we expand this.

        return response()->json([
            'success' => true,
            'message' => 'Lead converted to client successfully.',
            'redirect' => route('book.index')
        ]);
    }

    /**
     * UPDATE LEAD → NOT INTERESTED (future filtering)
     */
    public function markNotInterested(Contact $contact)
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        if ($contact->tenant_id !== $tenantId) {
            return response()->json(['error' => 'Unauthorized tenant.'], 403);
        }

        if ($contact->contact_type !== 'lead') {
            return response()->json(['error' => 'This record is not a lead.'], 400);
        }

        $contact->status = 'Not Interested';
        $contact->save();

        return response()->json([
            'success' => true,
            'message' => 'Lead marked as Not Interested.'
        ]);
    }
}
