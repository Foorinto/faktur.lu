<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;

class GrantLifetimePro extends Command
{
    protected $signature = 'user:grant-lifetime-pro {email}';
    protected $description = 'Accorde un abonnement Pro illimité à vie à un utilisateur (sans Stripe)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Utilisateur '$email' introuvable.");
            return 1;
        }

        $proPlan = Plan::pro();
        if (!$proPlan) {
            $this->error('Plan Pro introuvable en base.');
            return 1;
        }

        // Supprimer les abonnements existants pour cet utilisateur
        $existingSubs = $user->subscriptions()->get();
        foreach ($existingSubs as $sub) {
            $sub->items()->delete();
            $sub->delete();
        }

        // Créer un abonnement local (pas lié à Stripe)
        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'lifetime_pro_' . $user->id,
            'stripe_status' => 'active',
            'stripe_price' => $proPlan->stripe_price_id_yearly ?? 'lifetime_pro',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null, // null = jamais d'expiration
        ]);

        $subscription->items()->create([
            'stripe_id' => 'si_lifetime_pro_' . $user->id,
            'stripe_product' => 'prod_lifetime_pro',
            'stripe_price' => $proPlan->stripe_price_id_yearly ?? 'lifetime_pro',
            'quantity' => 1,
        ]);

        $this->info("Abonnement Pro illimité à vie accordé à {$user->name} ({$email})");
        $this->info("Plan détecté : " . $user->fresh()->plan);

        return 0;
    }
}
