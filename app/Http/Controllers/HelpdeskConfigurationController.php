<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\HelpdeskRecord;
use App\Models\User;
use App\Services\TicketWorkflow;
use App\Support\HelpdeskCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HelpdeskConfigurationController extends Controller
{
    private function module(Request $request, string $kind): array
    {
        $module = HelpdeskCatalog::modules()[$kind] ?? null;
        abort_unless($module, 404);
        abort_unless($request->user()->canAccess($module[1]), 403);

        return $module;
    }

    private function records(string $kind)
    {
        return match ($kind) {
            'departments' => Department::query(),
            'categories' => Category::with('departments'),
            default => HelpdeskRecord::ofKind($kind),
        };
    }

    private function activeRecordRule(string $kind): \Illuminate\Validation\Rules\Exists
    {
        $rule = $kind === 'departments'
            ? Rule::exists('departments', 'id')
            : Rule::exists('helpdesk_records', 'id')->where('kind', $kind);

        return $rule->where('is_active', true);
    }

    public function index(Request $request, string $kind)
    {
        $module = $this->module($request, $kind);
        $editing = $request->filled('edit') ? $this->records($kind)->findOrFail($request->integer('edit')) : null;
        return view('helpdesk.manage', [
            'kind' => $kind, 'module' => $module, 'editing' => $editing,
            'records' => $this->records($kind)->orderBy('name')->paginate(20)->withQueryString(),
            'options' => HelpdeskRecord::active()->orderBy('name')->get()->groupBy('kind')->put('departments', Department::active()->orderBy('name')->get()),
            'agents' => User::whereIn('role', ['agent', 'admin'])->where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function save(Request $request, string $kind, ?string $record = null)
    {
        $module = $this->module($request, $kind);
        $record = $record === null ? null : $this->records($kind)->findOrFail($record);
        $rules = ['name' => 'required|string|max:255', 'description' => 'nullable|string|max:20000', 'is_active' => 'required|boolean'];
        if ($kind === 'categories') {
            $unique = Rule::unique('categories', 'name');
            $rules['name'] = ['required', 'string', 'max:255', $record ? $unique->ignore($record) : $unique];
        }
        foreach ($module[2] as $key => $field) {
            $path = 'settings.'.$key;
            $type = $field[1];
            $rules[$path] = match ($type) {
                'record' => ['nullable', $this->activeRecordRule($field[2])],
                'agent' => ['nullable', Rule::exists('users', 'id')->whereIn('role', ['admin', 'agent'])->where('is_active', true)],
                'category' => 'nullable|exists:categories,id',
                'members' => 'nullable|array',
                'record_many' => 'nullable|array',
                'permissions' => 'nullable|array',
                'weekdays' => 'required|array|min:1',
                'time' => 'required|date_format:H:i',
                'number' => 'required|numeric|min:0.25|max:87600',
                'checkbox' => 'nullable|boolean',
                'select' => ['nullable', Rule::in($field[2])],
                default => 'nullable|string|max:20000',
            };
            if ($type === 'members') {
                $rules[$path.'.*'] = ['integer', Rule::exists('users', 'id')->whereIn('role', ['admin', 'agent'])->where('is_active', true)];
            }
            if ($type === 'record_many') {
                $rules[$path.'.*'] = ['integer', $this->activeRecordRule($field[2])];
            }
            if ($type === 'permissions') {
                $rules[$path.'.*'] = Rule::in(array_keys(User::PERMISSIONS));
            }
            if ($type === 'weekdays') {
                $rules[$path.'.*'] = 'integer|between:1,7';
            }
        }
        if ($kind === 'fields') {
            $rules['settings.key'] = ['required', 'string', 'max:64', 'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/'];
            $rules['settings.type'] = ['required', Rule::in(['text', 'textarea', 'select', 'date', 'number'])];
        }
        if ($kind === 'filters') {
            $rules['settings.field'] = 'required|in:title,email,priority,category_id';
            $rules['settings.match'] = 'required|in:contains,equals';
            $rules['settings.value'] = 'required|string|max:255';
        }
        if (in_array($kind, ['articles', 'canned'])) {
            $rules['settings.body'] = 'required|string|max:50000';
        }
        if ($kind === 'mail_templates') {
            $rules['settings.event'] = 'required|in:opened,reply,overdue';
            $rules['settings.subject'] = 'required|string|max:200';
            $rules['settings.body'] = 'required|string|max:50000';
        }
        if ($kind === 'organizations') {
            $rules['settings.domain'] = ['nullable', 'string', 'max:253', 'regex:/^(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/'];
        }
        if ($kind === 'schedules') {
            $rules['settings.timezone'] = 'required|timezone';
            $rules['settings.end'] = 'required|date_format:H:i|after:settings.start';
        }
        if ($kind === 'lists') {
            $rules['settings.options'] = 'required|string|max:20000';
        }
        $data = $request->validate($rules);
        $settings = $data['settings'] ?? [];
        // Exclude unknown keys and normalize unchecked boxes and empty selections.
        $settings = array_intersect_key($settings, $module[2]);
        foreach ($module[2] as $key => $field) {
            if ($field[1] === 'checkbox') {
                $settings[$key] = $request->boolean('settings.'.$key);
            } elseif (in_array($field[1], ['members', 'record_many', 'weekdays'])) {
                $settings[$key] = array_map('intval', $settings[$key] ?? []);
            } elseif ($field[1] === 'permissions') {
                $settings[$key] = $settings[$key] ?? [];
            } elseif (($settings[$key] ?? null) === null || ($settings[$key] ?? null) === '') {
                unset($settings[$key]);
            }
        }
        if ($kind === 'fields') {
            if (HelpdeskRecord::ofKind('fields')->where('settings->key', $settings['key'])->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists()) {
                throw ValidationException::withMessages(['settings.key' => 'This field key already exists.']);
            }
            if ($settings['type'] === 'select' && empty(trim($settings['options'] ?? '')) && empty($settings['list_id'])) {
                throw ValidationException::withMessages(['settings.options' => 'Enter select options or choose a custom list.']);
            }
        }
        if ($kind === 'schedules') {
            foreach (preg_split('/\R/', trim($settings['holidays'] ?? '')) as $date) {
                if ($date !== '' && ! validator(['date' => $date], ['date' => 'date_format:Y-m-d'])->passes()) {
                    throw ValidationException::withMessages(['settings.holidays' => 'Holiday dates must use YYYY-MM-DD.']);
                }
            }
        }
        if ($kind !== 'categories') {
            $data['settings'] = $settings;
        } else {
            unset($data['settings']);
        }
        if (! in_array($kind, ['departments', 'categories'], true)) {
            $data['kind'] = $kind;
        }
        DB::transaction(function () use ($record, $kind, $data, $settings, $request, $module) {
            if ($record) {
                $record->update($data);
            } else {
                $record = $this->records($kind)->create($data);
            }
            if ($record instanceof Category) {
                $record->departments()->sync($settings['department_ids']);
            }
            app(TicketWorkflow::class)->event(null, $request->user(), 'configuration', ($record->wasRecentlyCreated ? 'Created ' : 'Updated ').$module[0].': '.$record->name);
        });

        return redirect()->route('helpdesk.manage',$kind)->with('status','Saved successfully.');
    }
}
