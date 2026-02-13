<x-mail::message>
# Bonjour {{ $user->name }},

Votre période d'essai gratuit de faktur.lu est maintenant terminée.

## Votre compte est en lecture seule

Vous pouvez toujours vous connecter pour :
- Consulter vos factures existantes
- Télécharger vos documents
- Accéder à vos données

Mais vous ne pouvez plus créer de nouvelles factures ou devis.

## Réactivez votre compte

Choisissez un abonnement pour retrouver l'accès complet :

**Essentiel** - 4€/mois
- 10 clients, 20 factures/mois

**Pro** - 9€/mois
- Tout illimité + FAIA + archivage

<x-mail::button :url="$subscriptionUrl" color="primary">
S'abonner maintenant
</x-mail::button>

Vos données sont conservées et vous attendent. Aucune perte d'information.

Cordialement,<br>
L'équipe faktur.lu
</x-mail::message>
