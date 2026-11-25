<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Department;
use App\Models\HelpdeskRecord;
use Illuminate\Support\Facades\Schema;

class TicketTopics
{
    public const GROUPS = [
        'Access and Accounts' => ['Email Creation', 'NAS Concern', 'Password Resets (NAS, Email, HRIS)'],
        'General Support' => ['Hardware Support', 'Preventive Maintenance', 'CCTV Review', 'Hardware Upgrade', 'Meeting Link Creation'],
        'Network and Internet' => ['Internet Connection Problem', 'Guest WIFI Voucher'],
        'Software Support' => ['HRIS (HERA) Concern', 'CRMS Concern', 'DMS Concern'],
    ];

    public static function install(bool $legacyDepartments = false): void
    {
        $names = array_merge(...array_values(self::GROUPS));
        HelpdeskRecord::ofKind('topics')->whereNotIn('name', $names)->update(['is_active' => false]);
        $departments = $legacyDepartments ? HelpdeskRecord::ofKind('departments') : Department::query();
        $department = $departments->where('name', 'IT Services')->first();
        foreach (self::GROUPS as $name => $topics) {
            $aliases = match ($name) {
                'Access and Accounts' => ['Access and Accounts', 'Access & Accounts'],
                'Network and Internet' => ['Network and Internet', 'Network & Internet'],
                default => [$name],
            };
            $category = Category::whereIn('name', $aliases)->first() ?? new Category(['description' => 'IT support requests.']);
            $category->name = $name;
            $category->save();
            $departmentIds = [];
            foreach ($topics as $topicName) {
                $topic = HelpdeskRecord::firstOrNew(['kind' => 'topics', 'name' => $topicName]);
                $topic->settings = array_merge(['priority' => 'medium', 'department_id' => $department?->id], $topic->settings ?? [], ['category_id' => $category->id]);
                $topic->is_active = true;
                $topic->save();
                if (! empty($topic->settings['department_id'])) {
                    $departmentIds[] = (int) $topic->settings['department_id'];
                }
            }
            if ($departmentIds && ! $legacyDepartments && Schema::hasTable('category_department')) {
                $category->departments()->syncWithoutDetaching(Department::whereKey($departmentIds)->pluck('id'));
            }
        }
    }
}

