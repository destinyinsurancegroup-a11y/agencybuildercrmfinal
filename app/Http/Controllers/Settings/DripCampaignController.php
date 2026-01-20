<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DripCampaign;
use App\Models\DripStep;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DripCampaignController extends Controller
{
    /**
     * List campaigns.
     */
    public function index()
    {
        $user = $this->requireAuth();

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
     * Show create form.
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

        DripCampaign::create([
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
     * Show edit form.
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

    /*
    |--------------------------------------------------------------------------
    | STEPS BUILDER
    |--------------------------------------------------------------------------
    */

    /**
     * View steps builder page.
     */
    public function steps(DripCampaign $dripCampaign)
    {
        $user = $this->requireAuth();
        $this->enforceCampaignScopeOrAbort($dripCampaign, $user);

        $steps = DripStep::query()
            ->where('drip_campaign_id', $dripCampaign->id)
            ->orderBy('step_order')
            ->get();

        // Templates list for dropdown. Respect scope + channel_mode.
        $templatesQ = MessageTemplate::query()
            ->where('is_active', true)
            ->orderBy('channel')
            ->orderBy('name');

        if (isset($user->agency_id)) {
            $templatesQ->where(function ($qq) use ($user) {
                $qq->whereNull('agency_id')->orWhere('agency_id', $user->agency_id);
            });
        }
        if (isset($user->tenant_id)) {
            $templatesQ->where(function ($qq) use ($user) {
                $qq->whereNull('tenant_id')->orWhere('tenant_id', $user->tenant_id);
            });
        }

        if (in_array($dripCampaign->channel_mode, ['sms', 'email'], true)) {
            $templatesQ->where('channel', $dripCampaign->channel_mode);
        } else {
            // mixed: allow both
            $templatesQ->whereIn('channel', ['sms', 'email']);
        }

        $templates = $templatesQ->get(['id', 'channel', 'name', 'subject']);

        return view('settings.messaging.drips.steps', [
            'campaign'  => $dripCampaign,
            'steps'     => $steps,
            'templates' => $templates,
            'types'     => $this->campaignTypes(),
        ]);
    }

    /**
     * Create a new step.
     */
    public function storeStep(Request $request, DripCampaign $dripCampaign)
    {
        $user = $this->requireAuth();
        $this->enforceCampaignScopeOrAbort($dripCampaign, $user);

        $data = $request->validate([
            'template_id'     => 'required|integer|exists:message_templates,id',
            'step_order'      => 'nullable|integer|min:1|max:999',
            'delay_days'      => 'nullable|integer|min:0|max:3650',
            'holiday_mmdd'    => 'nullable|string|max:5', // "12-25" for holiday/birthday (not for policy_anniversary)
            'send_time_local' => 'nullable|string|max:8', // "09:00" or "09:00:00"
            'is_active'       => 'nullable|boolean',
        ]);

        // Template scope check (best-effort)
        $template = MessageTemplate::query()->findOrFail((int) $data['template_id']);
        $this->enforceTemplateScopeOrAbort($template, $user);

        // If campaign is not mixed, enforce template channel matches
        if (in_array($dripCampaign->channel_mode, ['sms', 'email'], true)) {
            if ($template->channel !== $dripCampaign->channel_mode) {
                return back()->withErrors([
                    'template_id' => 'Template channel must match campaign channel.',
                ]);
            }
        }

        // Normalize send_time_local (if user passes "09:00", store "09:00:00")
        $sendTime = $this->normalizeTime($data['send_time_local'] ?? null);

        // Determine next order if not provided
        $stepOrder = isset($data['step_order']) && $data['step_order']
            ? (int) $data['step_order']
            : ((int) DripStep::query()->where('drip_campaign_id', $dripCampaign->id)->max('step_order') + 1);

        // Validate per type
        $delayDays = $data['delay_days'] ?? null;
        $holidayMmdd = $data['holiday_mmdd'] ?? null;

        if ($dripCampaign->type === 'onboarding_timeline') {
            if ($delayDays === null) {
                return back()->withErrors(['delay_days' => 'Timeline campaigns require a delay (days).']);
            }
            $holidayMmdd = null;
        }

        if (in_array($dripCampaign->type, ['date_holiday', 'date_birthday'], true)) {
            if (!$holidayMmdd) {
                return back()->withErrors(['holiday_mmdd' => 'This campaign type requires a MM-DD date (example: 12-25).']);
            }
            if (!$this->looksLikeMmdd($holidayMmdd)) {
                return back()->withErrors(['holiday_mmdd' => 'Invalid format. Use MM-DD (example: 12-25).']);
            }
            $delayDays = null;
        }

        // Policy anniversary uses the CONTACT’s Initial Draft Date.
        // The step does NOT need holiday_mmdd.
        if ($dripCampaign->type === 'policy_anniversary') {
            $holidayMmdd = null;
            $delayDays = null; // annual scheduling comes from contact date
        }

        DB::transaction(function () use ($dripCampaign, $user, $template, $stepOrder, $delayDays, $holidayMmdd, $sendTime, $data) {

            // If inserting into an existing order number, bump later steps down.
            DripStep::query()
                ->where('drip_campaign_id', $dripCampaign->id)
                ->where('step_order', '>=', $stepOrder)
                ->increment('step_order');

            DripStep::create([
                'agency_id'         => $dripCampaign->agency_id ?? ($user->agency_id ?? null),
                'tenant_id'         => $dripCampaign->tenant_id ?? ($user->tenant_id ?? null),
                'drip_campaign_id'  => $dripCampaign->id,
                'template_id'       => $template->id,
                'step_order'        => $stepOrder,
                'delay_days'        => $delayDays,
                'holiday_mmdd'      => $holidayMmdd,
                'send_time_local'   => $sendTime,
                'is_active'         => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            ]);
        });

        return redirect()
            ->route('settings.messaging.drips.steps', $dripCampaign->id)
            ->with('success', 'Step added.');
    }

    /**
     * Update an existing step.
     */
    public function updateStep(Request $request, DripCampaign $dripCampaign, $dripStep)
    {
        $user = $this->requireAuth();
        $this->enforceCampaignScopeOrAbort($dripCampaign, $user);

        /** @var DripStep $step */
        $step = DripStep::query()->findOrFail((int) $dripStep);

        if ((int) $step->drip_campaign_id !== (int) $dripCampaign->id) {
            abort(404);
        }

        $data = $request->validate([
            'template_id'     => 'required|integer|exists:message_templates,id',
            'step_order'      => 'required|integer|min:1|max:999',
            'delay_days'      => 'nullable|integer|min:0|max:3650',
            'holiday_mmdd'    => 'nullable|string|max:5',
            'send_time_local' => 'nullable|string|max:8',
            'is_active'       => 'nullable|boolean',
        ]);

        $template = MessageTemplate::query()->findOrFail((int) $data['template_id']);
        $this->enforceTemplateScopeOrAbort($template, $user);

        if (in_array($dripCampaign->channel_mode, ['sms', 'email'], true)) {
            if ($template->channel !== $dripCampaign->channel_mode) {
                return back()->withErrors([
                    'template_id' => 'Template channel must match campaign channel.',
                ]);
            }
        }

        $sendTime = $this->normalizeTime($data['send_time_local'] ?? null);

        $newOrder = (int) $data['step_order'];

        $delayDays = $data['delay_days'] ?? null;
        $holidayMmdd = $data['holiday_mmdd'] ?? null;

        if ($dripCampaign->type === 'onboarding_timeline') {
            if ($delayDays === null) {
                return back()->withErrors(['delay_days' => 'Timeline campaigns require a delay (days).']);
            }
            $holidayMmdd = null;
        }

        if (in_array($dripCampaign->type, ['date_holiday', 'date_birthday'], true)) {
            if (!$holidayMmdd) {
                return back()->withErrors(['holiday_mmdd' => 'This campaign type requires a MM-DD date (example: 12-25).']);
            }
            if (!$this->looksLikeMmdd($holidayMmdd)) {
                return back()->withErrors(['holiday_mmdd' => 'Invalid format. Use MM-DD (example: 12-25).']);
            }
            $delayDays = null;
        }

        if ($dripCampaign->type === 'policy_anniversary') {
            $holidayMmdd = null;
            $delayDays = null;
        }

        DB::transaction(function () use ($dripCampaign, $step, $template, $newOrder, $delayDays, $holidayMmdd, $sendTime, $data) {

            $oldOrder = (int) $step->step_order;

            // Reorder within campaign if needed
            if ($newOrder !== $oldOrder) {
                // Close gap / make space
                if ($newOrder > $oldOrder) {
                    DripStep::query()
                        ->where('drip_campaign_id', $dripCampaign->id)
                        ->where('step_order', '>', $oldOrder)
                        ->where('step_order', '<=', $newOrder)
                        ->decrement('step_order');
                } else {
                    DripStep::query()
                        ->where('drip_campaign_id', $dripCampaign->id)
                        ->where('step_order', '>=', $newOrder)
                        ->where('step_order', '<', $oldOrder)
                        ->increment('step_order');
                }
            }

            $step->update([
                'template_id'     => $template->id,
                'step_order'      => $newOrder,
                'delay_days'      => $delayDays,
                'holiday_mmdd'    => $holidayMmdd,
                'send_time_local' => $sendTime,
                'is_active'       => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $step->is_active,
            ]);
        });

        return redirect()
            ->route('settings.messaging.drips.steps', $dripCampaign->id)
            ->with('success', 'Step updated.');
    }

    /**
     * Delete a step.
     */
    public function destroyStep(DripCampaign $dripCampaign, $dripStep)
    {
        $user = $this->requireAuth();
        $this->enforceCampaignScopeOrAbort($dripCampaign, $user);

        /** @var DripStep $step */
        $step = DripStep::query()->findOrFail((int) $dripStep);

        if ((int) $step->drip_campaign_id !== (int) $dripCampaign->id) {
            abort(404);
        }

        DB::transaction(function () use ($dripCampaign, $step) {
            $oldOrder = (int) $step->step_order;
            $step->delete();

            // Pull orders up to fill gap
            DripStep::query()
                ->where('drip_campaign_id', $dripCampaign->id)
                ->where('step_order', '>', $oldOrder)
                ->decrement('step_order');
        });

        return redirect()
            ->route('settings.messaging.drips.steps', $dripCampaign->id)
            ->with('success', 'Step deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
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

    protected function enforceTemplateScopeOrAbort(MessageTemplate $template, $user): void
    {
        if (isset($user->agency_id) && isset($template->agency_id) && (string) $template->agency_id !== (string) $user->agency_id) {
            abort(403, 'Unauthorized');
        }
        if (isset($user->tenant_id) && isset($template->tenant_id) && (string) $template->tenant_id !== (string) $user->tenant_id) {
            abort(403, 'Unauthorized');
        }
    }

    protected function normalizeTime(?string $time): ?string
    {
        $time = trim((string) $time);
        if ($time === '') return null;

        // Accept "HH:MM" or "HH:MM:SS"
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        // Invalid => null (let validation handle in UI if needed)
        return null;
    }

    protected function looksLikeMmdd(string $mmdd): bool
    {
        if (!preg_match('/^\d{2}-\d{2}$/', $mmdd)) return false;

        [$m, $d] = explode('-', $mmdd);
        $m = (int) $m;
        $d = (int) $d;

        if ($m < 1 || $m > 12) return false;
        if ($d < 1 || $d > 31) return false;

        // Not perfect (Feb 31), but good enough for MVP
        return true;
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
