import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * Les deux raccourcis de saisie d'encaissement.
 *
 * Ils étaient rendus en texte souligné et se voyaient mal (retour de l'auteur,
 * 2026-08-31). Ce sont des actions : elles remplissent le champ montant
 * au-dessus, elles doivent se lire comme des boutons.
 *
 * ⚠️ Le raccourci d'acompte ne s'affiche que TANT QU'AUCUN versement n'existe.
 * Une fois l'acompte reçu, le proposer encore inviterait à le saisir deux fois.
 *
 * Ce test lit la source, comme MarketingLayout.footer.test.js : monter la page
 * exigerait Inertia, Ziggy et les traductions pour vérifier des classes CSS et
 * une condition d'affichage.
 */

const SOURCE = readFileSync(
    resolve(process.cwd(), "resources/js/Pages/Invoices/Show.vue"),
    "utf8",
);

/** Le bloc des deux raccourcis. */
const RACCOURCIS = (() => {
    const debut = SOURCE.indexOf('@click="solderLaFacture"');
    expect(debut, "raccourci « solder » introuvable").toBeGreaterThan(-1);

    return SOURCE.slice(debut - 400, SOURCE.indexOf("formEncaissement.errors.amount", debut));
})();

describe("raccourcis de saisie d'un encaissement", () => {
    it("présente « solder la facture » comme un bouton, pas comme un lien", () => {
        expect(RACCOURCIS).toContain("rounded-lg border border-primary-200 bg-primary-50");
        expect(RACCOURCIS).not.toContain("hover:underline");
    });

    it("distingue l'acompte du solde par la couleur", () => {
        // Deux actions différentes : régler le reste dû, ou saisir l'acompte
        // annoncé. Les rendre identiques ferait cliquer sur la mauvaise.
        expect(RACCOURCIS).toContain("border-amber-200 bg-amber-50");
    });

    it("n'affiche l'acompte que lorsqu'il est attendu", () => {
        expect(RACCOURCIS).toContain('v-if="acompteAttendu"');
    });

    it("annonce le montant de l'acompte dans le bouton", () => {
        // « Acompte prévu » sans le montant obligerait à ouvrir le devis pour
        // savoir ce qu'on s'apprête à saisir.
        expect(RACCOURCIS).toContain("formatCurrency(acompteAttendu)");
    });
});

describe("condition d'affichage du raccourci d'acompte", () => {
    const CALCUL = SOURCE.slice(
        SOURCE.indexOf("const acompteAttendu"),
        SOURCE.indexOf("const saisirLAcompte"),
    );

    it("se tait dès qu'un versement a été enregistré", () => {
        expect(CALCUL).toContain("paymentSummary.paid === 0");
    });

    it("se tait quand le devis n'annonçait aucun acompte", () => {
        expect(CALCUL).toContain("paymentSummary.deposit");
    });
});
