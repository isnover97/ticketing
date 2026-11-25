<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use App\Models\HelpdeskRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class HelpdeskDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $make = fn ($kind, $name, $settings = [], $description = '') => HelpdeskRecord::firstOrCreate(['kind' => $kind, 'name' => $name], ['settings' => $settings, 'description' => $description, 'is_active' => true]);
        $sla = $make('sla', 'Standard — 24 hours', ['hours' => 24], 'Resolution target measured in elapsed hours.');
        $make('sla', 'Priority — 4 hours', ['hours' => 4]);
        $team = $make('teams', 'IT Support', ['members' => User::whereIn('role', ['agent', 'admin'])->where('is_active', true)->pluck('id')->all()]);
        Department::firstOrCreate(['name' => 'IT Services'], ['settings' => ['sla_id' => $sla->id, 'team_id' => $team->id]]);
        \App\Support\TicketTopics::install();
        $make('queues', 'Assigned to me', ['assigned' => 'mine']);
        $make('queues', 'Unassigned open tickets', ['assigned' => 'unassigned', 'status' => 'open']);
        $make('queues', 'Overdue tickets', ['overdue' => true]);
        $make('queues', 'Urgent tickets', ['priority' => 'urgent', 'status' => 'open']);
        $make('canned', 'Request more information', ['body' => 'Thank you for contacting support. Please provide the device name, the exact error message, and when the issue started so we can investigate.']);
        $make('canned', 'Resolution confirmation', ['body' => 'We have completed the requested work. Please confirm whether the issue is resolved or reply with any remaining concerns.']);
        $make('articles','How to submit a useful support request',['category' => 'Getting started', 'body' => "Choose the help topic that best matches your issue. Include a clear subject, the steps that caused the problem, and any error messages.\n\nSign in to track your tickets and reply to your support team. You can attach screenshots and documents from the ticket conversation. Do not upload passwords or authentication codes."],'A short guide to opening and tracking a support request.');
    }
}

