<?php

namespace App\Console\Commands;

use App\Models\HelpdeskMessage;
use App\Models\HelpdeskRecord;
use App\Models\Ticket;
use App\Services\HelpdeskMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendHelpdeskMail extends Command
{
    protected $signature = 'helpdesk:send-mail {--limit=50} {--message= : Deliver only this outgoing message ID}';

    protected $description = 'Queue overdue notices and deliver pending helpdesk messages';

    public function handle(): int
    {
        $settings = HelpdeskRecord::ofKind('mail_settings')->first()?->settings ?? [];
        $mode = $settings['mode'] ?? config('mail.default');
        if ($mode === 'disabled') {
            $this->info('Email delivery is disabled.');

            return self::SUCCESS;
        }
        if ($mode === 'smtp' && $settings) {
            config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => $settings['host'], 'mail.mailers.smtp.port' => (int) $settings['port'],
                'mail.mailers.smtp.scheme' => $settings['scheme'] ?? 'smtp', 'mail.mailers.smtp.username' => $settings['username'] ?? null,
                'mail.mailers.smtp.password' => ! empty($settings['password']) ? Crypt::decryptString($settings['password']) : null,
                'mail.mailers.smtp.timeout' => 20, 'mail.from.address' => $settings['from_address'], 'mail.from.name' => $settings['from_name']]);
        } elseif ($mode === 'log') {
            config(['mail.default' => 'log']);
        }
        if (! $this->option('message')) {
            foreach (Ticket::where('due_at', '<', now())->whereNotIn('status', ['closed', 'resolved'])->with(['agent', 'user'])->cursor() as $ticket) {
                app(HelpdeskMail::class)->overdue($ticket);
            }
        }
        HelpdeskMessage::where('direction', 'out')->where('status', 'sending')->where('updated_at', '<', now()->subMinutes(10))->update(['status' => 'failed']);
        $ids = HelpdeskMessage::where('direction', 'out')->when($this->option('message'), fn ($q) => $q->whereKey((int) $this->option('message')))->whereIn('status', ['pending', 'failed'])->where('attempts', '<', 5)->orderBy('id')->limit(max(1, min(500, (int) $this->option('limit'))))->pluck('id');
        $count = 0;
        foreach ($ids as $id) {
            $message = DB::transaction(function () use ($id) {
                $m = HelpdeskMessage::whereKey($id)->lockForUpdate()->first();
                if (! $m || ! in_array($m->status, ['pending', 'failed']) || $m->attempts >= 5) {
                    return null;
                }
                $m->update(['status' => 'sending', 'attempts' => $m->attempts + 1]);

                return $m;
            });
            if (! $message) {
                continue;
            }
            try {
                Mail::send(['html' => 'mail.notification', 'text' => 'mail.notification-text'], ['notification' => $message], function ($mail) use ($message, $settings) {
                    $mail->to($message->recipient)->subject($message->subject);
                    if (! empty($settings['reply_to'])) {
                        $mail->replyTo($settings['reply_to']);
                    }
                });
                $message->update(['status' => in_array(config('mail.default'), ['log', 'array']) ? 'logged' : 'sent', 'error' => null]);
                $count++;
            } catch (\Throwable $e) {
                $message->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 2000)]);
            }
        }
        $this->info("Processed {$count} messages. Transport: ".config('mail.default'));

        return self::SUCCESS;
    }
}

