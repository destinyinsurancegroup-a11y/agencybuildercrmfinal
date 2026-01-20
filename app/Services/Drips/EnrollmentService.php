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

            // If enrollment already existed, don't reschedule steps (MVP behavior).
            if ($enrollment->wasRecentlyCreated === false) {
                return $enrollment;
            }

            // 2️⃣ Generate steps
            $this->scheduleSteps($campaign, $enrollment, $contact);

            return $enrollment;
        });
    }

    /**
     * Schedule steps for an enrollment.
     * Server timezone assumed.
     */
    protected function scheduleSteps(
        DripCampaign $campaign,
        CampaignEnrollment $enrollment,
        Contact $contact
    ): void {
        $baseTime = now(); // server timezone (MVP assumption)

        $cfg = (array) config('abc_messaging.contact_fields', []);
        $defaultSendTime = (string) config('abc_messaging.default_send_time_local', '09:00:00');

        foreach ($campaign->steps()->where('is_active', true)->get() as $step) {

            $scheduledFor = null;

            // ─────────────────────────────────────────
            // 1) TIMELINE CAMPAIGN (e.g. 90-day welcome)
            // ─────────────────────────────────────────
            if ($campaign->type === 'onboarding_timeline') {
                if ($step->delay_days === null) {
                    continue;
                }

                $scheduledFor = $baseTime->copy()->addDays((int) $step->delay_days);
            }

            // ─────────────────────────────────────────
            // 2) HOLIDAY CAMPAIGN (same date for everyone)
            // ─────────────────────────────────────────
            if ($campaign->type === 'date_holiday') {
                if (empty($step->holiday_mmdd)) {
                    continue;
                }

                $scheduledFor = $this->nextOccurrenceFromMmdd((string) $step->holiday_mmdd, $baseTime);
                if (!$scheduledFor) {
                    continue;
                }
            }

            // ─────────────────────────────────────────
            // 3) BIRTHDAY (per-contact)
            // ─────────────────────────────────────────
            if ($campaign->type === 'date_birthday') {
                $birthdayField = $cfg['birthday'] ?? 'birthday';
                $birthdayVal = data_get($contact, $birthdayField);

                $scheduledFor = $this->nextOccurrenceFromDateValue($birthdayVal, $baseTime);
                if (!$scheduledFor) {
                    continue; // no birthday stored
                }
            }

            // ─────────────────────────────────────────
            // 4) POLICY ANNIVERSARY (per-contact)
            // Based on policy issue date (Initial Draft Date)
            // Only for clients (Book of Business)
            // ─────────────────────────────────────────
            if ($campaign->type === 'policy_anniversary') {

                if (!$this->isClientContact($contact, $cfg)) {
                    continue; // don't schedule for non-clients
                }

                $issueField = $cfg['policy_issue_date'] ?? null;
                if (empty($issueField)) {
                    continue; // mapping not configured
                }

                $issueVal = data_get($contact, $issueField);

                $scheduledFor = $this->nextOccurrenceFromDateValue($issueVal, $baseTime);
                if (!$scheduledFor) {
                    continue; // no issue date stored
                }
            }

            if (!$scheduledFor) {
                continue;
            }

            // Apply send_time_local if provided, otherwise default
            $sendTime = !empty($step->send_time_local)
                ? (string) $step->send_time_local
                : $defaultSendTime;

            $scheduledFor = $this->applySendTime($scheduledFor, $sendTime);

            // Create scheduled row
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

    /**
     * Determine if a contact is a "client" using config rules.
     * This avoids hard-coding contact_type/status logic.
     */
    protected function isClientContact(Contact $contact, array $cfg): bool
    {
        $rules = $cfg['is_client'] ?? null;
        if (!is_array($rules)) {
            // If not configured, default to true (to avoid silently disabling),
            // but you DO have it configured in your file.
            return true;
        }

        $mode = $rules['mode'] ?? 'string';
        $field = $rules['field'] ?? 'status';

        if ($mode === 'boolean') {
            return (bool) data_get($contact, $field);
        }

        if ($mode === 'date_not_null') {
            $val = data_get($contact, $field);
            return !empty($val);
        }

        // mode === 'string'
        $expected = (string) ($rules['status_value'] ?? 'client');
        $actual = (string) (data_get($contact, $field) ?? '');
        return strtolower($actual) === strtolower($expected);
    }

    /**
     * Given "MM-DD" (e.g. "12-25"), returns next occurrence date (server timezone).
     */
    protected function nextOccurrenceFromMmdd(string $mmdd, Carbon $baseTime): ?Carbon
    {
        $mmdd = trim($mmdd);
        if (!preg_match('/^\d{2}-\d{2}$/', $mmdd)) {
            return null;
        }

        [$month, $day] = explode('-', $mmdd);

        $candidate = Carbon::create(
            (int) $baseTime->year,
            (int) $month,
            (int) $day,
            0, 0, 0
        );

        if ($candidate->lessThanOrEqualTo($baseTime)) {
            $candidate->addYear();
        }

        return $candidate;
    }

    /**
     * Accepts a date-ish value (Carbon, DateTime, "YYYY-MM-DD", etc.)
     * Returns the next annual occurrence for that month/day.
     */
    protected function nextOccurrenceFromDateValue($value, Carbon $baseTime): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            $dt = $value instanceof Carbon ? $value : Carbon::parse($value);

            $candidate = Carbon::create(
                (int) $baseTime->year,
                (int) $dt->month,
                (int) $dt->day,
                0, 0, 0
            );

            if ($candidate->lessThanOrEqualTo($baseTime)) {
                $candidate->addYear();
            }

            return $candidate;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Apply HH:MM or HH:MM:SS time to a Carbon date (server timezone).
     */
    protected function applySendTime(Carbon $date, string $time): Carbon
    {
        $parts = explode(':', trim($time));
        $h = (int) ($parts[0] ?? 9);
        $m = (int) ($parts[1] ?? 0);

        return $date->copy()->setTime($h, $m, 0);
    }
}
