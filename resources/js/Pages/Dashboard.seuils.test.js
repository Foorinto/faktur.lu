import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * Deux cartes du tableau de bord qui trompaient leur lecteur.
 *
 * Signalées par un client payant le 2026-08-31, captures à l'appui :
 *
 *   « Position actuelle : -4 € — je ne comprends pas d'où vient ce montant
 *     puisqu'à ce jour j'ai eu plus d'encaissements que de dépenses »
 *   « pourquoi il y a la box Seuil de franchise TVA alors que l'entreprise
 *     est assujettie ? »
 *
 * Les deux chiffres étaient justes et les deux cartes mensongères : la
 * prévision part de zéro et ignore le solde bancaire, et le seuil de franchise
 * n'a aucun sens pour qui l'a déjà franchi.
 */

const lire = (chemin) => readFileSync(resolve(process.cwd(), chemin), "utf8");

const DASHBOARD = lire("resources/js/Pages/Dashboard.vue");
const CASHFLOW = lire("resources/js/Components/Dashboard/CashflowChart.vue");

describe("carte du seuil de franchise TVA", () => {
    it("ne s'affiche que sous le régime de franchise", () => {
        const carte = DASHBOARD.slice(
            DASHBOARD.indexOf("<!-- VAT Franchise Threshold -->"),
            DASHBOARD.indexOf("vat_franchise_threshold"),
        );

        expect(carte).toContain('v-if="sousRegimeDeFranchise"');
    });

    it("lit le régime là où il est déjà connu", () => {
        // `franchiseAlert` porte l'information : la recalculer ailleurs
        // laisserait les deux réponses diverger.
        expect(DASHBOARD).toContain("props.franchiseAlert?.is_franchise_regime");
    });

    it("laisse le seuil de comptabilité simplifiée occuper la ligne entière", () => {
        // Une carte orpheline sur une demi-largeur se lit comme un manque.
        const voisine = DASHBOARD.slice(
            DASHBOARD.indexOf("<!-- Simplified Accounting Threshold -->"),
            DASHBOARD.indexOf("simplified_accounting_threshold"),
        );

        expect(voisine).toContain("sousRegimeDeFranchise ? '' : 'lg:col-span-2'");
    });
});

describe("prévision de trésorerie", () => {
    it("n'affiche plus de « position actuelle »", () => {
        // Elle valait toujours « moins une journée de dépenses moyennes » :
        // la prévision démarre à zéro, elle ne connaît pas le compte en banque.
        expect(CASHFLOW).not.toContain("t('cash_position')");
    });

    it("garde les horizons, qui répondent à une vraie question", () => {
        expect(CASHFLOW).toContain("t('days_30')");
        expect(CASHFLOW).toContain("t('days_60')");
        expect(CASHFLOW).toContain("t('days_90')");
    });

    it("dit qu'elle ignore le solde bancaire", () => {
        // Sans cette phrase, les montants se lisent comme une position réelle.
        expect(CASHFLOW).toContain("t('cashflow_excludes_bank_balance')");
    });
});
