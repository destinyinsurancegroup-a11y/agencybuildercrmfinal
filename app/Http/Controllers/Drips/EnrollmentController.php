<?php

namespace App\Http\Controllers\Drips;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\DripCampaign;
use App\Services\Drips\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    public function enroll(Request $request, EnrollmentService $service)
    {
        $data = $request->validate([
            'campaign_id' => 'required|integer|exists:drip_campaigns,id',
            'contact_id'  => 'required|integer|exists:contacts,id',
        ]);

        $campaign = DripCampaign::active()->findOrFail($data['campaign_id']);
        $contact  = Contact::findOrFail($data['contact_id']);

        $service->enrollContact(
            $campaign,
            $contact,
            'manual',
            Auth::id()
        );

        return response()->json(['success' => true]);
    }

    public function enrollBulk(Request $request, EnrollmentService $service)
    {
        $data = $request->validate([
            'campaign_id' => 'required|integer|exists:drip_campaigns,id',
            'contact_ids' => 'required|array|min:1',
            'contact_ids.*' => 'integer|exists:contacts,id',
        ]);

        $campaign = DripCampaign::active()->findOrFail($data['campaign_id']);
        $userId = Auth::id();

        foreach (array_unique($data['contact_ids']) as $contactId) {
            $contact = Contact::find($contactId);
            if (!$contact) continue;

            $service->enrollContact($campaign, $contact, 'bulk', $userId);
        }

        return response()->json(['success' => true]);
    }
}
