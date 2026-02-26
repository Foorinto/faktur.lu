<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendReminderNotifications extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send email notifications for due reminders';

    public function handle(): int
    {
        $reminders = Reminder::withoutGlobalScope('user')
            ->where('is_completed', false)
            ->where('remind_at', '<=', now())
            ->with(['client' => fn ($q) => $q->withoutGlobalScope('user')->select('id', 'name'), 'user:id,name,email'])
            ->get();

        $count = 0;

        foreach ($reminders as $reminder) {
            if (!$reminder->user || !$reminder->user->email) {
                continue;
            }

            try {
                Mail::raw(
                    "Rappel : {$reminder->title}\n\n" .
                    "Client : {$reminder->client->name}\n" .
                    ($reminder->description ? "Description : {$reminder->description}\n" : '') .
                    "Date prévue : {$reminder->remind_at->format('d/m/Y H:i')}",
                    function ($message) use ($reminder) {
                        $message->to($reminder->user->email)
                            ->subject("Rappel : {$reminder->title} - {$reminder->client->name}");
                    }
                );

                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to send reminder #{$reminder->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$count} reminder notification(s).");

        return Command::SUCCESS;
    }
}
