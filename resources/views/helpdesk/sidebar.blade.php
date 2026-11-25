@php
    $user = auth()->user();
    $icons = ['Tickets' => '▤', 'Operations' => '▦', 'Administration' => '⚙', 'Support' => '?'];
    $groups = [
        'Tickets' => [
            ['Tickets & Dashboard', 'helpdesk.index', $user?->canAccess('tickets.view'), ['helpdesk.index', 'helpdesk.ticket']],
            ['Open a ticket', 'helpdesk.create', $user?->canAccess('tickets.create'), ['helpdesk.create']],
        ],
        'Operations' => [
            ['Tasks', 'helpdesk.tasks', $user?->canAccess('tasks.manage'), ['helpdesk.tasks*']],
            ['Users & Organizations', 'helpdesk.directory', $user?->canAccess('directory.view'), ['helpdesk.directory']],
            ['Reports', 'helpdesk.reports', $user?->canAccess('analytics.view'), ['helpdesk.reports']],
        ],
        'Administration' => [
            ['Settings', 'helpdesk.settings', $user?->canManageSystem(), ['helpdesk.settings']],
            ['User Management & Access', 'admin.users.index', $user?->canManageSystem(), ['admin.users.*']],
            ['Audit History', 'helpdesk.audit', $user?->canManageSystem(), ['helpdesk.audit']],
            ['Email & Delivery', 'helpdesk.email', $user?->canManageSystem(), ['helpdesk.email']],
            ['API Keys', 'helpdesk.api-keys', $user?->canManageSystem(), ['helpdesk.api-keys']],
        ],
        'Support' => [
            ['Self-service Knowledge Base', 'knowledge.index', true, ['knowledge.*']],
            ['Support home', 'support.create', true, ['support.create'], [], url('/')],
        ],
    ];

    foreach (\App\Support\HelpdeskCatalog::modules() as $kind => $module) {
        $groups['Administration'][] = [
            $module[0], 'helpdesk.manage', $user?->canAccess($module[1]),
            request()->route('kind') === $kind ? ['helpdesk.manage'] : [], [$kind],
        ];
    }
@endphp

<aside class="sidebar workspace-sidebar" id="workspace-sidebar">
    <nav aria-label="Workspace">
        @foreach ($groups as $label => $items)
            @php
                $items = collect($items)->filter(fn ($item) => $item[2]);
                $currentGroup = $items->contains(fn ($item) => request()->routeIs(...$item[3]));
            @endphp
            @if ($items->isNotEmpty())
                <details class="sidebar-group" @if ($currentGroup) data-current @endif @if ($currentGroup || $label === 'Tickets') open @endif>
                    <summary title="{{ $label }}">
                        <span class="sidebar-group-icon" aria-hidden="true">{{ $icons[$label] }}</span>
                        <span class="sidebar-group-label">{{ $label }}</span>
                    </summary>
                    <ul>
                        @foreach ($items as $item)
                            @php
                                $active = request()->routeIs(...$item[3]);
                                $href = $item[5] ?? route($item[1], $item[4] ?? []);
                            @endphp
                            <li><a href="{{ $href }}" @if ($active) aria-current="location" @endif>{{ $item[0] }}</a></li>
                        @endforeach
                    </ul>
                </details>
            @endif
        @endforeach
    </nav>
    <div class="sidebar-foot">
        {{ ['user' => 'Client', 'agent' => 'System Developer', 'admin' => 'Administrator'][$user?->role] ?? 'Customer Support' }}
        <br><span>PREI support workspace</span>
    </div>
</aside>
