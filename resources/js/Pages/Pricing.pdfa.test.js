import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * L'archivage PDF/A est une obligation légale : depuis FEAT-126 il est
 * automatique pour tous les plans. Les tableaux comparatifs des tarifs ne
 * doivent plus le présenter comme un avantage Pro. On lit la ligne
 * « pdfa_archive » des deux pages : trois coches, aucune croix.
 */
const ligne = (fichier) => {
    const source = readFileSync(resolve(process.cwd(), fichier), "utf8");
    const i = source.indexOf("rows.pdfa_archive");
    expect(i).toBeGreaterThan(0);
    const debut = source.lastIndexOf("<tr>", i);
    const fin = source.indexOf("</tr>", i);
    return source.slice(debut, fin);
};

describe("tarifs : archivage PDF/A pour tous", () => {
    for (const fichier of ["resources/js/Pages/Pricing.vue", "resources/js/Pages/Welcome.vue"]) {
        it(`${fichier} coche la ligne dans toutes les colonnes`, () => {
            const bloc = ligne(fichier);
            expect(bloc).not.toMatch(/M6 18L18 6M6 6l12 12/);
            expect((bloc.match(/M5 13l4 4L19 7/g) ?? []).length).toBe(3);
        });
    }
});
