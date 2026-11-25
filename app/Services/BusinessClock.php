<?php

namespace App\Services;

use App\Models\HelpdeskRecord;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class BusinessClock
{
    public function due(HelpdeskRecord $sla): CarbonImmutable
    {
        $minutes = max(1, (int) round((float) $sla->settings['hours'] * 60));
        $schedule = ! empty($sla->settings['schedule_id']) ? HelpdeskRecord::ofKind('schedules')->active()->find($sla->settings['schedule_id']) : null;
        $cursor = CarbonImmutable::instance(now());
        if (! $schedule) {
            return $cursor->addMinutes($minutes);
        }
        $s = $schedule->settings;
        $cursor = $cursor->setTimezone($s['timezone']);
        if (empty($s['days']) || ($s['start'] ?? '') >= ($s['end'] ?? '')) {
            throw ValidationException::withMessages(['sla' => 'The business schedule has no valid working hours.']);
        }
        $holidays = array_map('trim', preg_split('/\R/', trim($s['holidays'] ?? '')));
        for ($i = 0; $i < 25000; $i++) {
            if (! in_array($cursor->dayOfWeekIso, array_map('intval', $s['days']), true) || in_array($cursor->toDateString(), $holidays, true)) {
                $cursor = $cursor->addDay()->startOfDay();

                continue;
            }
            $start = $cursor->setTimeFromTimeString($s['start']);
            $end = $cursor->setTimeFromTimeString($s['end']);
            if ($cursor->lessThan($start)) {
                $cursor = $start;
            }
            if ($cursor->greaterThanOrEqualTo($end)) {
                $cursor = $cursor->addDay()->startOfDay();

                continue;
            }
            $available = (int) floor($cursor->diffInSeconds($end) / 60);
            if ($minutes <= $available) {
                return $cursor->addMinutes($minutes)->setTimezone(config('app.timezone'));
            }
            $minutes -= $available;
            $cursor = $cursor->addDay()->startOfDay();
        }
        throw ValidationException::withMessages(['sla' => 'The selected business schedule cannot accommodate this SLA target.']);
    }
}
