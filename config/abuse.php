<?php

/*
|--------------------------------------------------------------------------
| Protections contre les inscriptions frauduleuses (FEAT-138)
|--------------------------------------------------------------------------
|
| Septembre 2026 : des comptes créés avec des adresses jetables et des noms
| de marques usurpées (« Vinted », « Poshmark Support ») utilisaient l'essai
| pour envoyer, depuis notre domaine, des factures de phishing crédibles
| (PDF avec logo, IBAN, QR code de paiement). La logique vit dans
| App\Services\AbuseProtectionService ; ce fichier n'en porte que les listes
| et les seuils.
|
*/

return [

    /*
    | Liste communautaire des domaines d'adresses jetables, une ligne par
    | domaine (environ 9 000 en septembre 2026). Téléchargée chaque semaine par
    | `abuse:update-disposable-list` dans storage/app/private/abuse/. Un
    | téléchargement raté ou suspect garde la dernière liste connue.
    */
    'disposable_list_url' => env(
        'ABUSE_DISPOSABLE_LIST_URL',
        'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/main/disposable_email_blocklist.conf'
    ),

    // En dessous de ce nombre de lignes, la réponse est jugée anormale (page
    // d'erreur, fichier tronqué) et l'ancienne liste est conservée.
    'disposable_list_min_lines' => 1000,

    /*
    | Socle toujours appliqué, même si la liste n'a jamais été téléchargée
    | (installation neuve, serveur sans accès sortant). Tous figurent dans la
    | liste communautaire.
    */
    'disposable_seed' => [
        '10minutemail.com', '10minutemail.net', '1secmail.com', '1secmail.net',
        '1secmail.org', 'byom.de', 'discard.email', 'dispostable.com',
        'emailfake.com', 'emailondeck.com', 'fakeinbox.com', 'fakemail.net',
        'getairmail.com', 'getnada.com', 'grr.la', 'guerrillamail.biz',
        'guerrillamail.com', 'guerrillamail.de', 'guerrillamail.info',
        'guerrillamail.net', 'guerrillamail.org', 'guerrillamailblock.com',
        'harakirimail.com', 'inboxkitten.com', 'jetable.org', 'mail.tm',
        'mailcatch.com', 'maildrop.cc', 'mailinator.com', 'mailnesia.com',
        'mailpoof.com', 'mailsac.com', 'mintemail.com', 'moakt.com',
        'mohmal.com', 'mytemp.email', 'nada.email', 'sharklasers.com',
        'spam4.me', 'temp-mail.org', 'tempail.com', 'tempinbox.com',
        'tempmailo.com', 'tempr.email', 'throwawaymail.com', 'trash-mail.com',
        'trashmail.com', 'trashmail.de', 'yopmail.com', 'yopmail.fr',
        'yopmail.net',
    ],

    /*
    | Noms qu'un fraudeur emprunte pour rendre crédible une fausse facture :
    | places de marché, transporteurs, moyens de paiement, et les mots qui
    | imitent un service officiel.
    |
    | Correspondance (AbuseProtectionService::matchesBrand) : insensible à la
    | casse, aux accents et au remplissage (« V-i-n-t-e-d », « P@yPal »).
    |   - `words` : mot entier seulement. Pour les noms courts ou courants,
    |     sinon « Compost SARL » (post) ou « Groupe Administratif » (admin)
    |     seraient pris.
    |   - `embedded` : aussi à l'intérieur d'un mot (« VintedLux »). Réservé
    |     aux marques longues et sans sens courant.
    */
    'brands' => [
        // Les marques d'abord, les mots génériques ensuite : la première
        // correspondance devient la raison du signalement, « vinted » dit plus
        // que « support » pour « Vinted Support ».
        'words' => [
            'vinted', 'poshmark', 'paypal', 'amazon', 'ebay', 'leboncoin',
            'wallapop', 'depop', 'dhl', 'ups', 'fedex', 'gls', 'post', 'bpost',
            'chronopost', 'colissimo', 'mondial relay', 'western union',
            'banque', 'bank', 'admin', 'support',
        ],
        // Pas « amazon » ici : « Amazonie Voyages » serait refusé. Le mot
        // entier suffit (« Amazon Retours », « A-m-a-z-o-n »).
        'embedded' => [
            'vinted', 'poshmark', 'paypal', 'leboncoin', 'wallapop',
            'chronopost', 'colissimo', 'mondialrelay', 'westernunion',
        ],
    ],

    /*
    | Envois de documents par jour pendant l'essai, pour un compte qui envoie
    | par nos serveurs. Un compte qui a branché son propre fournisseur, ou un
    | abonné, n'est pas concerné : c'est notre domaine qu'on protège.
    */
    'trial_daily_document_emails' => (int) env('ABUSE_TRIAL_DAILY_DOCUMENT_EMAILS', 5),

];
