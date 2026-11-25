<?php

namespace App\Support;

class HelpdeskCatalog
{
    public static function modules(): array
    {
        // Field definitions: label, input type, optional choices or record kind.
        $routing = [
            'department_id' => ['Department', 'record', 'departments'],
            'team_id' => ['Team', 'record', 'teams'],
            'sla_id' => ['SLA plan', 'record', 'sla'],
            'agent_id' => ['System developer', 'agent'],
        ];

        return [
            'roles' => ['Role Profiles', 'configuration.manage', ['permissions' => ['Granted module permissions', 'permissions']]],
            'lists' => ['Custom Lists', 'configuration.manage', ['options' => ['Options (one per line)', 'textarea']]],
            'forms' => ['Custom Forms', 'configuration.manage', []],
            'schedules' => ['Business Schedules', 'configuration.manage', [
                'timezone' => ['Timezone (e.g. Asia/Shanghai)', 'text'],
                'days' => ['Working days', 'weekdays'], 'start' => ['Opening time', 'time'], 'end' => ['Closing time', 'time'],
                'holidays' => ['Holiday dates: YYYY-MM-DD, one per line', 'textarea'],
            ]],
            'mail_templates' => ['Email Templates', 'configuration.manage', [
                'event' => ['Event', 'select', ['opened', 'reply', 'status', 'assignment', 'overdue']],
                'subject' => ['Email subject', 'text'], 'body' => ['Email text: {ticket_number}, {name}, {subject}, {message}, {url}', 'textarea'],
            ]],
            'departments' => ['Departments', 'configuration.manage', [
                'sla_id' => ['Default SLA', 'record', 'sla'], 'team_id' => ['Default team', 'record', 'teams'],
            ]],
            'teams' => ['Teams', 'configuration.manage', ['members' => ['Team members', 'members']]],
            'categories' => ['Category Masterlist', 'configuration.manage', [
                'department_ids' => ['Departments', 'record_many', 'departments'],
            ]],
            'sla' => ['SLA Plans', 'configuration.manage', ['hours' => ['Resolution target (hours)', 'number'], 'schedule_id' => ['Business schedule (blank = elapsed time)', 'record', 'schedules']]],
            'topics' => ['Help Topics', 'configuration.manage', array_merge([
                'category_id' => ['Ticket category', 'category'], 'priority' => ['Default priority', 'select', ['low', 'medium', 'high', 'urgent']],
                'form_ids' => ['Custom forms', 'record_many', 'forms'],
            ], $routing)],
            'filters' => ['Ticket Filters', 'configuration.manage', array_merge([
                'field' => ['Match field', 'select', ['title', 'email', 'priority', 'category_id']],
                'match' => ['Match method', 'select', ['contains', 'equals']], 'value' => ['Match value', 'text'],
                'priority' => ['Set priority', 'select', ['low', 'medium', 'high', 'urgent']],
                'reject' => ['Reject matching requests', 'checkbox'], 'stop' => ['Stop after matching this filter', 'checkbox'],
            ], $routing)],
            'fields' => ['Custom Fields', 'configuration.manage', [
                'key' => ['Field key (letters, numbers, underscores)', 'text'],
                'type' => ['Field type', 'select', ['text', 'textarea', 'select', 'date', 'number']],
                'options' => ['Select options (one per line)', 'textarea'], 'required' => ['Required', 'checkbox'],
                'topic_id' => ['Only for help topic (blank = all)', 'record', 'topics'],
                'form_id' => ['Custom form (optional)', 'record', 'forms'],
                'list_id' => ['Select options from a custom list', 'record', 'lists'],
            ]],
            'queues' => ['Saved Queues', 'configuration.manage', [
                'status' => ['Status', 'select', ['open', 'in_progress', 'resolved', 'closed']],
                'priority' => ['Priority', 'select', ['low', 'medium', 'high', 'urgent']],
                'department_id' => ['Department', 'record', 'departments'],
                'assigned' => ['Assignment', 'select', ['mine', 'unassigned']], 'overdue' => ['Overdue only', 'checkbox'],
            ]],
            'articles' => ['Knowledge Base', 'knowledge.manage', [
                'category' => ['Article category', 'text'], 'body' => ['Article text', 'textarea'],
            ]],
            'canned' => ['Canned Responses', 'knowledge.manage', [
                'body' => ['Response text', 'textarea'],
                'topic_id' => ['Help topic (optional)', 'record', 'topics'],
                'category_id' => ['Category (optional)', 'category'],
            ]],
            'organizations' => ['Organizations', 'configuration.manage', [
                'domain' => ['Email domain (e.g. company.com)', 'text'],
            ]],
        ];
    }
}
