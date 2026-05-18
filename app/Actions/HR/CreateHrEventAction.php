<?php

namespace App\Actions\HR;

use App\Mail\HrEventInvitation;
use App\Models\HR\Employee;
use App\Models\HR\HrEvent;
use App\Models\HR\HrEventParticipant;
use App\Models\HR\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CreateHrEventAction
{
    /**
     * Create a HR event after validating room availability.
     *
     * @param  array  $data  Validated payload from controller/FormRequest
     * @param  User  $user   Owner of the organisation
     * @param  Employee  $creator  Employee who creates the event
     */
    public function execute(array $data, User $user, Employee $creator): HrEvent
    {
        $startsAt = $data['starts_at'] instanceof \DateTimeInterface
            ? $data['starts_at']
            : new \DateTime($data['starts_at']);
        $endsAt = $data['ends_at'] instanceof \DateTimeInterface
            ? $data['ends_at']
            : new \DateTime($data['ends_at']);

        // Conflict check on room
        if (! empty($data['room_id']) && ($data['location_type'] ?? null) === 'room') {
            $room = Room::where('user_id', $user->id)->findOrFail($data['room_id']);
            if ($room->hasConflict($startsAt, $endsAt)) {
                throw ValidationException::withMessages([
                    'room_id' => [__('app.hr_event_room_conflict')],
                ]);
            }
        }

        return DB::transaction(function () use ($data, $user, $creator) {
            $event = HrEvent::create([
                'user_id' => $user->id,
                'created_by_employee_id' => $creator->id,
                'title' => $data['title'],
                'type' => $data['type'] ?? 'meeting',
                'color' => $data['color'] ?? null,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'is_all_day' => $data['is_all_day'] ?? false,
                'location_type' => $data['location_type'] ?? 'none',
                'room_id' => ($data['location_type'] ?? null) === 'room' ? ($data['room_id'] ?? null) : null,
                'address' => ($data['location_type'] ?? null) === 'address' ? ($data['address'] ?? null) : null,
                'video_url' => ($data['location_type'] ?? null) === 'video' ? ($data['video_url'] ?? null) : null,
                'description' => $data['description'] ?? null,
            ]);

            // Internal participants (employees)
            foreach ($data['participant_employee_ids'] ?? [] as $employeeId) {
                HrEventParticipant::create([
                    'hr_event_id' => $event->id,
                    'employee_id' => $employeeId,
                    'response' => 'pending',
                ]);
            }

            // External participants (emails)
            foreach ($data['participant_external_emails'] ?? [] as $email) {
                HrEventParticipant::create([
                    'hr_event_id' => $event->id,
                    'external_email' => $email,
                    'response' => 'pending',
                ]);
            }

            $event->load(['room', 'participants.employee.account', 'creator']);

            // Dispatch invitation emails (best-effort, swallow errors)
            foreach ($event->participants as $participant) {
                $email = $participant->employee?->account?->email
                    ?? $participant->employee?->email_pro
                    ?? $participant->external_email;

                if (! $email) {
                    continue;
                }

                try {
                    Mail::to($email)->queue(new HrEventInvitation($event, $participant));
                } catch (\Throwable $e) {
                    // Log only, do not fail the event creation
                    \Log::warning('HrEventInvitation send failed', [
                        'event_id' => $event->id,
                        'participant_id' => $participant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $event;
        });
    }
}
