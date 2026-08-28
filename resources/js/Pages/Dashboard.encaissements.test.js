import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * La carte « Encaissements par moyen » et ses deux sorties.
 *
 * Elle ne se suffit pas à elle-même, et c'est voulu : un tableau de bord donne
 * un ordre de grandeur, pas un détail. Deux chemins en partent, et ils ne
 * mènent pas au même endroit.
 *
 *   - chaque MOYEN mène au listing des factures filtré sur lui ;
 *   - « Voir tout » mène au LIVRE DE RECETTES, où la période se choisit
 *     librement et où la ventilation se détaille.
 *
 * Les confondre est l'erreur facile : le listing ne sait pas ventiler, et le
 * livre de recettes ne montre pas les factures une à une.
 *
 * Ce test lit la source, comme MarketingLayout.footer.test.js : monter le
 * tableau de bord exigerait Inertia, Ziggy, les traductions et un abonnement
 * pour vérifier deux noms de route.
 */

const SOURCE = readFileSync(
    resolve(process.cwd(), "resources/js/Pages/Dashboard.vue"),
    "utf8",
);

/** Le bloc de la carte, de son titre à sa fermeture. */
const CARTE = (() => {
    const debut = SOURCE.indexOf("dashboard_payments_by_method");
    expect(debut, "carte des encaissements introuvable").toBeGreaterThan(-1);

    return SOURCE.slice(debut, SOURCE.indexOf("<!-- Fourth Row", debut));
})();

describe("carte des encaissements par moyen", () => {
    it("porte un lien « Voir tout »", () => {
        expect(CARTE).toContain("t('view_all')");
    });

    it("envoie « Voir tout » vers le livre de recettes", () => {
        // Et non vers le listing : le listing ne sait pas ventiler.
        expect(CARTE).toContain("route('reports.revenue-book'");
    });

    it("cadre « Voir tout » sur l'année affichée", () => {
        // Sans les bornes, le livre s'ouvrirait sur l'année en cours alors que
        // la carte en montre une autre.
        expect(CARTE).toContain("${selectedYear}-01-01");
        expect(CARTE).toContain("${selectedYear}-12-31");
    });

    it("envoie chaque moyen vers le listing des factures filtré", () => {
        expect(CARTE).toContain("route('invoices.index', { payment_method:");
    });

    it("retombe sur « unknown » quand le moyen n'est pas renseigné", () => {
        // `null` dans l'URL ne filtrerait rien ; le listing attend « unknown »
        // pour retrouver les encaissements repris sans moyen.
        expect(CARTE).toContain("ligne.method ?? 'unknown'");
    });

    it("affiche le titre avec l'année, comme le récapitulatif TVA voisin", () => {
        expect(CARTE).toContain("{ year: selectedYear }");
    });
});
