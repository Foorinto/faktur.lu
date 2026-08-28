import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * L'entrée « Comptabilité » doit ouvrir sur le livre de recettes.
 *
 * Elle ouvrait sur l'export FAIA : un fichier qu'on produit une fois par an, à
 * la demande d'un contrôleur, présenté comme la porte d'entrée d'une section
 * qu'on consulte toutes les semaines. Un client payant a cherché ses
 * encaissements dans « Facturation », ne les a jamais trouvés, et a écrit pour
 * demander une fonctionnalité qui existait déjà (2026-08-28).
 *
 * ⚠️ Cette destination n'est correcte que parce que la page est consultable
 * par TOUS les plans. Si elle repassait derrière `plan.feature`, un compte
 * gratuit cliquerait sur « Comptabilité » pour être renvoyé sur l'écran
 * d'abonnement : on aurait remplacé une page mal choisie par un mur. Le test
 * de bornage côté serveur (RevenueBookHistoryTest) garde l'autre moitié.
 *
 * Ce test lit la source, à la manière de MarketingLayout.footer.test.js :
 * monter AppLayout exigerait Inertia, Ziggy, les traductions et l'abonnement
 * pour vérifier deux noms de route écrits en clair.
 */

const SOURCE = readFileSync(
    resolve(process.cwd(), "resources/js/Layouts/AppLayout.vue"),
    "utf8",
);

/** L'entrée de menu « Comptabilité », de son nom à sa virgule finale. */
const ENTREE = (() => {
    const debut = SOURCE.indexOf("name: t('accounting')");
    expect(debut, "entrée « Comptabilité » introuvable dans la navigation").toBeGreaterThan(-1);

    return SOURCE.slice(debut, SOURCE.indexOf("}", debut));
})();

describe("entrée de menu « Comptabilité »", () => {
    it("ouvre sur le livre de recettes", () => {
        expect(ENTREE).toContain("reports.revenue-book");
    });

    it("n'ouvre plus sur l'export FAIA", () => {
        // Le FAIA se produit une fois par an, sur demande d'un contrôleur.
        expect(ENTREE).not.toContain("exports.audit.index");
    });

    it("continue de surligner toute la section", () => {
        // `routes` décide du surlignage : sans « exports », les pages d'export
        // n'allumeraient plus l'entrée de menu.
        expect(ENTREE).toContain("'reports'");
        expect(ENTREE).toContain("'exports'");
    });
});
