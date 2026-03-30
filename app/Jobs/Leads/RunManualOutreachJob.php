<?php

namespace App\Jobs\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadMessage;
use App\Models\OutreachTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RunManualOutreachJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $leadId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lead = Lead::query()
            ->with([
                'campaign:id,solution_name',
                'contacts:id,lead_id,email,contact_name',
                'messages' => fn ($query) => $query->latest('version')->latest('id')->limit(1),
            ])
            ->find($this->leadId);

        if (! $lead) {
            return;
        }

        if (! (bool) config('endex.outreach.enabled', false)) {
            $this->record($lead->id, 'outreach.skipped.disabled', [
                'mode' => (string) config('endex.outreach.mode', 'manual_review_only'),
            ]);

            return;
        }

        $mode = (string) config('endex.outreach.mode', 'manual_review_only');

        if ($mode !== 'manual_review_only') {
            $this->record($lead->id, 'outreach.skipped.unsupported_mode', [
                'mode' => $mode,
            ]);

            return;
        }

        if ($lead->status !== LeadStatus::Approved) {
            $this->record($lead->id, 'outreach.skipped.manual_gate', [
                'required_status' => LeadStatus::Approved->value,
                'current_status' => $lead->status->value,
            ]);

            return;
        }

        $alreadySentForLeadToday = LeadActivity::query()
            ->where('lead_id', $lead->id)
            ->where('event', 'outreach.sent')
            ->whereDate('occurred_at', today())
            ->exists();

        if ($alreadySentForLeadToday) {
            $this->record($lead->id, 'outreach.skipped.idempotent', [
                'reason' => 'already_sent_same_day',
            ]);

            return;
        }

        $dailyLimit = max(1, (int) config('endex.outreach.daily_send_limit', 25));
        $sentToday = LeadActivity::query()
            ->where('event', 'outreach.sent')
            ->whereDate('occurred_at', today())
            ->count();

        if ($sentToday >= $dailyLimit) {
            $this->record($lead->id, 'outreach.blocked.daily_limit', [
                'daily_limit' => $dailyLimit,
                'sent_today' => $sentToday,
            ]);

            return;
        }

        $channel = (string) config('endex.outreach.channel', 'email');
        $templateKey = (string) config('endex.outreach.template_key', 'default');

        $template = OutreachTemplate::query()
            ->where('channel', $channel)
            ->where('template_key', $templateKey)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if ($template) {
            $message = LeadMessage::query()->create([
                'lead_id' => $lead->id,
                'channel' => $channel,
                'subject' => $this->renderTemplate($template->subject_template, $lead),
                'body' => $this->renderTemplate($template->body_template, $lead),
                'tone' => 'consultivo',
                'generated_by_agent' => 'OutreachTemplateEngine',
                'version' => $template->version,
            ]);
        } else {
            $message = $lead->messages->first();
        }

        if (! $message) {
            $this->record($lead->id, 'outreach.blocked.no_message', [
                'mode' => $mode,
                'channel' => $channel,
                'template_key' => $templateKey,
            ]);

            return;
        }

        $recipientEmail = $lead->contacts
            ->pluck('email')
            ->filter(fn (?string $email): bool => filled($email))
            ->values()
            ->first();

        if (! is_string($recipientEmail) || trim($recipientEmail) === '') {
            $this->record($lead->id, 'outreach.blocked.no_recipient', [
                'channel' => $message->channel,
                'message_version' => $message->version,
            ]);

            return;
        }

        try {
            Mail::mailer('ses')
                ->to($recipientEmail)
                ->send(new class($message->subject, $message->body) extends Mailable
                {
                    public function __construct(public ?string $subjectLine, public string $bodyText) {}

                    public function build(): self
                    {
                        return $this->subject($this->subjectLine ?? 'Propuesta Endex')
                            ->text('emails.outreach_plain', [
                                'bodyText' => $this->bodyText,
                            ]);
                    }
                });
        } catch (Throwable $exception) {
            $this->record($lead->id, 'outreach.failed.send_exception', [
                'channel' => $message->channel,
                'recipient' => $recipientEmail,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $this->record($lead->id, 'outreach.sent', [
            'mode' => $mode,
            'channel' => $message->channel,
            'recipient' => $recipientEmail,
            'subject' => $message->subject,
            'message_version' => $message->version,
            'template_id' => $template?->id,
            'provider' => 'aws_ses',
        ]);
    }

    private function renderTemplate(?string $content, Lead $lead): ?string
    {
        if ($content === null) {
            return null;
        }

        return strtr($content, [
            '{{company_name}}' => $lead->company_name,
            '{{city}}' => $lead->city ?? 'tu ciudad',
            '{{sector}}' => $lead->sector ?? 'tu sector',
            '{{solution_name}}' => $lead->campaign?->solution_name ?? 'Endex',
        ]);
    }

    /**
     * @param  array<string, mixed>  $eventData
     */
    private function record(int $leadId, string $event, array $eventData): void
    {
        LeadActivity::query()->create([
            'lead_id' => $leadId,
            'actor_type' => 'system',
            'actor_name' => 'RunManualOutreachJob',
            'event' => $event,
            'event_data' => $eventData,
            'occurred_at' => now(),
        ]);
    }
}
