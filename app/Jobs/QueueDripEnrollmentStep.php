<?php

namespace App\Jobs;

use App\Models\CampaignEnrollmentStep;
use App\Services\Messaging\MessageOutbox;
use App\Services\Messaging\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class QueueDripEnrollmentStep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $enrollmentStepId) {}

    public function handle(TemplateRenderer $renderer, MessageOutbox $outbox): void
    {
        DB::transaction(function () use ($renderer, $outbox) {
            /** @var CampaignEnrollmentStep $ces */
            $ces = CampaignEnrollmentStep::query()->lockForUpdate()->findOrFail($this->enrollmentStepId);

            if ($ces->status !== 'scheduled') {
                return; // idempotent
            }

            $enrollment = $ces->enrollment()->firstOrFail();
            $contact = $enrollment->contact()->firstOrFail();
            $dripStep = $ces->dripStep()->with('template')->firstOrFail();
            $template = $dripStep->template;

            if (!$template || !$template->is_active) {
                $ces->markSkipped('template_inactive');
                return;
            }

            $cfg = config('abc_messaging.contact_fields');
            $opt = config('abc_messaging.opt_out_methods');

            $channel = $template->channel;

            // Determine recipient
            $toAddress = null;
            if ($channel === 'sms') {
                $toAddress = data_get($contact, $cfg['phone']);
                if (!$toAddress) { $ces->markSkipped('no_phone'); return; }
                if (method_exists($contact, $opt['sms']) && $contact->{$opt['sms']}()) {
                    $ces->markSkipped('opted_out_sms'); return;
                }
            } else {
                $toAddress = data_get($contact, $cfg['email']);
                if (!$toAddress) { $ces->markSkipped('no_email'); return; }
                if (method_exists($contact, $opt['email']) && $contact->{$opt['email']}()) {
                    $ces->markSkipped('opted_out_email'); return;
                }
            }

            // Agent context: use enrolled_by if available
            $agent = null;
            if (!empty($enrollment->enrolled_by)) {
                $agentClass = config('auth.providers.users.model', \App\Models\User::class);
                /** @var Model|null $agent */
                $agent = $agentClass::query()->find($enrollment->enrolled_by);
            }

            $agency = []; // optional: fill later from agency settings table if you have it

            $subject = $template->subject
                ? $renderer->render($template->subject, $contact, $agent, $agency)
                : null;

            $body = $renderer->render($template->body, $contact, $agent, $agency);

            $messageId = $outbox->queue([
                'agency_id' => $enrollment->agency_id,
                'tenant_id' => $enrollment->tenant_id,
                'contact_id' => $contact->id,
                'created_by' => $enrollment->enrolled_by,

                'campaign_enrollment_step_id' => $ces->id,

                'channel' => $channel,
                'to_address' => $toAddress,
                'subject' => $subject,
                'body' => $body,
            ]);

            $ces->status = 'queued';
            $ces->queued_at = now();
            $ces->message_id = $messageId;
            $ces->save();
        });
    }

    public function failed(Throwable $e): void
    {
        CampaignEnrollmentStep::query()
            ->whereKey($this->enrollmentStepId)
            ->update([
                'status' => 'failed',
                'attempts' => DB::raw('attempts + 1'),
                'last_error' => substr($e->getMessage(), 0, 2000),
                'updated_at' => now(),
            ]);
    }
}
