<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/** L'alerte du journal d'audit démontrable : chaîne rompue, scellement arrêté, export en échec. */
class AuditAlert extends Mailable
{
    /** @param list<string> $lignes */
    public function __construct(public string $sujet, public array $lignes, public string $conclusion = '') {}

    public function build(): static
    {
        $texte = implode("\n", array_merge(
            ["Le journal d'audit signale :", ''],
            array_map(fn (string $ligne) => '- '.$ligne, $this->lignes),
            ['', $this->conclusion],
        ));

        return $this
            ->subject('['.config('marque.nom').'] '.$this->sujet)
            ->html(nl2br(e($texte)));
    }
}
