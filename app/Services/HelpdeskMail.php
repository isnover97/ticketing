<?php

namespace App\Services;

use App\Models\HelpdeskMessage;
use App\Models\HelpdeskRecord;
use App\Models\Ticket;
use App\Support\HelpdeskEmailUrl;
use Illuminate\Support\Str;

class HelpdeskMail
{
    private function queue(Ticket $ticket, string $event, string $recipient, string $message, ?string $key = null, ?string $subjectLabel = null): void
    {
        $template = HelpdeskRecord::ofKind('mail_templates')->active()->where('settings->event', $event)->first();
        $values = ['{ticket_number}' => $ticket->ticket_number, '{name}' => $ticket->user->name, '{subject}' => $ticket->title, '{message}' => $message, '{url}' => HelpdeskEmailUrl::route('helpdesk.ticket', $ticket)];
        $defaultSubject = $subjectLabel ? '[{ticket_number}] '.$subjectLabel.': {subject}' : '[{ticket_number}] {subject}';
        $subject = strtr($template?->settings['subject'] ?? $defaultSubject, $values);
        $body = strtr($template?->settings['body'] ?? "Hello {name},\n\n{message}\n\nTicket: {ticket_number}\nSign in to view: {url}", $values);
        $queued = HelpdeskMessage::firstOrCreate(['message_key' => $key ?? (string) Str::uuid()], [
            'ticket_id' => $ticket->id, 'direction' => 'out', 'recipient' => $recipient, 'subject' => mb_substr($subject, 0, 255), 'body' => $body, 'status' => 'pending',
        ]);
        if ($queued->wasRecentlyCreated && ! app()->runningInConsole()) {
            \Illuminate\Support\Facades\DB::afterCommit(function () use ($queued) {
                app()->terminating(function () use ($queued) {
                    try {
                        \Illuminate\Support\Facades\Artisan::call('helpdesk:send-mail', ['--message' => $queued->id]);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                });
            });
        }
    }

    public function opened(Ticket $ticket): void
    {
        if (config('helpdesk.autorespond')) {
            $this->queue($ticket, 'opened', $ticket->user->email, 'Your support request has been received.', 'opened-'.$ticket->id, 'Ticket received');
        }
    }

    public function replied(Ticket $ticket, string $body): void
    {
        $this->queue($ticket, 'reply', $ticket->user->email, $body, subjectLabel: 'New support reply');
    }

    public function statusChanged(Ticket $ticket, string $old): void
    {
        if ($old === $ticket->status) {
            return;
        }
        if ($ticket->status === 'closed') {
            $this->queue($ticket, 'status', $ticket->user->email, 'Your support ticket has been closed.', subjectLabel: 'Ticket closed');

            return;
        }
        $label = ucwords(str_replace('_', ' ', $ticket->status));
        $this->queue($ticket, 'status', $ticket->user->email, "Your ticket status has changed to {$label}.");
    }

    public function assigned(Ticket $ticket): void
    {
        $ticket->load(['agent', 'team', 'user']);
        $name = $ticket->agent?->name ?? $ticket->team?->name ?? 'Unassigned';
        $this->queue($ticket, 'assignment', $ticket->user->email, "Your ticket assignment has been updated: {$name}.");
        if ($ticket->agent?->is_active && $ticket->agent->email !== $ticket->user->email) {
            $this->queue($ticket, 'assignment', $ticket->agent->email, "A support ticket from {$ticket->user->name} has been assigned to you.");
        }
    }

    public function overdue(Ticket $ticket): void
    {
        if ($ticket->agent && $ticket->agent->is_active) {
            $this->queue($ticket, 'overdue', $ticket->agent->email, 'This ticket has passed its SLA due date.', 'overdue-'.$ticket->id.'-'.$ticket->due_at?->timestamp);
        }
    }
}
