<?php

namespace App\Console\Commands;

use App\Mail\HrEventReminder;
use App\Models\HR\HrEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command
{
    protected $signature = 'hr:send-event-reminders';

    protected $description = 'Send reminder emails to HR event participants (J-1 and J0).';

    public function handle(): int
    {
        $now = Carbon::now();

        // J-1: events tomorrow
        $tomorrowStart = $now->copy()->addDay()->startOfDay();
        $tomorrowEnd = $now->copy()->addDay()->endOfDay();

        $this->sendForRange($tomorrowStart, $tomorrowEnd, false);

        // J0: events today (run in the morning)
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        $this->sendForRange($todayStart, $todayEnd, true);

        return self::SUCCESS;
    }

    protected function sendForRange(Carbon $start, Carbon $end, bool $isSameDay): void
    {
        $events = HrEvent::query()
            ->withoutGlobalScope('user') // Run across all tenants
            ->with(['participants.employee.account', 'room'])
            ->whereBetween('starts_at', [$start, $end])
            ->get();

        foreach ($events as $event) {
            foreach ($event->participants as $participant) {
                $email = $participant->employee?->account?->email
                    ?? $participant->employee?->email_pro
                    ?? $participant->external_email;

                if (! $email) {
                    continue;
                }

                try {
                    Mail::to($email)->send(new HrEventReminder($event, $participant, $isSameDay));
                    $this->info(sprintf(
                        'Sent reminder (%s) for event %d to %s',
                        $isSameDay ? 'today' : 'tomorrow',
                        $event->id,
                        $email
                    ));
                } catch (\Throwable $e) {
                    $this->error(sprintf(
                        'Failed to send reminder for event %d to %s: %s',
                        $event->id,
                        $email,
                        $e->getMessage()
                    ));
                }
            }
        }
    }
}
