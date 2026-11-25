<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\HelpdeskApiKey;
use App\Models\HelpdeskMessage;
use App\Models\HelpdeskRecord;
use App\Models\HelpdeskTask;
use App\Models\Ticket;
use App\Models\User;
use App\Services\BusinessClock;
use App\Services\HelpdeskMail;
use App\Services\TicketWorkflow;
use App\Support\HelpdeskCatalog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class HelpdeskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('An in-memory test database is required.');
        }
    }

    private function record(string $kind, array $settings = [], string $name = 'Test record'): Department|HelpdeskRecord
    {
        if ($kind === 'departments') {
            return Department::create(['name' => $name, 'settings' => $settings, 'is_active' => true]);
        }

        return HelpdeskRecord::create(['kind' => $kind, 'name' => $name, 'settings' => $settings, 'is_active' => true]);
    }

    private function ticket(?User $owner = null): Ticket
    {
        $owner ??= User::factory()->create(['role' => 'user']);

        return Ticket::create(['ticket_number' => 'TEST-'.Str::random(8), 'user_id' => $owner->id,
            'category_id' => Category::firstOrCreate(['name' => 'Support'])->id, 'title' => 'Test issue', 'description' => 'Details', 'status' => 'open', 'priority' => 'medium']);
    }

    public function test_portal_emails_requester_when_ticket_is_opened_replied_to_and_closed(): void
    {
        config(['mail.default' => 'array', 'helpdesk.autorespond' => true, 'helpdesk.email_url' => 'https://support.example.test/portal/']);
        $requester = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $department = Department::create(['name' => 'IT', 'is_active' => true]);
        $category = Category::create(['name' => 'Support']);
        $category->departments()->attach($department);

        $this->actingAs($requester)->post(route('helpdesk.store'), [
            'department_id' => $department->id,
            'category_id' => $category->id,
            'title' => 'Cannot sign in',
            'description' => 'The application shows an error.',
            'priority' => 'medium',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $ticket = Ticket::sole();

        $this->actingAs($admin)->post(route('helpdesk.reply', $ticket), [
            'body' => 'Please retry signing in.',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $closure = ['action' => 'status', 'status' => 'closed'];
        $this->patch(route('helpdesk.update', $ticket), $closure)->assertSessionHasNoErrors()->assertRedirect();
        $this->patch(route('helpdesk.update', $ticket), $closure)->assertSessionHasNoErrors()->assertRedirect();

        $this->artisan('helpdesk:send-mail')->assertSuccessful();
        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(3, $messages);
        foreach (['Ticket received', 'New support reply', 'Ticket closed'] as $index => $label) {
            $email = $messages[$index]->getOriginalMessage();
            $this->assertSame($requester->email, $email->getTo()[0]->getAddress());
            $this->assertSame("[{$ticket->ticket_number}] {$label}: Cannot sign in", $email->getSubject());
            $url = 'https://support.example.test/portal/helpdesk/tickets/'.$ticket->id;
            $this->assertStringContainsString($url, $email->getTextBody());
            $this->assertStringContainsString($url, $email->getHtmlBody());
        }
    }

    public function test_status_assignment_and_public_reply_email_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $agent = User::factory()->create(['role' => 'agent']);
        $ticket = $this->ticket();
        $this->actingAs($admin);
        // Exercise the shared workflow directly, used by both dashboard and API.
        $before = HelpdeskMessage::count();
        app(TicketWorkflow::class)->status($ticket, $admin, 'in_progress');
        $this->assertSame($before + 1, HelpdeskMessage::count());
        $this->assertDatabaseHas('helpdesk_messages', ['recipient' => $ticket->user->email, 'ticket_id' => $ticket->id, 'status' => 'pending']);
        app(TicketWorkflow::class)->status($ticket, $admin, 'in_progress');
        $this->assertSame($before + 1, HelpdeskMessage::count());
        $ticket->update(['agent_id' => $agent->id]);
        app(HelpdeskMail::class)->assigned($ticket);
        $this->assertDatabaseHas('helpdesk_messages', ['recipient' => $agent->email, 'ticket_id' => $ticket->id]);
        $message = HelpdeskMessage::latest('id')->first();
        $this->artisan('helpdesk:send-mail', ['--message' => $message->id])->assertSuccessful();
        $this->assertSame('logged', $message->fresh()->status);
        $this->assertSame('pending', HelpdeskMessage::oldest('id')->first()->status);
        $before = HelpdeskMessage::count();
        $this->post(route('helpdesk.reply', $ticket), ['body' => 'Private staff note', 'is_internal' => true])->assertRedirect();
        $this->assertSame($before, HelpdeskMessage::count());
        $this->post(route('helpdesk.reply', $ticket), ['body' => 'Public support reply'])->assertRedirect();
        $this->assertSame($before + 1, HelpdeskMessage::count());
        $this->assertStringContainsString('Public support reply', HelpdeskMessage::latest('id')->first()->body);
    }

    public function test_dashboard_settings_are_admin_only_and_applied(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        $agent = User::factory()->create(['role'=>'agent']);
        $data=['name'=>'Company Helpdesk','support_email'=>'support@example.com','display_timezone'=>'Asia/Manila','page_size'=>10,'show_stats'=>0,'default_priority'=>'high'];
        $this->actingAs($agent)->get('/helpdesk/settings')->assertForbidden();
        $this->post('/helpdesk/settings',$data)->assertForbidden();
        $this->actingAs($admin)->get('/helpdesk/settings')->assertOk();
        $this->post('/helpdesk/settings',$data)->assertRedirect(route('helpdesk.settings'));
        $this->get('/helpdesk')->assertOk()->assertSee('Company Helpdesk')->assertDontSee('<div class="stats">',false);
        $this->get('/helpdesk/new')->assertOk()->assertSee('value="high" selected',false);
        $this->post('/helpdesk/settings',array_replace($data,['page_size'=>500]))->assertSessionHasErrors('page_size');
    }
    public function test_ticket_number_series_skips_existing_numbers_and_preserves_counter(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $data=array_replace(\App\Http\Controllers\DashboardSettingsController::settings(),['number_mode'=>'sequential','number_prefix'=>'IT','number_date'=>'none','number_padding'=>4,'next_number'=>10]);
        $this->actingAs($admin)->post('/helpdesk/settings',$data)->assertRedirect();
        $ticket=$this->ticket();$ticket->update(['ticket_number'=>'IT-0010']);
        $series=app(\App\Services\TicketNumberSeries::class);
        $this->assertSame('IT-0011',$series->next());
        unset($data['next_number']);
        $this->post('/helpdesk/settings',$data)->assertRedirect();
        $this->assertSame('IT-0012',$series->next());
        $this->assertSame('IT-0010',$ticket->fresh()->ticket_number);
        $this->post('/helpdesk/settings',array_replace($data,['number_prefix'=>'<script>']))->assertSessionHasErrors('number_prefix');
    }
    public function test_administrator_can_open_every_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = $this->ticket();
        $this->actingAs($admin);
        foreach (['/helpdesk', '/helpdesk/new', '/helpdesk/tasks', '/helpdesk/directory', '/helpdesk/reports', '/helpdesk/audit', '/helpdesk/email', '/helpdesk/api-keys', '/helpdesk/tickets/'.$ticket->id] as $path) {
            $this->get($path)->assertOk();
        }
        foreach (array_keys(HelpdeskCatalog::modules()) as $kind) {
            $this->get('/helpdesk/manage/'.$kind)->assertOk();
        }
        $this->get('/')->assertRedirect(route('helpdesk.index'));
    }

    public function test_help_topic_routes_ticket_and_required_custom_fields_are_enforced(): void
    {
        $category = Category::create(['name' => 'Support']);
        $sla = $this->record('sla', ['hours' => 4]);
        $dept = $this->record('departments', ['sla_id' => $sla->id]);
        $topic = $this->record('topics', ['department_id' => $dept->id, 'priority' => 'high']);
        $this->record('fields', ['key' => 'asset_tag', 'type' => 'text', 'required' => true, 'topic_id' => $topic->id]);
        $data = ['title' => 'Broken laptop', 'description' => 'Will not start', 'category_id' => $category->id, 'priority' => 'medium',
            'requester_name' => 'Guest', 'requester_email' => 'guest@example.com', 'help_topic_id' => $topic->id];
        $this->postJson('/api/tickets', $data)->assertUnprocessable()->assertJsonValidationErrors('custom_fields.asset_tag');
        $this->assertDatabaseCount('tickets', 0);
        $response = $this->postJson('/api/tickets', array_merge($data, ['custom_fields' => ['asset_tag' => 'LT-42', 'unknown' => 'discard']]))->assertOk();
        $ticket = Ticket::findOrFail($response->json('id'));
        $this->assertSame($dept->id, $ticket->department_id);
        $this->assertSame('high', $ticket->priority);
        $this->assertSame(['asset_tag' => 'LT-42'], $ticket->custom_fields);
        $this->assertTrue($ticket->due_at->between(now()->addHours(3), now()->addHours(5)));
        $this->assertDatabaseHas('helpdesk_messages', ['ticket_id' => $ticket->id, 'status' => 'pending']);
    }

    public function test_filters_can_reject_or_route_requests(): void
    {
        $category = Category::create(['name' => 'Support']);
        $dept = $this->record('departments');
        $this->record('filters', ['field' => 'title', 'match' => 'contains', 'value' => 'urgent', 'priority' => 'urgent', 'department_id' => $dept->id, 'stop' => true]);
        $data = ['title' => 'Urgent network problem', 'description' => 'Offline', 'category_id' => $category->id, 'priority' => 'low', 'requester_email' => 'user@example.com'];
        $this->postJson('/api/tickets', $data)->assertOk()->assertJsonPath('priority', 'urgent')->assertJsonPath('department_id', $dept->id);
        $this->record('filters', ['field' => 'email', 'match' => 'equals', 'value' => 'spam@example.com', 'reject' => true]);
        $this->postJson('/api/tickets', array_merge($data, ['title' => 'Other', 'requester_email' => 'spam@example.com']))->assertUnprocessable();
        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_internal_notes_and_files_are_private_and_clients_are_scoped(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = $this->ticket($owner);
        $this->actingAs($admin)->post('/helpdesk/tickets/'.$ticket->id.'/reply', ['body' => 'Private diagnosis', 'is_internal' => 1])->assertRedirect();
        $this->post('/helpdesk/tickets/'.$ticket->id.'/attachment', ['file' => UploadedFile::fake()->createWithContent('notes.txt', 'Private diagnosis'), 'is_internal' => 1])->assertRedirect();
        $attachment = $ticket->attachments()->firstOrFail();
        $this->actingAs($owner)->get('/helpdesk/tickets/'.$ticket->id)->assertOk()->assertDontSee('Private diagnosis')->assertDontSee('notes.txt');
        $this->getJson('/api/tickets')->assertOk()->assertJsonPath('0.comments', []);
        $this->get('/helpdesk/attachments/'.$attachment->id)->assertForbidden();
        $this->post('/helpdesk/tickets/'.$ticket->id.'/reply', ['body' => 'Forged note', 'is_internal' => 1])->assertForbidden();
        $this->actingAs($other)->get('/helpdesk/tickets/'.$ticket->id)->assertForbidden();
        $this->actingAs($admin)->get('/helpdesk/attachments/'.$attachment->id)->assertOk();
    }

    public function test_ticket_photo_preview_preserves_attachment_access(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = $this->ticket();
        $ticket->update(['user_id' => $owner->id]);
        $photo = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aZwAAAABJRU5ErkJggg==');
        $this->actingAs($admin)->post(route('helpdesk.attachment', $ticket), [
            'file' => UploadedFile::fake()->createWithContent('photo.png', $photo),
        ])->assertSessionHasNoErrors();
        $attachment = $ticket->attachments()->sole();
        $preview = route('helpdesk.download', ['attachment' => $attachment, 'preview' => 1]);

        $this->actingAs($owner)->get(route('helpdesk.ticket', $ticket))->assertOk()
            ->assertSee('src="'.$preview.'"', false);
        $response = $this->get($preview)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $this->assertSame($photo, $response->streamedContent());
        $this->get(route('helpdesk.download', $attachment))->assertDownload('photo.png');
        $this->actingAs($other)->get($preview)->assertForbidden();

        $attachment->update(['is_internal' => true]);
        $this->actingAs($owner)->get($preview)->assertForbidden();
        $this->get(route('helpdesk.ticket', $ticket))->assertOk()->assertDontSee($preview, false);
    }

    public function test_locks_prevent_conflicting_staff_updates_and_tasks_prevent_closure(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $developer = User::factory()->create(['role' => 'agent']);
        $ticket = $this->ticket();
        $this->actingAs($admin)->patch('/helpdesk/tickets/'.$ticket->id, ['action' => 'lock'])->assertRedirect();
        $this->actingAs($developer)->postJson('/api/tickets/'.$ticket->id.'/comments', ['body' => 'Conflicting reply'])->assertUnprocessable();
        $this->actingAs($admin)->patch('/helpdesk/tickets/'.$ticket->id, ['action' => 'unlock'])->assertRedirect();
        $task = HelpdeskTask::create(['title' => 'Investigate', 'ticket_id' => $ticket->id, 'created_by' => $admin->id, 'status' => 'open']);
        $this->patchJson('/api/tickets/'.$ticket->id.'/status', ['status' => 'closed'])->assertUnprocessable();
        $this->patch('/helpdesk/tasks/'.$task->id, ['status' => 'closed'])->assertRedirect();
        $this->patchJson('/api/tickets/'.$ticket->id.'/status', ['status' => 'closed'])->assertOk();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'closed']);
    }

    public function test_bulk_updates_are_atomic_and_csv_export_escapes_formulas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $a = $this->ticket();
        $b = $this->ticket();
        $a->update(['title' => '=DANGEROUS()']);
        HelpdeskTask::create(['title' => 'Blocking work', 'ticket_id' => $b->id, 'created_by' => $admin->id, 'status' => 'open']);
        $this->actingAs($admin)->postJson('/helpdesk/tickets/bulk', ['ids' => [$a->id, $b->id], 'status' => 'closed'])->assertUnprocessable();
        $this->assertSame('open', $a->fresh()->status);
        $response = $this->get('/helpdesk/tickets/export');
        $response->assertOk();
        $this->assertStringContainsString("'=DANGEROUS()", $response->streamedContent());
    }

    public function test_knowledge_base_only_publishes_active_articles(): void
    {
        $public = $this->record('articles', ['body' => 'Public instructions'], 'Public guide');
        $draft = $this->record('articles', ['body' => 'Secret draft'], 'Unpublished');
        $draft->update(['is_active' => false]);
        $this->get('/knowledge')->assertOk()->assertSee('Public guide')->assertDontSee('Unpublished');
        $this->get('/knowledge/'.$public->id)->assertOk()->assertSee('Public instructions');
        $this->get('/knowledge/'.$draft->id)->assertNotFound();
    }

    public function test_inbound_email_requires_signature_deduplicates_and_checks_reply_ownership(): void
    {
        Category::create(['name' => 'Support']);
        config(['helpdesk.inbound_secret' => 'a-long-testing-secret-32-characters']);
        $data = ['message_id' => 'email-001', 'from' => 'guest@example.com', 'subject' => 'Email request', 'body' => 'Please help'];
        $this->postJson('/api/mail/inbound', $data)->assertUnauthorized();
        $send = function (array $data) {
            $json = json_encode($data);
            $timestamp = (string) time();

            return $this->call('POST', '/api/mail/inbound', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_HELPDESK_TIMESTAMP' => $timestamp, 'HTTP_X_HELPDESK_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$json, config('helpdesk.inbound_secret'))], $json);
        };
        $response = $send($data)->assertCreated();
        $number = $response->json('ticket_number');
        $send($data)->assertOk()->assertJsonPath('duplicate', true);
        $this->assertDatabaseCount('tickets', 1);
        $send(array_merge($data, ['message_id' => 'email-002', 'from' => 'attacker@example.com', 'ticket_number' => $number]))->assertForbidden();
        $send(array_merge($data, ['message_id' => 'email-003', 'ticket_number' => $number, 'body' => 'Following up']))->assertCreated();
        $this->assertDatabaseHas('comments', ['body' => 'Following up', 'is_internal' => false]);
    }

    public function test_mail_worker_uses_array_transport_in_tests(): void
    {
        $ticket = $this->ticket();
        app(HelpdeskMail::class)->opened($ticket);
        config(['mail.default' => 'array']);
        $this->artisan('helpdesk:send-mail')->assertSuccessful();
        $this->assertNull(HelpdeskMessage::where('ticket_id', $ticket->id)->first()->error);
        $this->assertDatabaseHas('helpdesk_messages', ['ticket_id' => $ticket->id, 'status' => 'logged', 'attempts' => 1]);
        $sent = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $this->assertStringContainsString('PASCAL RESOURCES ENERGY INC.', $sent->getHtmlBody());
        $this->assertStringContainsString('background:#f0f7ff', $sent->getHtmlBody());
        $this->assertStringContainsString(route('helpdesk.ticket', $ticket), $sent->getHtmlBody());
        $this->assertStringContainsString('Your support request has been received.', $sent->getTextBody());
        $notification = HelpdeskMessage::where('ticket_id', $ticket->id)->firstOrFail();
        $notification->body = '<script>alert("test")</script>';
        $html = view('mail.notification', compact('notification'))->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_department_and_team_scopes_and_profiles_are_enforced(): void
    {
        $deptA = $this->record('departments', [], 'A');
        $deptB = $this->record('departments', [], 'B');
        $profile = $this->record('roles', ['permissions' => ['tickets.view']]);
        $agent = User::factory()->create(['role' => 'agent', 'ticket_scope' => 'departments', 'department_ids' => [$deptA->id], 'access_profile_id' => $profile->id, 'permissions' => null]);
        $a = $this->ticket();
        $a->update(['department_id' => $deptA->id]);
        $b = $this->ticket();
        $b->update(['department_id' => $deptB->id]);
        $this->actingAs($agent)->get('/helpdesk/tickets/'.$a->id)->assertOk();
        $this->get('/helpdesk/tickets/'.$b->id)->assertForbidden();
        $this->getJson('/api/tickets')->assertOk()->assertJsonCount(1);
        $team = $this->record('teams', ['members' => [$agent->id]]);
        $agent->update(['ticket_scope' => 'assigned']);
        $b->update(['team_id' => $team->id]);
        $this->getJson('/api/tickets')->assertOk()->assertJsonPath('0.id', $b->id)->assertJsonCount(1);
        $profile->update(['settings' => ['permissions' => []]]);
        $this->getJson('/api/tickets')->assertForbidden();
    }

    public function test_administrator_assigned_to_hr_can_only_access_hr_tickets(): void
    {
        $hr = $this->record('departments', [], 'HR');
        $it = $this->record('departments', [], 'IT');
        $admin = User::factory()->create(['role' => 'admin', 'department_ids' => [$hr->id]]);
        $hrTicket = $this->ticket();
        $hrTicket->update(['department_id' => $hr->id, 'title' => 'HR request']);
        $itTicket = $this->ticket();
        $itTicket->update(['department_id' => $it->id, 'title' => 'IT request']);

        $this->actingAs($admin)->get(route('helpdesk.index'))
            ->assertOk()->assertSee('HR Dashboard')->assertSee('HR request')->assertDontSee('IT request');
        $this->get(route('helpdesk.index', ['department_id' => $it->id]))->assertNotFound();
        $this->get(route('helpdesk.ticket', $itTicket))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->getJson('/api/tickets')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $hrTicket->id);
    }

    public function test_business_schedule_skips_weekends_and_holidays(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 16:00:00', 'Asia/Shanghai')->utc());
        $schedule = $this->record('schedules', ['timezone' => 'Asia/Shanghai', 'days' => [1, 2, 3, 4, 5], 'start' => '09:00', 'end' => '17:00', 'holidays' => '2026-10-12']);
        $sla = $this->record('sla', ['hours' => 8, 'schedule_id' => $schedule->id]);
        $due = app(BusinessClock::class)->due($sla);
        $this->assertSame('2026-10-13 16:00', $due->setTimezone('Asia/Shanghai')->format('Y-m-d H:i'));
        $this->travelBack();
    }

    public function test_custom_forms_and_lists_validate_only_the_selected_topic(): void
    {
        $category = Category::create(['name' => 'Support']);
        $form = $this->record('forms');
        $list = $this->record('lists', ['options' => "Laptop\nDesktop"]);
        $topic = $this->record('topics', ['form_ids' => [$form->id]]);
        $this->record('fields', ['key' => 'device', 'type' => 'select', 'required' => true, 'form_id' => $form->id, 'list_id' => $list->id]);
        $data = ['requester_email' => 'guest@example.com', 'title' => 'Issue', 'description' => 'Details', 'category_id' => $category->id, 'priority' => 'medium'];
        $this->postJson('/api/tickets', $data)->assertOk();
        $data['help_topic_id'] = $topic->id;
        $data['custom_fields'] = ['device' => 'Unknown'];
        $this->postJson('/api/tickets', $data)->assertUnprocessable()->assertJsonValidationErrors('custom_fields.device');
        $data['custom_fields'] = ['device' => 'Laptop'];
        $this->postJson('/api/tickets', $data)->assertOk()->assertJsonPath('custom_fields.device', 'Laptop');
        $this->get('/support/new')->assertOk()->assertSee('Laptop');
    }

    public function test_configuration_saves_and_secrets_are_encrypted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->post('/helpdesk/manage/roles', ['name' => 'Read only', 'is_active' => 1, 'settings' => ['permissions' => ['tickets.view']]])->assertRedirect();
        $profile = HelpdeskRecord::ofKind('roles')->firstOrFail();
        $this->get('/helpdesk/manage/roles?edit='.$profile->id)->assertOk();
        $this->put('/helpdesk/manage/roles/'.$profile->id, ['name' => 'Reader', 'is_active' => 1, 'settings' => ['permissions' => []]])->assertRedirect();
        $this->post('/helpdesk/email', ['mode' => 'log', 'from_address' => 'support@example.com', 'from_name' => 'Support', 'password' => 'secret-value', 'inbound_secret' => str_repeat('x', 40)])->assertRedirect();
        $settings = HelpdeskRecord::ofKind('mail_settings')->firstOrFail()->settings;
        $this->assertNotSame('secret-value', $settings['password']);
        $this->assertSame('secret-value', Crypt::decryptString($settings['password']));
        $this->get('/helpdesk/email')->assertOk()->assertDontSee('secret-value')->assertDontSee(str_repeat('x', 40));
    }

    public function test_customer_registration_and_password_reset_are_functional(): void
    {
        $this->post('/register', ['name' => 'Customer', 'email' => 'customer@example.com', 'role' => 'admin',
            'password' => 'long-account-password', 'password_confirmation' => 'long-account-password'])->assertRedirect(route('login'));
        $user = User::where('email', 'customer@example.com')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        $mail = HelpdeskMessage::where('recipient', $user->email)->firstOrFail();
        preg_match('~/reset-password/([^?\s]+)~', $mail->body, $match);
        $token = $match[1];
        $this->post('/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'new-account-password', 'password_confirmation' => 'new-account-password'])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-account-password', $user->fresh()->password));
        $this->post('/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'other-account-password', 'password_confirmation' => 'other-account-password'])->assertSessionHasErrors('email');
    }

    public function test_api_keys_are_scoped_hashed_and_revocable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $agent = User::factory()->create(['role' => 'agent', 'permissions' => ['tickets.view']]);
        $this->ticket();
        $this->actingAs($admin)->post('/helpdesk/api-keys', ['name' => 'Read integration', 'user_id' => $agent->id, 'scopes' => ['read']])->assertRedirect();
        $token = session('api_token');
        $this->assertNotEmpty($token);
        $key = HelpdeskApiKey::firstOrFail();
        $this->assertSame(hash('sha256', $token), $key->getRawOriginal('key_hash'));
        $this->assertArrayNotHasKey('key_hash', $key->toArray());
        $this->getJson('/integration/v1/tickets')->assertUnauthorized();
        $this->withToken($token)->getJson('/integration/v1/tickets')->assertOk()->assertJsonCount(1);
        $this->withToken($token)->postJson('/integration/v1/tickets', [])->assertForbidden();
        $key->update(['is_active' => false]);
        $this->withToken($token)->getJson('/integration/v1/tickets')->assertUnauthorized();
    }
}





