<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MessagingSeeder extends Seeder
{
    public function run(): void
    {
        // These are "global defaults" (agency_id/tenant_id = null) that any agent can copy/use/edit/delete.
        // We seed campaigns as PAUSED so nothing auto-sends until you explicitly activate + enroll.
        $agencyId = null;
        $tenantId = null;
        $createdBy = null;

        DB::transaction(function () use ($agencyId, $tenantId, $createdBy) {

            // -------------------------
            // 1) Templates (idempotent)
            // -------------------------
            $templates = [
                // SMS templates
                [
                    'channel' => 'sms',
                    'name' => 'Welcome (Client Onboarding - Day 0)',
                    'subject' => null,
                    'body' => "Hi {{first_name}} — welcome! I’m {{agent_name}}. If you ever need anything, reply here and I’ll help.",
                    'variables_json' => ['first_name','agent_name'],
                ],
                [
                    'channel' => 'sms',
                    'name' => 'Onboarding Check-in (Day 2)',
                    'subject' => null,
                    'body' => "Hey {{first_name}}, quick check-in — do you have any questions about your coverage or next steps?",
                    'variables_json' => ['first_name'],
                ],
                [
                    'channel' => 'sms',
                    'name' => 'Onboarding Value (Day 7)',
                    'subject' => null,
                    'body' => "Hi {{first_name}} — reminder: if anything changes (address, job, family), tell me so we keep you properly protected.",
                    'variables_json' => ['first_name'],
                ],
                [
                    'channel' => 'sms',
                    'name' => 'Birthday Greeting',
                    'subject' => null,
                    'body' => "Happy Birthday, {{first_name}}! 🎉 — {{agent_name}}",
                    'variables_json' => ['first_name','agent_name'],
                ],
                [
                    'channel' => 'sms',
                    'name' => 'Holiday Greeting (Generic)',
                    'subject' => null,
                    'body' => "Happy Holidays, {{first_name}}! Wishing you and your family the best. — {{agent_name}}",
                    'variables_json' => ['first_name','agent_name'],
                ],

                // Email templates
                [
                    'channel' => 'email',
                    'name' => 'Welcome Email (Client Onboarding - Day 0)',
                    'subject' => "Welcome, {{first_name}}",
                    'body' => "Hi {{first_name}},\n\nWelcome! I’m {{agent_name}} and I’m here to help anytime you need it.\n\nIf anything changes in your life (address, job, family), please let me know so we can keep your coverage aligned.\n\nThanks,\n{{agent_name}}",
                    'variables_json' => ['first_name','agent_name'],
                ],
                [
                    'channel' => 'email',
                    'name' => 'Birthday Email',
                    'subject' => "Happy Birthday, {{first_name}}!",
                    'body' => "Hi {{first_name}},\n\nHappy Birthday! Wishing you a great year ahead.\n\n— {{agent_name}}",
                    'variables_json' => ['first_name','agent_name'],
                ],
            ];

            $templateIdsByNameChannel = [];

            foreach ($templates as $t) {
                // Idempotent upsert keyed by (agency_id, tenant_id, channel, name)
                $existing = DB::table('message_templates')
                    ->whereNull('agency_id')
                    ->whereNull('tenant_id')
                    ->where('channel', $t['channel'])
                    ->where('name', $t['name'])
                    ->first();

                if ($existing) {
                    DB::table('message_templates')->where('id', $existing->id)->update([
                        'subject' => $t['subject'],
                        'body' => $t['body'],
                        'variables_json' => json_encode($t['variables_json']),
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);
                    $templateIdsByNameChannel[$t['channel'].'|'.$t['name']] = (int) $existing->id;
                } else {
                    $id = (int) DB::table('message_templates')->insertGetId([
                        'agency_id' => $agencyId,
                        'tenant_id' => $tenantId,
                        'channel' => $t['channel'],
                        'name' => $t['name'],
                        'subject' => $t['subject'],
                        'body' => $t['body'],
                        'is_active' => true,
                        'variables_json' => json_encode($t['variables_json']),
                        'created_by' => $createdBy,
                        'updated_by' => $createdBy,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'deleted_at' => null,
                    ]);

                    $templateIdsByNameChannel[$t['channel'].'|'.$t['name']] = $id;
                }
            }

            // -------------------------
            // 2) Campaigns (idempotent)
            // -------------------------
            $campaigns = [
                [
                    'name' => 'Client Onboarding (90 Day)',
                    'type' => 'onboarding_timeline',
                    'status' => 'paused',          // important: safe default
                    'channel_mode' => 'mixed',     // future use; steps use template channel anyway
                    'description' => 'Starter 90-day onboarding touchpoints for new clients.',
                ],
                [
                    'name' => 'Birthday Touch',
                    'type' => 'date_birthday',
                    'status' => 'paused',          // important: safe default
                    'channel_mode' => 'mixed',
                    'description' => 'Annual birthday greeting to keep your name top-of-mind.',
                ],
            ];

            $campaignIdsByName = [];

            foreach ($campaigns as $c) {
                $existing = DB::table('drip_campaigns')
                    ->whereNull('agency_id')
                    ->whereNull('tenant_id')
                    ->where('type', $c['type'])
                    ->where('name', $c['name'])
                    ->first();

                if ($existing) {
                    DB::table('drip_campaigns')->where('id', $existing->id)->update([
                        'status' => $c['status'],
                        'channel_mode' => $c['channel_mode'],
                        'description' => $c['description'],
                        'updated_at' => now(),
                    ]);
                    $campaignIdsByName[$c['name']] = (int) $existing->id;
                } else {
                    $id = (int) DB::table('drip_campaigns')->insertGetId([
                        'agency_id' => $agencyId,
                        'tenant_id' => $tenantId,
                        'name' => $c['name'],
                        'type' => $c['type'],
                        'status' => $c['status'],
                        'channel_mode' => $c['channel_mode'],
                        'description' => $c['description'],
                        'created_by' => $createdBy,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'deleted_at' => null,
                    ]);
                    $campaignIdsByName[$c['name']] = $id;
                }
            }

            // -------------------------
            // 3) Steps (safe defaults)
            // -------------------------
            // Onboarding: Day 0, Day 2, Day 7 (you can expand later)
            $onboardingId = $campaignIdsByName['Client Onboarding (90 Day)'];

            $onboardingSteps = [
                [
                    'step_order' => 1,
                    'delay_days' => 0,
                    'template_key' => 'sms|Welcome (Client Onboarding - Day 0)',
                ],
                [
                    'step_order' => 2,
                    'delay_days' => 2,
                    'template_key' => 'sms|Onboarding Check-in (Day 2)',
                ],
                [
                    'step_order' => 3,
                    'delay_days' => 7,
                    'template_key' => 'sms|Onboarding Value (Day 7)',
                ],
            ];

            foreach ($onboardingSteps as $s) {
                $templateId = $templateIdsByNameChannel[$s['template_key']] ?? null;
                if (! $templateId) {
                    continue;
                }

                // Unique key is (drip_campaign_id, step_order)
                $existing = DB::table('drip_steps')
                    ->where('drip_campaign_id', $onboardingId)
                    ->where('step_order', $s['step_order'])
                    ->first();

                $payload = [
                    'agency_id' => $agencyId,
                    'tenant_id' => $tenantId,
                    'drip_campaign_id' => $onboardingId,
                    'template_id' => $templateId,
                    'step_order' => $s['step_order'],
                    'delay_days' => $s['delay_days'],
                    'holiday_mmdd' => null,
                    'send_time_local' => '09:00:00',
                    'is_active' => true,
                    'updated_at' => now(),
                ];

                if ($existing) {
                    DB::table('drip_steps')->where('id', $existing->id)->update($payload);
                } else {
                    $payload['created_at'] = now();
                    DB::table('drip_steps')->insert($payload);
                }
            }

            // Birthday: single step (uses SMS birthday template by default)
            $birthdayId = $campaignIdsByName['Birthday Touch'];

            $birthdayTemplateId = $templateIdsByNameChannel['sms|Birthday Greeting'] ?? null;
            if ($birthdayTemplateId) {
                $existing = DB::table('drip_steps')
                    ->where('drip_campaign_id', $birthdayId)
                    ->where('step_order', 1)
                    ->first();

                $payload = [
                    'agency_id' => $agencyId,
                    'tenant_id' => $tenantId,
                    'drip_campaign_id' => $birthdayId,
                    'template_id' => $birthdayTemplateId,
                    'step_order' => 1,
                    'delay_days' => null,
                    'holiday_mmdd' => null,
                    'send_time_local' => '09:00:00',
                    'is_active' => true,
                    'updated_at' => now(),
                ];

                if ($existing) {
                    DB::table('drip_steps')->where('id', $existing->id)->update($payload);
                } else {
                    $payload['created_at'] = now();
                    DB::table('drip_steps')->insert($payload);
                }
            }
        });
    }
}
