<?php

namespace App\Services\Drips;

use App\Models\CampaignEnrollment;
use App\Models\CampaignEnrollmentStep;
use App\Models\DripCampaign;
use App\Models\Contact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /**
     * Enroll a single contact into a drip campaign.
     * Idempotent: will not duplicate enrollment or steps.
     */
    public function enrollContact(
        DripCampaign $campaign,
        Contact $contact,
        string $source = 'manual',
        ?int $enrolledBy = null
    ): CampaignEnrollment {
        return DB::transaction(function () use ($campaign, $contact, $source, $enrolledBy) {

            // 1️⃣ Create or fetch enrollment
            $enrollment = CampaignEnrollment::firstOrCreate(
                [
                    'drip_campaign_id' => $campaign->id,
                    'contact_id'       => $contact->id,
                ],
                [
                    'agency_id'   => $campaign->agency_id,
                    'tenant_id'   => $campaign->tenant_id,
                    'status'      => 'active',
                    'source'      => $source,
                    'enrolled_by' => $enrolledBy,
                    'enrolled_at' => now(),
                    'started_at'  => now(),
                ]
            );

            // If enrollment already existed, don't reschedule steps
            if ($enrollment->wasRecentlyCreated === false) {
                return $enrollment;
            }

            // 2️⃣ Generate steps
            $this->scheduleSteps($campaign, $enrollment);

            return $enrollment;
        });
    }

    /**
     * Schedule steps for an enrollment.
     * Server timezone assumed.
     */
    protected function scheduleSteps(
        DripCampaign $campaign,
        CampaignEnrollment $enrollment
    ): void {
        $baseTime = now(); // server timezone (MVP assumption)

        foreach ($campaign->steps()->active()->get() as $step) {

            $scheduledFor = null;

            // ─────────────────────────────────────────
            // TIMELINE CAMPAIGN (e.g. 90-day welcome)
            // ─────────────────────────────────────────
            if ($campaign->type === 'onboarding_timeline') {
                if ($step->delay_days === null) {
                    continue;
                }

                $scheduledFor = $baseTime->copy()->addDays($step->delay_days);
            }

            // ─────────────────────────────────────────
            // HOLIDAY / DATE-BASED CAMPAIGN
            // ─────────────────────────────────────────
            if (in_array($campaign->type, [
                'date_holiday',
                'date_birthday',
                'date_client_anniversary',
            ], true)) {

                if (!$step->holiday_mmdd) {
                    continue;
                }

                [$month, $day] = explode('-', $step->holiday_mmdd);

                $year = (int) $baseTime->year;
                $candidate = Carbon::create($year, $month, $day, 0, 0, 0);

                // If date already passed this year, schedule next year
                if ($candidate->lessThanOrEqualTo($baseTime)) {
                    $candidate->addYear();
                }

                $scheduledFor = $candidate;
            }

            if (!$scheduledFor) {
                continue;
            }

            // Apply send_time_local if provided (server timezone)
            if (!empty($step->send_time_local)) {
                [$h, $m] = explode(':', $step->send_time_local);
                $scheduledFor->setTime((int) $h, (int) $m);
            }

            CampaignEnrollmentStep::create([
                'agency_id' => $campaign->agency_id,
                'tenant_id' => $campaign->tenant_id,

                'campaign_enrollment_id' => $enrollment->id,
                'drip_step_id'           => $step->id,

                'scheduled_for' => $scheduledFor,
                'status'        => 'scheduled',
                'attempts'      => 0,
            ]);
        }
    }
}
