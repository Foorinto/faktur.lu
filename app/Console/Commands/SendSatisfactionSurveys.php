<?php

namespace App\Console\Commands;

use App\Mail\SatisfactionSurveyEmail;
use App\Models\SatisfactionSurvey;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSatisfactionSurveys extends Command
{
    protected $signature = 'survey:send';
    protected $description = 'Envoie le sondage de satisfaction (NPS) aux inscrits, ~14 jours après leur inscription';

    /** Send window (days since signup): from MIN to MAX inclusive. */
    protected int $minDays = 14;
    protected int $maxDays = 21;

    public function handle(): int
    {
        $users = User::query()
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->where('account_status', '!=', 'cancelled')
            ->where('drip_unsubscribed', false)
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $days = (int) $user->created_at->diffInDays(now());

            // Only inside the send window: avoids re-sending and avoids
            // mass-emailing the existing base on first deploy (older accounts skipped).
            if ($days < $this->minDays || $days > $this->maxDays) {
                continue;
            }

            // One survey per user (unique index) → natural dedup.
            if (SatisfactionSurvey::where('user_id', $user->id)->exists()) {
                $skipped++;
                continue;
            }

            try {
                $survey = SatisfactionSurvey::createForUser($user);
                Mail::to($user->email)->send(new SatisfactionSurveyEmail($user, $survey));
                $sent++;
            } catch (\Throwable $e) {
                Log::warning("Satisfaction survey email failed for user {$user->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sondage de satisfaction : {$sent} envoyés, {$skipped} ignorés.");

        return self::SUCCESS;
    }
}
