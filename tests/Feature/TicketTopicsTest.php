<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\HelpdeskRecord;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Support\TicketTopics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketTopicsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_ticket_can_include_an_attachment(): void
    {
        Storage::fake('local');
        $department = Department::create(['name' => 'Facilities', 'is_active' => true]);
        $category = Category::create(['name' => 'Building Requests']);
        $category->departments()->attach($department);
        $data = [
            'category_id' => $category->id,
            'department_id' => $department->id,
            'title' => 'Building request',
            'description' => 'Ticket details',
            'priority' => 'medium',
            'requester_email' => 'requester@example.com',
        ];

        $this->post(route('support.store'), $data + ['file' => UploadedFile::fake()->create('invalid.html', 1, 'text/html')])
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('tickets', 0);
        $this->post(route('support.store'), $data + ['file' => UploadedFile::fake()->createWithContent('details.txt', 'Additional details')])
            ->assertSessionHasNoErrors()->assertRedirect(route('support.create'));

        $attachment = TicketAttachment::sole();
        $this->assertSame('details.txt', $attachment->name);
        $this->assertFalse($attachment->is_internal);
        $this->assertSame($attachment->ticket->user_id, $attachment->user_id);
        Storage::disk('local')->assertExists($attachment->getRawOriginal('path'));
    }

    public function test_category_masterlist_assigns_a_category_without_requiring_a_topic(): void
    {
        $department = Department::create(['name' => 'Facilities', 'is_active' => true]);
        $otherDepartment = Department::create(['name' => 'Finance', 'is_active' => true]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('helpdesk.manage.store', 'categories'), [
                'name' => 'Building Requests',
                'is_active' => 1,
                'settings' => ['department_ids' => [$department->id]],
            ])->assertSessionHasNoErrors()->assertRedirect();
        $category = Category::where('name', 'Building Requests')->sole();
        $this->assertSame([$department->id], $category->departments->modelKeys());
        $this->get(route('helpdesk.manage', ['categories', 'edit' => $category->id]))->assertOk()->assertSee('Facilities');

        $data = [
            'category_id' => $category->id,
            'department_id' => $otherDepartment->id,
            'title' => 'Building request',
            'description' => 'Ticket details',
            'priority' => 'medium',
            'requester_email' => 'requester@example.com',
        ];
        $this->postJson(route('helpdesk.store'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $data['department_id'] = $department->id;
        $this->post(route('helpdesk.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('tickets', ['category_id' => $category->id, 'department_id' => $department->id]);
    }

    public function test_public_ticket_form_renders_the_category_filter_script(): void
    {
        TicketTopics::install();

        $this->get('/')->assertOk()
            ->assertSee('name="department_id" required', false)
            ->assertDontSee('Use automatic routing')
            ->assertSee("category.addEventListener('change',updateTopics);", false)
            ->assertDontSee("@stack('scripts')", false);
    }

    public function test_ticket_form_rejects_topics_outside_the_selected_category(): void
    {
        $department = Department::create(['name' => 'IT Services', 'is_active' => true]);
        TicketTopics::install();
        $category = Category::where('name', 'General Support')->sole();
        $topic = HelpdeskRecord::ofKind('topics')->where('name', 'Email Creation')->sole();
        $hardwareTopic = HelpdeskRecord::ofKind('topics')->where('name', 'Hardware Support')->sole();
        $hardwareTopic->update(['settings' => array_replace($hardwareTopic->settings, ['department_id' => $department->id])]);
        $category->departments()->syncWithoutDetaching([$department->id]);
        $data = [
            'category_id' => $category->id,
            'help_topic_id' => $topic->id,
            'title' => 'Support request',
            'description' => 'Ticket details',
            'priority' => 'medium',
            'requester_name' => 'Guest',
            'requester_email' => 'guest@example.com',
        ];

        $this->postJson(route('support.store'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('department_id');
        $data['department_id'] = $department->id;
        $this->postJson(route('support.store'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('help_topic_id');
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->postJson(route('helpdesk.store'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('help_topic_id');
        $this->assertDatabaseCount('tickets', 0);

        $data['help_topic_id'] = $hardwareTopic->id;
        $this->post(route('helpdesk.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'category_id' => $category->id,
            'help_topic_id' => $data['help_topic_id'],
        ]);
    }

    public function test_catalog_has_the_requested_category_topic_relationships_and_is_repeatable(): void
    {
        TicketTopics::install();
        TicketTopics::install();

        $this->assertSame(13, HelpdeskRecord::ofKind('topics')->active()->count());
        foreach (TicketTopics::GROUPS as $name => $topics) {
            $category = Category::where('name', $name)->firstOrFail();
            foreach ($topics as $topic) {
                $record = HelpdeskRecord::ofKind('topics')->active()->where('name', $topic)->sole();
                $this->assertSame($category->id, $record->settings['category_id']);
            }
        }
    }
}


