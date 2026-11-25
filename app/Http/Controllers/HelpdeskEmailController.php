<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HelpdeskMessage;
use App\Models\HelpdeskRecord;
use App\Models\Ticket;
use App\Services\HelpdeskMail;
use App\Services\TicketWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HelpdeskEmailController extends Controller
{
    public function index()
    {
        $settings = HelpdeskRecord::ofKind('mail_settings')->first()?->settings ?? [];
        unset($settings['password'],$settings['inbound_secret']);

        return view('helpdesk.email', [
            'settings' => $settings, 'messages' => HelpdeskMessage::with('ticket')->latest()->paginate(30),
            'inboundReady' => ! empty(config('helpdesk.inbound_secret')) || ! empty(HelpdeskRecord::ofKind('mail_settings')->first()?->settings['inbound_secret']),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'mode' => 'required|in:disabled,log,smtp', 'host' => 'nullable|required_if:mode,smtp|string|max:255',
            'port' => 'nullable|required_if:mode,smtp|integer|min:1|max:65535', 'scheme' => 'nullable|in:smtp,smtps',
            'username' => 'nullable|string|max:255', 'password' => 'nullable|string|max:2000',
            'from_address' => 'required|email|max:255', 'from_name' => 'required|string|max:255',
            'reply_to' => 'nullable|email|max:255', 'inbound_secret' => 'nullable|string|min:32|max:2000',
        ]);
        $record = HelpdeskRecord::ofKind('mail_settings')->first();
        $old = $record?->settings ?? [];
        foreach (['password', 'inbound_secret'] as $key) {
            $data[$key] = ! empty($data[$key]) ? Crypt::encryptString($data[$key]) : ($old[$key] ?? null);
        }
        if ($record) {
            $record->update(['settings' => $data]);
        } else {
            HelpdeskRecord::create(['kind' => 'mail_settings', 'name' => 'Mail configuration', 'settings' => $data, 'is_active' => true]);
        }
        app(TicketWorkflow::class)->event(null, $request->user(), 'email_settings', 'Email transport settings updated.');

        return back()->with('status', 'Email settings saved. Passwords and signing secrets are encrypted.');
    }

    public function test(Request $request)
    {
        HelpdeskMessage::create(['message_key' => (string) Str::uuid(), 'direction' => 'out',
            'recipient' => $request->user()->email, 'subject' => 'PREI HelpDesk mail delivery test', 'body' => 'Your helpdesk outbound email delivery is working.', 'status' => 'pending']);

        return back()->with('status', 'A test message was queued to your administrator email. Run the mail worker to deliver it.');
    }

    public function inbound(Request $request)
    {
        $secret = config('helpdesk.inbound_secret');
        if (! $secret) {
            $encrypted = HelpdeskRecord::ofKind('mail_settings')->first()?->settings['inbound_secret'];
            if ($encrypted) {
                $secret = Crypt::decryptString($encrypted);
            }
        }
        abort_unless($secret, 503, 'Inbound email is not configured.');
        $timestamp = $request->header('X-Helpdesk-Timestamp', '');
        abort_unless(ctype_digit($timestamp) && abs(time() - (int) $timestamp) <= 300, 401);
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        abort_unless(hash_equals($expected, $request->header('X-Helpdesk-Signature', '')), 401);
        $data = $request->validate([
            'message_id' => 'required|string|max:255', 'from' => 'required|email|max:255', 'name' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255', 'body' => 'required|string|max:20000',
            'ticket_number' => 'nullable|string|max:255', 'category_id' => 'nullable|exists:categories,id',
            'help_topic_id' => 'nullable|integer', 'custom_fields' => 'nullable|array', 'automatic' => 'sometimes|boolean',
        ]);
        if (! empty($data['automatic'])) {
            return response()->json(['message' => 'Automatic message ignored.'], 202);
        }
        $key = 'in-'.hash('sha256', $data['message_id']);
        $existing = HelpdeskMessage::where('message_key', $key)->first();
        if ($existing) {
            return response()->json(['ticket_id' => $existing->ticket_id, 'duplicate' => true]);
        }
        $ticket = DB::transaction(function () use ($data, $key) {
            $message = HelpdeskMessage::create(['message_key' => $key, 'direction' => 'in', 'recipient' => strtolower($data['from']),
                'subject' => $data['subject'], 'body' => $data['body'], 'status' => 'received']);
            $workflow = app(TicketWorkflow::class);
            if (! empty($data['ticket_number'])) {
                $ticket = Ticket::where('ticket_number', $data['ticket_number'])->firstOrFail();
                abort_unless(strcasecmp($ticket->user->email, $data['from']) === 0, 403, 'The sender does not own this ticket.');
                $ticket->comments()->create(['user_id' => $ticket->user_id, 'body' => $data['body'], 'is_internal' => false]);
                if (in_array($ticket->status, ['closed', 'resolved'])) {
                    $ticket->update(['status' => 'open']);
                }
                $ticket->touch();
                $workflow->event($ticket, $ticket->user, 'email_reply', 'Requester replied by email.');
            } else {
                $incoming = Request::create('/', 'POST', [
                    'requester_email' => $data['from'], 'requester_name' => $data['name'] ?? $data['from'],
                    'title' => $data['subject'], 'description' => $data['body'], 'priority' => 'medium',
                    'category_id' => $data['category_id'] ?? Category::value('id'),
                    'help_topic_id' => $data['help_topic_id'] ?? null, 'custom_fields' => $data['custom_fields'] ?? [],
                ]);
                $incoming->setUserResolver(fn () => null);
                $ticket = $workflow->create($incoming);
                app(HelpdeskMail::class)->opened($ticket);
            }
            $message->update(['ticket_id' => $ticket->id]);

            return $ticket;
        });

        return response()->json(['ticket_id' => $ticket->id, 'ticket_number' => $ticket->ticket_number],201);
    }
}
