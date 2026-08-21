<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SectorLead;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lecture des manifestations d'intérêt sectorielles.
 *
 * C'est ici que se tranche la question qui a lancé tout ceci : quel pack métier
 * mérite treize jours de travail. Le décompte par secteur dit lequel attire ;
 * les réponses écrites disent pourquoi.
 */
class AdminSectorLeadController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SectorLeads/Index', [
            'parSecteur' => SectorLead::query()
                ->selectRaw('sector, COUNT(*) as total, SUM(CASE WHEN email IS NOT NULL THEN 1 ELSE 0 END) as avec_email')
                ->groupBy('sector')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($l) => [
                    'secteur' => $l->sector,
                    'libelle' => __("app.business_sectors.{$l->sector}.label", [], 'fr'),
                    'total' => (int) $l->total,
                    'avec_email' => (int) $l->avec_email,
                ]),

            'reponses' => SectorLead::query()
                ->latest()
                ->limit(200)
                ->get()
                ->map(fn (SectorLead $l) => [
                    'id' => $l->id,
                    'secteur' => $l->sectorLabel(),
                    'email' => $l->email,
                    'message' => $l->message,
                    'newsletter' => $l->wants_newsletter,
                    'date' => $l->created_at?->format('d/m/Y H:i'),
                ]),
        ]);
    }

    /**
     * Supprime une réponse.
     *
     * Utile pour le spam qui passe le pot de miel, et nécessaire pour honorer
     * une demande d'effacement : l'adresse est une donnée personnelle, donnée
     * pour être recontacté, et rien n'oblige la personne à le rester.
     *
     * La suppression est définitive et ne concerne que cette table. Une
     * inscription à l'infolettre, si elle existe, vit ailleurs et survit : les
     * deux consentements ont été donnés pour des raisons différentes, et
     * l'effacement de l'un ne vaut pas retrait de l'autre.
     */
    public function destroy(SectorLead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()
            ->route('admin.sector-leads.index')
            ->with('success', 'Réponse supprimée.');
    }

    public function export(): StreamedResponse
    {
        $nom = 'interets-sectoriels-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF"); // BOM UTF-8, pour Excel
            fputcsv($sortie, ['secteur', 'email', 'reponse', 'newsletter', 'langue', 'date']);

            SectorLead::query()->latest()->chunk(500, function ($lots) use ($sortie) {
                foreach ($lots as $l) {
                    fputcsv($sortie, [
                        $l->sectorLabel(),
                        $l->email,
                        $l->message,
                        $l->wants_newsletter ? 'oui' : 'non',
                        $l->locale,
                        $l->created_at?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($sortie);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
