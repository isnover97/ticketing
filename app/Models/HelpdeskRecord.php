<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpdeskRecord extends Model
{
    protected $fillable = ['kind', 'name', 'description', 'settings', 'is_active'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'is_active' => 'boolean'];
    }

    public function scopeOfKind($query, string $kind)
    {
        return $query->where('kind', $kind);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function selectOptions(): array
    {
        $list = ! empty($this->settings['list_id']) ? self::ofKind('lists')->active()->find($this->settings['list_id']) : null;

        return array_values(array_filter(array_map('trim', preg_split('/\R/', trim($list?->settings['options'] ?? $this->settings['options'] ?? ''))), fn ($v) => $v !== ''));
    }

    public static function fieldsForTopic(?self $topic)
    {
        return self::availableFields()->filter(function ($field) use ($topic) {
            $s = $field->settings;
            if (! empty($s['topic_id']) && (int) $s['topic_id'] !== (int) $topic?->id) {
                return false;
            }
            if (! empty($s['form_id']) && ! in_array((int) $s['form_id'], array_map('intval', $topic?->settings['form_ids'] ?? []), true)) {
                return false;
            }

            return true;
        });
    }

    public static function availableFields()
    {
        $forms = self::ofKind('forms')->active()->pluck('id')->map(fn ($id) => (int) $id)->all();
        return self::ofKind('fields')->active()->get()->filter(fn ($field) => empty($field->settings['form_id']) || in_array((int) $field->settings['form_id'], $forms, true));
    }
}
