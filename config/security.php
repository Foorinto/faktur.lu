<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Second facteur obligatoire pour les comptes exposés (FEAT-124)
    |--------------------------------------------------------------------------
    |
    | La commande quotidienne security:enforce-two-factor envoie de vrais mails
    | (préavis, rappel, activation). Cet interrupteur permet de déployer le
    | code sans lancer le parcours, puis de le lancer à la date voulue en
    | passant la variable à true, sans nouveau déploiement.
    |
    */

    'enforce_two_factor' => (bool) env('SECURITY_ENFORCE_TWO_FACTOR', false),

];
