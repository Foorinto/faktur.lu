<?php

namespace App\Casts;

/**
 * Marque un cast qui chiffre l'attribut au repos.
 *
 * Le journal d'audit masque tout attribut chiffré (AuditLogger). Il
 * reconnaît le cast `encrypted` de Laravel par son nom ; un cast maison doit
 * porter cette interface pour être traité de la même façon, sinon sa valeur
 * repartirait en clair dans le journal.
 */
interface EncryptsAttribute {}
