<?php

namespace App\Services;

use App\Models\BusinessSettings;
use App\Models\User;
use App\Support\DpaDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exemplaire nominatif de l'accord de traitement des données.
 *
 * Le document public est un modèle vierge, à compléter à la main. Celui-ci est
 * l'exemplaire du client : sa raison sociale, ses identifiants, et la mention
 * de son acceptation.
 *
 * Cette mention tient lieu de signature, et c'est délibéré. Reproduire un
 * paraphe manuscrit qui n'a jamais été tracé n'aurait aucune valeur ; un
 * horodatage, une adresse IP et une version de document en ont une, et c'est
 * exactement ce que le RGPD attend d'une acceptation en ligne.
 */
class DpaPdfService
{
    public function download(User $user): Response
    {
        $settings = BusinessSettings::getInstance();

        $raisonSociale = $settings?->legal_name
            ?: $settings?->company_name
            ?: $user->name;

        $pdf = Pdf::loadView('pdf.dpa', [
            'client' => [
                'raison_sociale' => $raisonSociale,
                'adresse' => $this->adresse($settings),
                'identifiants' => $this->identifiants($settings),
                'contact' => $user->name,
                'email' => $user->email,
            ],
            'acceptation' => [
                'date' => $user->dpa_accepted_at?->format('d/m/Y à H:i') ?? 'non enregistrée',
                'version' => $user->dpa_version ?: DpaDocument::VERSION,
                // Une case cochée en connaissance de cause et une acceptation
                // par renvoi des conditions générales n'ont pas la même portée :
                // le document dit laquelle des deux a eu lieu.
                'par_renvoi' => $user->dpa_acceptance_method === 'terms',
            ],
            'genereLe' => now()->format('d/m/Y'),
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
            ]);

        return $pdf->download($this->nomFichier($raisonSociale));
    }

    private function adresse(?BusinessSettings $settings): string
    {
        if (! $settings) {
            return '—';
        }

        $lignes = array_filter([
            $settings->address,
            trim(($settings->postal_code ?? '').' '.($settings->city ?? '')),
            $settings->country_code,
        ]);

        return $lignes ? implode(', ', $lignes) : '—';
    }

    private function identifiants(?BusinessSettings $settings): string
    {
        if (! $settings) {
            return '—';
        }

        $parts = array_filter([
            $settings->vat_number ? 'TVA '.$settings->vat_number : null,
            $settings->rcs_number ? 'RCS '.$settings->rcs_number : null,
            $settings->matricule ? 'Matricule '.$settings->matricule : null,
        ]);

        return $parts ? implode(' · ', $parts) : '—';
    }

    private function nomFichier(string $raisonSociale): string
    {
        return 'dpa-faktur-lu-'.str($raisonSociale)->slug().'-v'.DpaDocument::VERSION.'.pdf';
    }
}
