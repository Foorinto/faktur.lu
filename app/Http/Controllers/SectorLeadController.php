<?php

namespace App\Http\Controllers;

use App\Models\SectorLead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Réception des manifestations d'intérêt déposées sur les pages sectorielles.
 *
 * La question posée est « qu'est-ce qui vous prend le plus de temps ? » plutôt
 * que « comment facturez-vous ? ». La première dit quoi construire, la seconde
 * contre quoi se battre : c'est la première qui doit trancher si un pack métier
 * vaut treize jours de travail.
 */
class SectorLeadController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'sector' => ['required', 'string', Rule::in(User::BUSINESS_SECTORS)],
            // L'email est OBLIGATOIRE.
            //
            // Il était facultatif au motif qu'une réponse anonyme comptait
            // autant pour la mesure. C'était faux : le volume se mesure déjà
            // par les impressions de recherche. Ce que ce formulaire apporte de
            // spécifique, c'est un contact dans un secteur où nous n'en avons
            // aucun — et de quoi prévenir la personne le jour où le pack
            // qu'elle a réclamé existe.
            'email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'wants_newsletter' => ['boolean'],
            // La source n'est PAS validée strictement, elle est nettoyée.
            //
            // Elle vient d'un paramètre d'URL que n'importe qui peut tordre.
            // Refuser la réponse entière parce qu'un `utm_source` est bizarre
            // reviendrait à perdre le contact pour sauver une étiquette : on
            // garde la réponse et on jette l'étiquette.
            'source' => ['nullable', 'string', 'max:200'],
        ]);

        SectorLead::create([
            'sector' => $donnees['sector'],
            'source' => $this->sourceNettoyee($donnees['source'] ?? null),
            'email' => $donnees['email'],
            'message' => $donnees['message'] ?? null,
            'locale' => app()->getLocale(),
            'wants_newsletter' => (bool) ($donnees['wants_newsletter'] ?? false),
        ]);

        return back()->with('success', __('app.sector_lead.thanks'));
    }

    /**
     * Ne retient qu'une étiquette de canal plausible.
     *
     * Ni accents, ni espaces, ni chemin : de quoi nommer une fédération, un
     * réseau ou un cabinet. Tout le reste est écarté sans faire échouer l'envoi.
     */
    protected function sourceNettoyee(?string $brute): ?string
    {
        $source = trim((string) $brute);

        if ($source === '' || ! preg_match('/^[a-zA-Z0-9._-]{1,60}$/', $source)) {
            return null;
        }

        return mb_strtolower($source);
    }
}
