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
        ]);

        SectorLead::create([
            'sector' => $donnees['sector'],
            'email' => $donnees['email'],
            'message' => $donnees['message'] ?? null,
            'locale' => app()->getLocale(),
            'wants_newsletter' => (bool) ($donnees['wants_newsletter'] ?? false),
        ]);

        return back()->with('success', __('app.sector_lead.thanks'));
    }
}
