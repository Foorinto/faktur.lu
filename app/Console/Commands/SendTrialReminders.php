<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingSoon;
use App\Mail\TrialExpired;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTrialReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trial:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send trial reminder emails to users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->sendTrialEndingSoonReminders();
        $this->sendTrialExpiredNotifications();
        $this->updateExpiredTrialStatus();

        return self::SUCCESS;
    }

    /**
     * Send reminders to users whose trial is ending in 3 days.
     */
    protected function sendTrialEndingSoonReminders(): void
    {
        $threeDaysFromNow = Carbon::now()->addDays(3)->startOfDay();
        $endOfDay = $threeDaysFromNow->copy()->endOfDay();

        $users = User::whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [$threeDaysFromNow, $endOfDay])
            ->where('account_status', 'trial')
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('stripe_status', 'active');
            })
            ->get();

        foreach ($users as $user) {
            Mail::to($user->email)->send(new TrialEndingSoon($user, 3));
            $this->info("Sent trial ending soon email to: {$user->email}");
        }

        $this->info("Sent {$users->count()} trial ending soon reminders.");
    }

    /**
     * Send notifications to users whose trial expired today.
     */
    protected function sendTrialExpiredNotifications(): void
    {
        $today = Carbon::now()->startOfDay();
        $endOfToday = Carbon::now()->endOfDay();

        $users = User::whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [$today, $endOfToday])
            ->where('account_status', 'trial')
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('stripe_status', 'active');
            })
            ->get();

        foreach ($users as $user) {
            Mail::to($user->email)->send(new TrialExpired($user));
            $this->info("Sent trial expired email to: {$user->email}");
        }

        $this->info("Sent {$users->count()} trial expired notifications.");
    }

    /**
     * Update account_status to 'expired' for users whose trial has ended.
     */
    protected function updateExpiredTrialStatus(): void
    {
        $count = User::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', Carbon::now())
            ->where('account_status', 'trial')
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('stripe_status', 'active');
            })
            ->update(['account_status' => 'expired']);

        $this->info("Updated {$count} users to expired status.");
    }
}
