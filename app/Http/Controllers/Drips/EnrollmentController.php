<?php

namespace App\Http\Controllers\Drips;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\DripCampaign;
use App\Services\Drips\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EnrollmentController extends Controller
{
    protected static ?bool $contactsHasAgencyId = null;
    protected static ?bool $contactsHasTenantId = null;

    protected function contactsHasAgencyId(): bool
    {
        if (self::$contactsHasAgencyId === null) {
            self::$contactsHasAgencyId = Schema::hasColumn('contacts', 'agency_id');
        }
        return self::$contactsHasAgencyId;
    }

    protected function contactsHasTenantId(): bool
    {
        if (self::$contactsHasTenantId === null) {
            self::$contactsHasTenantId = Schema::hasColumn('contacts', 'tenant_id');
        }
        return self::$contactsHasTenantId;
    }

    protected function scopedCampaignQuery()
    {
        $user = Auth::user();

        $q = DripCampaign::query()->where('status', 'active');

        // Scope by agency/tenant if present on user/campaigns
        if (isset($user->agency_id)) {
            $q->where(function ($qq) use ($user) {
                $qq->whereNull('agency_id')->orWhere('agency_id', $user->agency_id);
            });
        }

        if (isset($user->tenant_id)) {
            $q->where(function ($qq) use ($user) {
                $qq->whereNull('tenant_id')->orWhere('tenant_id', $user->tenant_id);
            });
        }

        return $q;
    }

    protected function scopedContactsQuery(array $ids)
    {
        $user = Auth::user();
        $q = Contact::query()->whereIn('id', $ids);

        if ($this->contactsHasAgencyId() && isset($user->agency_id)) {
            $q->where(function ($qq) use ($user) {
                $qq->whereNull('agency_id')->orWhere('agency_id', $user->agency_id);
            });
        }

        if ($this->contactsHasTenantId() && isset($user->tenant_id)) {
            $q->where(function ($qq) use ($user) {
                $qq->whereNull('tenant_id')->orWhere('tenant_id', $user->tenant_id);
            });
        }

        return $q;
    }

    public function enroll(Request $request, EnrollmentService $service)
    {
        $data = $request->validate([
            'campaign_id' => 'required|integer|exists:drip_campaigns,id',
            'contact_id'  => 'required|integer|exists:contacts,id',
        ]);

        $campaign = $this->scopedCampaignQuery()->findOrFail($data['campaign_id']);

        $contact = $this->scopedContactsQuery([(int)$data['contact_id']])
            ->firstOrFail();

        $service->enrollContact($campaign, $contact, 'manual', Auth::id());

        return response()->json(['success' => true]);
    }

    public function enrollBulk(Request $request, EnrollmentService $service)
    {
        $data = $request->validate([
            'campaign_id' => 'required|integer|exists:drip_campaigns,id',
            'contact_ids' => 'required|array|min:1',
            'contact_ids.*' => 'integer|exists:contacts,id',
        ]);

        $campaign = $this->scopedCampaignQuery()->findOrFail($data['campaign_id']);
        $userId = Auth::id();

        $ids = array_values(array_unique(array_map('intval', $data['contact_ids'])));

        $contacts = $this->scopedContactsQuery($ids)->get();

        foreach ($contacts as $contact) {
            $service->enrollContact($campaign, $contact, 'bulk', $userId);
        }

        return response()->json([
            'success' => true,
            'enrolled' => $contacts->count(),
            'skipped_not_found_or_unauthorized' => max(0, count($ids) - $contacts->count()),
        ]);
    }
}
