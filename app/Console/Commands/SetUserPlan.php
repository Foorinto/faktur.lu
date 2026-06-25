<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Set or change a user's plan manually (no Stripe interaction).
 *
 *     php artisan user:set-plan email@example.com free
 *     php artisan user:set-plan email@example.com essentiel
 *     php artisan user:set-plan email@example.com pro
 *     php artisan user:set-plan email@example.com pro --yearly
 *
 * The created subscription is a local-only record (stripe_id = "manual_<plan>_<userId>")
 * so it never hits the Stripe API. To convert a manual subscription into a
 * real Stripe one later, simply delete it and have the user subscribe via
 * the regular checkout flow.
 */
class SetUserPlan extends Command
{
    protected $signature = 'user:set-plan
                            {email : E-mail du compte utilisateur}
                            {plan : Plan cible : free | trial | essentiel | pro}
                            {--yearly : Utiliser le prix annuel plutot que mensuel (pour essentiel/pro)}
                            {--days=14 : Duree du trial en jours (uniquement pour plan=trial)}
                            {--force : Ne pas demander de confirmation}';

    protected $description = 'Change manuellement le plan d\'un utilisateur (Free / Trial / Essentiel / Pro), sans passer par Stripe';

    private const SUPPORTED_PLANS = ['free', 'trial', 'essentiel', 'pro'];

    public function handle(): int
    {
        $email = $this->argument('email');
        $plan = strtolower($this->argument('plan'));
        $yearly = (bool) $this->option('yearly');

        if (! in_array($plan, self::SUPPORTED_PLANS, true)) {
            $this->error("Plan invalide. Valeurs autorisees : " . implode(', ', self::SUPPORTED_PLANS));
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("Utilisateur '$email' introuvable.");
            return self::FAILURE;
        }

        $currentPlan = $user->plan ?? 'free';
        $this->info("Utilisateur : {$user->name} ({$email})");
        $this->info("Plan actuel : {$currentPlan}");
        $this->info("Nouveau plan : {$plan}" . ($yearly && $plan !== 'free' ? ' (annuel)' : ($plan !== 'free' ? ' (mensuel)' : '')));

        if (! $this->option('force')) {
            if (! $this->confirm('Appliquer ce changement ?', true)) {
                $this->warn('Annule.');
                return self::SUCCESS;
            }
        }

        // Remove any existing subscription (Stripe or manual)
        $existing = $user->subscriptions()->get();
        foreach ($existing as $sub) {
            $sub->items()->delete();
            $sub->delete();
        }
        $this->line('  - ' . $existing->count() . ' abonnement(s) precedent(s) supprime(s).');

        if ($plan === 'free') {
            // Free = no subscription, no trial.
            $user->forceFill(['trial_ends_at' => null])->save();
            $this->info("Plan applique : Free (aucun abonnement, aucun trial).");
            $this->info("Plan detecte par le modele : " . $user->fresh()->plan);
            return self::SUCCESS;
        }

        if ($plan === 'trial') {
            // Trial = no subscription, but trial_ends_at in the future grants Pro access
            $days = max(1, (int) $this->option('days'));
            $user->forceFill(['trial_ends_at' => now()->addDays($days)])->save();
            $this->info("Plan applique : Trial ($days jours, expire le " . $user->fresh()->trial_ends_at->format('d/m/Y') . ").");
            $this->info("Plan detecte par le modele : " . $user->fresh()->plan);
            return self::SUCCESS;
        }

        // Essentiel or Pro -> create a manual local subscription
        $targetPlan = $plan === 'pro' ? Plan::pro() : Plan::essentiel();
        if (! $targetPlan) {
            $this->error("Plan '$plan' introuvable dans la table 'plans'. Lancez 'php artisan db:seed --class=PlansSeeder'.");
            return self::FAILURE;
        }

        $stripePrice = $yearly
            ? ($targetPlan->stripe_price_id_yearly ?? $targetPlan->stripe_price_id_monthly)
            : ($targetPlan->stripe_price_id_monthly ?? $targetPlan->stripe_price_id_yearly);

        if (! $stripePrice) {
            // Fallback identifier; doesn't hit Stripe - only used to satisfy the local Cashier schema
            $stripePrice = "manual_{$plan}_" . ($yearly ? 'yearly' : 'monthly');
        }

        $stripeId = "manual_{$plan}_" . $user->id;

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => $stripeId,
            'stripe_status' => 'active',
            'stripe_price' => $stripePrice,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $subscription->items()->create([
            'stripe_id' => "si_manual_{$plan}_" . $user->id,
            'stripe_product' => "prod_manual_{$plan}",
            'stripe_price' => $stripePrice,
            'quantity' => 1,
        ]);

        // Clear the generic trial since the user now has an active paid plan
        $user->forceFill(['trial_ends_at' => null])->save();

        $this->info("Plan applique : " . ucfirst($plan) . ($yearly ? ' (annuel)' : ' (mensuel)') . ".");
        $this->info("Plan detecte par le modele : " . $user->fresh()->plan);

        $this->newLine();
        $this->warn("Note : cet abonnement est local (stripe_id={$stripeId}). Aucun debit Stripe ne sera realise. Pour le revoquer : 'php artisan user:set-plan {$email} free'.");

        return self::SUCCESS;
    }
}
