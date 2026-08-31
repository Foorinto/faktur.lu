import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * Les deux raccourcis de saisie d'encaissement.
 *
 * Ils ont été rendus deux fois : d'abord en texte souligné (invisible), puis
 * en pastilles colorées — qui « ressemblaient encore à des tags informatifs
 * plutôt qu'à des boutons » (retour de l'auteur, 2026-08-31). Ce sont des
 * actions : elles remplissent le champ montant au-dessus.
 *
 * D'où `SecondaryButton`, le bouton d'action du dépôt — fond blanc, bordure
 * franche — plutôt qu'un style inventé sur place.
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
    it("réutilise le bouton du dépôt plutôt qu'un style local", () => {
        expect(RACCOURCIS).toContain("<SecondaryButton");
        expect(RACCOURCIS).not.toContain("hover:underline");
    });

    it("porte une icône sur CHACUN des deux boutons", () => {
        // Un « + » : on ajoute un montant dans le champ, on ne lit pas une
        // information. C'est ce qui distinguait mal les pastilles précédentes.
        //
        // Le compte, et pas la simple présence : vérifier « il y a une icône »
        // passait encore quand l'un des deux boutons perdait la sienne.
        const icones = RACCOURCIS.split("M12 4.5v15m7.5-7.5h-15").length - 1;

        expect(icones).toBe(2);
    });

    it("n'affiche l'acompte que lorsqu'il est attendu", () => {
        expect(RACCOURCIS).toContain('v-if="acompteAttendu"');
    });

    it("importe le composant qu'il utilise", () => {
        // Un composant non importé rend une balise inconnue : Vue n'échoue pas,
        // il n'affiche simplement rien — et le bouton disparaîtrait en silence.
        expect(SOURCE).toContain('import SecondaryButton from "@/Components/SecondaryButton.vue"');
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
