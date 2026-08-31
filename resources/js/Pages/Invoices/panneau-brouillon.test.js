import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * Le panneau d'encaissements doit être VISIBLE sur un brouillon.
 *
 * Un brouillon s'ouvre dans `Invoices/Edit.vue`, pas dans `Show.vue` — c'est
 * ce que j'avais manqué une première fois. Puis, en l'ajoutant, je l'ai glissé
 * dans la fenêtre de finalisation : il ne s'affichait qu'au moment de
 * finaliser, c'est-à-dire jamais pour qui cherche à saisir un acompte.
 * L'auteur a dû le signaler deux fois (2026-08-31).
 *
 * Ce test lit la source. Un composant présent dans un fichier ne prouve rien
 * s'il est enfermé derrière un `v-if` de fenêtre modale : c'est la POSITION
 * qui compte, et elle se vérifie ici.
 */

const SOURCE = readFileSync(
    resolve(process.cwd(), "resources/js/Pages/Invoices/Edit.vue"),
    "utf8",
);

const positionPanneau = SOURCE.indexOf("<EncaissementsPanel");

describe("panneau d'encaissements sur la page d'édition", () => {
    it("est présent", () => {
        expect(positionPanneau).toBeGreaterThan(-1);
    });

    it("reçoit ce qu'il attend", () => {
        const balise = SOURCE.slice(positionPanneau, SOURCE.indexOf("/>", positionPanneau));

        expect(balise).toContain(':invoice="invoice"');
        expect(balise).toContain(':payments="payments"');
        expect(balise).toContain(':payment-summary="paymentSummary"');
        expect(balise).toContain(':payment-methods="paymentMethods"');
    });

    it.each(["showPreviewModal", "showFinalizeModal"])(
        "n'est pas enfermé dans la fenêtre %s",
        (fenetre) => {
            const positionFenetre = SOURCE.indexOf(`v-if="${fenetre}"`);

            expect(positionFenetre, `fenêtre ${fenetre} introuvable`).toBeGreaterThan(-1);
            // Déclaré AVANT les fenêtres : il appartient à la page, pas à elles.
            expect(positionPanneau).toBeLessThan(positionFenetre);
        },
    );

    it("est importé", () => {
        expect(SOURCE).toContain(
            "import EncaissementsPanel from '@/Components/Invoices/EncaissementsPanel.vue'",
        );
    });
});
