<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DripCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DripCampaignController extends Controller
{
    /**
     * List campaigns.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $q = DripCampaign::query()->orderBy('created_at', 'desc');

        // Best-effort scope (matches your style elsewhere)
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

        $campaigns = $q->get();

        return view('settings.messaging.drips.index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show create form (we will build the blade next).
     */
    public function create()
    {
        $this->requireAuth();

        return view('settings.messaging.drips.create', [
            'types' => $this->campaignTypes(),
        ]);
    }

    /**
     * Store campaign.
     */
    public function store(Request $request)
    {
        $user = $this->requireAuth();

        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'type'         => 'required|string|in:' . implode(',', array_keys($this->campaignTypes())),
            'status'       => 'nullable|string|in:active,paused,archived',
            'channel_mode' => 'nullable|string|in:sms,email,mixed',
            'description'  => 'nullable|string|max:5000',
        ]);

        $campaign = DripCampaign::create([
            'agency_id'     => $user->agency_id ?? null,
            'tenant_id'     => $user->tenant_id ?? null,
            'name'          => $data['name'],
            'type'          => $data['type'],
            'status'        => $data['status'] ?? 'paused',
            'channel_mode'  => $data['channel_mode'] ?? 'sms',
            'description'   => $data['description'] ?? null,
            'created_by'    => $user->id,
        ]);

        return redirect()
            ->route('settings.messaging.drips.index')
            ->with('success', 'Drip campaign created.');
    }

    /**
     * Show edit form (we will build the blade next).
     */
    public function edit(DripCampaign $dripCampaign)
    {
        $user = $this->requireAuth();
        $this->enforceCampaignScopeOrAbort($dripCampaign, $user);

        return view('settings.messaging.drips.edit', [
            'campaign' => $dripCampaign,
            'types'    => $this->campaignTypes(),
        ]);
    }

    /**
     * Update campaign.
     */
    public function update(Request $request, DripCampaign $dripCampaign)
    {
        $user = $this->requireAuth();
        $this->enforceCampaignScopeOrAbort($dripCampaign, $user);

        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'type'         => 'required|string|in:' . implode(',', array_keys($this->campaignTypes())),
            'status'       => 'required|string|in:active,paused,archived',
            'channel_mode' => 'required|string|in:sms,email,mixed',
            'description'  => 'nullable|string|max:5000',
        ]);

        $dripCampaign->update([
            'name'         => $data['name'],
            'type'         => $data['type'],
            'status'       => $data['status'],
            'channel_mode' => $data['channel_mode'],
            'description'  => $data['description'] ?? null,
        ]);

        return redirect()
            ->route('settings.messaging.drips.index')
            ->with('success', 'Drip campaign updated.');
    }

    /**
     * Helpers
     */
    protected function requireAuth()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        return $user;
    }

    protected function enforceCampaignScopeOrAbort(DripCampaign $campaign, $user): void
    {
        // Best-effort agency/tenant protection
        if (isset($user->agency_id) && isset($campaign->agency_id) && (string) $campaign->agency_id !== (string) $user->agency_id) {
            abort(403, 'Unauthorized');
        }
        if (isset($user->tenant_id) && isset($campaign->tenant_id) && (string) $campaign->tenant_id !== (string) $user->tenant_id) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Campaign type labels for UI.
     * Values are stored in DB.
     */
    protected function campaignTypes(): array
    {
        return [
            'onboarding_timeline' => '90-Day Welcome (Timeline)',
            'date_holiday'        => 'Holiday',
            'date_birthday'       => 'Birthday',
            'policy_anniversary'  => 'Policy Anniversary (Initial Draft Date)',
        ];
    }
}
