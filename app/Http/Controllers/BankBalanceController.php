<?php

namespace App\Http\Controllers;

use App\Models\BankBalance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Le relevé de solde bancaire, saisi à la main.
 *
 * Point de départ de la prévision de trésorerie : sans lui, le graphique part
 * de zéro et ne prévoit pas un solde mais une variation.
 */
class BankBalanceController extends Controller
{
    /**
     * Enregistre un relevé. Chaque saisie s'ajoute à l'historique plutôt que
     * d'écraser la précédente : la prévision repart du plus récent, et les
     * anciens relevés gardent leur sens à leur date.
     */
    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            // Signé : un compte à découvert se saisit aussi, et c'est même le
            // cas où la prévision est la plus utile.
            'amount' => ['required', 'numeric', 'between:-99999999,99999999'],

            // Un relevé futur serait refusé par le calcul de toute façon, qui
            // part d'aujourd'hui. Autant le dire à la saisie plutôt que de
            // laisser l'utilisateur devant un chiffre qui ne bouge pas.
            'balance_date' => ['required', 'date', 'before_or_equal:today'],

            'label' => ['nullable', 'string', 'max:255'],
        ]);

        BankBalance::create($donnees);

        return back()->with('success', __('app.bank_balance_saved'));
    }

    /**
     * Supprime un relevé saisi par erreur. La prévision retombe alors sur le
     * relevé précédent, ou redevient une variation s'il n'y en a plus.
     */
    public function destroy(BankBalance $bankBalance): RedirectResponse
    {
        // La portée globale de `BelongsToUser` filtre déjà la résolution du
        // modèle ; ce garde-fou rend l'intention explicite et survivrait à sa
        // disparition.
        abort_unless($bankBalance->belongsToAuthUser(), 403);

        $bankBalance->delete();

        return back()->with('success', __('app.bank_balance_deleted'));
    }
}
