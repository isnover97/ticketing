<?php

namespace App\Support;

use App\Models\HelpdeskRecord;
use App\Models\Ticket;
use Illuminate\Support\Collection;

class CannedResponses
{
    private const REQUEST_DETAILS = [
        'Email Creation' => 'Please provide the employee’s full name, department, and requested start date.',
        'NAS Concern' => 'Please provide the shared folder path, the action you were trying to perform, and the exact error message.',
        'Password Resets (NAS, Email, HRIS)' => 'Please confirm the affected system and account username. Do not share your password or verification codes.',
        'Hardware Support' => 'Please provide the device name or asset tag, the symptoms, and when they started.',
        'Preventive Maintenance' => 'Please provide the device names or asset tags, their location, and a suitable maintenance window.',
        'CCTV Review' => 'Please provide the camera location, the date and approximate time of the incident, and the reason for the review.',
        'Hardware Upgrade' => 'Please provide the device asset tag, the requested upgrade, and the reason you need it.',
        'Meeting Link Creation' => 'Please provide the meeting title, date, start time and time zone, duration, and organizer.',
        'Internet Connection Problem' => 'Please provide your location, whether you are using Wi-Fi or a wired connection, and whether other devices are affected.',
        'Guest WIFI Voucher' => 'Please provide the visit date, the number of guests, and how long access is needed.',
        'HRIS (HERA) Concern' => 'Please provide the affected HRIS feature, the steps you took, and the exact error message.',
        'CRMS Concern' => 'Please provide the affected CRMS feature, the steps you took, and the exact error message.',
        'DMS Concern' => 'Please provide the affected DMS feature, the document reference if applicable, and the exact error message.',
    ];

    public static function forTicket(Ticket $ticket): Collection
    {
        $responses = HelpdeskRecord::ofKind('canned')->active()->orderBy('name')->get();
        $suggested = collect();
        $general = collect();

        foreach ($responses as $response) {
            $settings = $response->settings;
            $topic = $settings['topic_id'] ?? null;
            $category = $settings['category_id'] ?? null;

            if (! $topic && ! $category) {
                $general->push($response);
            } elseif ((! $topic || (int) $topic === (int) $ticket->help_topic_id)
                && (! $category || (int) $category === (int) $ticket->category_id)) {
                $suggested->push($response);
            }
        }

        $details = self::REQUEST_DETAILS[$ticket->helpTopic?->name] ?? null;
        if ($suggested->isEmpty() && $details) {
            $suggested->push(new HelpdeskRecord([
                'name' => 'Request details for '.$ticket->helpTopic->name,
                'settings' => ['body' => 'Thank you for contacting IT Support. '.$details],
            ]));
        }

        return collect(['Suggested for this request' => $suggested, 'General responses' => $general])
            ->filter(fn ($group) => $group->isNotEmpty());
    }
}
