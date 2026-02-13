<x-mail::message>
# Bonjour {{ $user->name }},

Votre période d'essai gratuit de faktur.lu se termine dans **{{ $daysRemaining }} {{ $daysRemaining > 1 ? 'jours' : 'jour' }}**.

Pendant votre essai, vous avez eu accès à toutes les fonctionnalités Pro :
- Clients et factures illimités
- Export FAIA pour le contrôle fiscal
- Archivage PDF/A 10 ans
- Relances automatiques

## Continuez à facturer sereinement

Choisissez le plan qui correspond à vos besoins :

**Essentiel** - 4€/mois
- 10 clients, 20 factures/mois
- Idéal pour démarrer

**Pro** - 9€/mois
- Tout illimité + FAIA + archivage
- Pour les freelances établis

<x-mail::button :url="$subscriptionUrl" color="primary">
Choisir mon abonnement
</x-mail::button>

Si vous avez des questions, n'hésitez pas à nous contacter via le support.

Cordialement,<br>
L'équipe faktur.lu
</x-mail::message>
