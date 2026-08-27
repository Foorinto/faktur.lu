import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

/**
 * Le bloc encaissements de la page facture (FEAT-114).
 *
 * Ces contrôles portent sur le gabarit et non sur un rendu : monter cette page
 * exigerait Inertia, Ziggy, les traductions et une facture complète, pour
 * vérifier la présence de trois champs.
 *
 * Ils existent parce que deux corrections ont été perdues sans bruit : prettier
 * reformate le fichier, les ancres de remplacement cessent de correspondre, et
 * le back-end accepte un montant que l'interface n'affiche plus. Le serveur
 * était juste, la vue était restée en arrière, et aucun test ne l'a vu.
 */

const SOURCE = readFileSync(
    resolve(process.cwd(), 'resources/js/Pages/Invoices/Show.vue'),
    'utf8',
);

describe('bloc encaissements', () => {
    it('permet de saisir un encaissement', () => {
        // Une liaison v-model, pas une simple mention : le nom de la variable
        // apparaît aussi dans les messages d'erreur, et sa présence ne prouve
        // donc pas que le champ existe.
        for (const champ of ['amount', 'paid_at', 'method', 'reference']) {
            expect(SOURCE).toMatch(
                new RegExp(`v-model="\\s*formEncaissement\\.${champ}\\s*"`),
            );
        }
    });

    /**
     * Le montant doit être corrigeable, y compris sur une facture soldée :
     * c'est le cas du paiement en plusieurs fois mal saisi.
     */
    it('permet de corriger le montant, la date, le moyen et la référence', () => {
        for (const champ of ['amount', 'paid_at', 'method', 'reference']) {
            expect(SOURCE).toMatch(
                new RegExp(`v-model="\\s*formCorrection\\.${champ}\\s*"`),
            );
        }
    });

    /**
     * Le garde d'immuabilité ne fait plus de « payée » un statut terminal :
     * aucun bouton ne doit être masqué au motif que la facture est soldée.
     */
    it('ne masque plus rien sur une facture soldée', () => {
        expect(SOURCE).not.toContain('v-if="!paymentSummary.locked"');
    });

    /**
     * Le montant est plafonné au reste dû, des deux côtés.
     *
     * Le serveur refuse déjà, mais un champ sans `max` laisse l'utilisateur
     * saisir puis se faire rejeter — on l'en empêche avant.
     */
    it('plafonne le montant au reste dû', () => {
        expect(SOURCE).toContain(':max="paymentSummary.due"');
        expect(SOURCE).toContain(':max="plafondCorrection"');
    });

    it('affiche l’encaissé et le reste dû', () => {
        expect(SOURCE).toContain('paymentSummary.paid');
        expect(SOURCE).toContain('paymentSummary.due');
    });

    it('propose la correction et la suppression de chaque encaissement', () => {
        expect(SOURCE).toContain('ouvrirCorrection(p)');
        expect(SOURCE).toContain('supprimerEncaissement(p)');
    });
});
