<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncStripeSubscription extends Command
{
    protected $signature = 'stripe:sync-subscription {user : ID ou email de l\'utilisateur}';

    protected $description = 'Synchronise la subscription Stripe d\'un utilisateur depuis Stripe vers la BDD locale (utile si le webhook a echoue)';

    public function handle(): int
    {
        $userInput = $this->argument('user');
        $user = is_numeric($userInput)
            ? User::find($userInput)
            : User::where('email', $userInput)->first();

        if (! $user) {
            $this->error("Utilisateur introuvable : {$userInput}");
            return self::FAILURE;
        }

        $this->info("Utilisateur : {$user->email} (ID {$user->id})");

        if (! $user->stripe_id) {
            $this->error('Cet utilisateur n\'a pas de stripe_id. Il n\'a probablement jamais initie de paiement Stripe.');
            return self::FAILURE;
        }

        $this->info("Stripe customer : {$user->stripe_id}");

        try {
            $stripe = $user->stripe();
            $stripeSubs = $stripe->subscriptions->all([
                'customer' => $user->stripe_id,
                'status' => 'all',
                'limit' => 10,
                'expand' => ['data.items.data'],
            ]);
        } catch (\Throwable $e) {
            $this->error('Erreur API Stripe : ' . $e->getMessage());
            return self::FAILURE;
        }

        if (empty($stripeSubs->data)) {
            $this->warn('Aucune subscription Stripe trouvee pour ce customer.');
            return self::SUCCESS;
        }

        $synced = 0;
        foreach ($stripeSubs->data as $stripeSubscription) {
            $this->info("\nSubscription Stripe : {$stripeSubscription->id}");
            $this->line("  Statut : {$stripeSubscription->status}");

            $firstItem = $stripeSubscription->items->data[0] ?? null;
            if (! $firstItem) {
                $this->warn('  Aucun item dans cette subscription, skip.');
                continue;
            }

            $this->line("  Price : {$firstItem->price->id}");

            $isSinglePrice = count($stripeSubscription->items->data) === 1;
            $trialEndsAt = $stripeSubscription->trial_end
                ? Carbon::createFromTimestamp($stripeSubscription->trial_end)
                : null;
            $endsAt = $stripeSubscription->ended_at
                ? Carbon::createFromTimestamp($stripeSubscription->ended_at)
                : ($stripeSubscription->cancel_at
                    ? Carbon::createFromTimestamp($stripeSubscription->cancel_at)
                    : null);

            $localSub = $user->subscriptions()->updateOrCreate(
                ['stripe_id' => $stripeSubscription->id],
                [
                    'type' => 'default',
                    'stripe_status' => $stripeSubscription->status,
                    'stripe_price' => $isSinglePrice ? $firstItem->price->id : null,
                    'quantity' => $isSinglePrice && isset($firstItem->quantity) ? $firstItem->quantity : null,
                    'trial_ends_at' => $trialEndsAt,
                    'ends_at' => $endsAt,
                ]
            );

            foreach ($stripeSubscription->items->data as $item) {
                $localSub->items()->updateOrCreate(
                    ['stripe_id' => $item->id],
                    [
                        'stripe_product' => $item->price->product,
                        'stripe_price' => $item->price->id,
                        'quantity' => $item->quantity ?? 1,
                    ]
                );
            }

            $this->info('  -> Synchronisee localement');
            $synced++;
        }

        $this->info("\n{$synced} subscription(s) synchronisee(s).");
        $this->line("\nVerification :");
        $user->refresh();
        $this->line('  isPro : ' . ($user->isPro() ? 'oui' : 'non'));
        $this->line('  isEssentiel : ' . ($user->isEssentiel() ? 'oui' : 'non'));
        $this->line('  isFree : ' . ($user->isFree() ? 'oui' : 'non'));

        return self::SUCCESS;
    }
}
