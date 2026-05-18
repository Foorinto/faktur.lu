<?php

namespace App\Actions\HR;

use App\Models\HR\HrEvent;
use App\Models\HR\HrEventParticipant;
use App\Models\HR\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateHrEventAction
{
    public function execute(HrEvent $event, array $data): HrEvent
    {
        // Conflict check on room (only if room changed or time changed)
        if (! empty($data['room_id']) && ($data['location_type'] ?? $event->location_type) === 'room') {
            $room = Room::where('user_id', $event->user_id)->findOrFail($data['room_id']);
            if ($room->hasConflict($data['starts_at'], $data['ends_at'], $event->id)) {
                throw ValidationException::withMessages([
                    'room_id' => [__('app.hr_event_room_conflict')],
                ]);
            }
        }

        return DB::transaction(function () use ($event, $data) {
            $event->update([
                'title' => $data['title'] ?? $event->title,
                'type' => $data['type'] ?? $event->type,
                'color' => $data['color'] ?? $event->color,
                'starts_at' => $data['starts_at'] ?? $event->starts_at,
                'ends_at' => $data['ends_at'] ?? $event->ends_at,
                'is_all_day' => $data['is_all_day'] ?? $event->is_all_day,
                'location_type' => $data['location_type'] ?? $event->location_type,
                'room_id' => ($data['location_type'] ?? $event->location_type) === 'room' ? ($data['room_id'] ?? $event->room_id) : null,
                'address' => ($data['location_type'] ?? $event->location_type) === 'address' ? ($data['address'] ?? $event->address) : null,
                'video_url' => ($data['location_type'] ?? $event->location_type) === 'video' ? ($data['video_url'] ?? $event->video_url) : null,
                'description' => $data['description'] ?? $event->description,
            ]);

            // Sync participants if provided
            if (isset($data['participant_employee_ids']) || isset($data['participant_external_emails'])) {
                $event->participants()->delete();

                foreach ($data['participant_employee_ids'] ?? [] as $employeeId) {
                    HrEventParticipant::create([
                        'hr_event_id' => $event->id,
                        'employee_id' => $employeeId,
                        'response' => 'pending',
                    ]);
                }

                foreach ($data['participant_external_emails'] ?? [] as $email) {
                    HrEventParticipant::create([
                        'hr_event_id' => $event->id,
                        'external_email' => $email,
                        'response' => 'pending',
                    ]);
                }
            }

            return $event->fresh(['room', 'participants.employee', 'creator']);
        });
    }
}
