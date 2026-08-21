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
            // L'email est facultatif : quelqu'un peut décrire sa situation sans
            // vouloir être recontacté, et cette réponse compte autant.
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'wants_newsletter' => ['boolean'],
        ]);

        // Un formulaire entièrement vide n'apprend rien et n'a pas à encombrer
        // la table : c'est un clic distrait, pas une réponse.
        if (blank($donnees['email'] ?? null) && blank($donnees['message'] ?? null)) {
            return back()->with('error', __('app.sector_lead.empty'));
        }

        SectorLead::create([
            'sector' => $donnees['sector'],
            'email' => $donnees['email'] ?? null,
            'message' => $donnees['message'] ?? null,
            'locale' => app()->getLocale(),
            'wants_newsletter' => (bool) ($donnees['wants_newsletter'] ?? false),
        ]);

        return back()->with('success', __('app.sector_lead.thanks'));
    }
}
