import { describe, expect, it, vi } from "vitest";

/**
 * L'identité de la marque vient du serveur, pas du bundle.
 *
 * Un changement de dénomination est engagé (accord amiable du 2026-09-01).
 * `import.meta.env.VITE_APP_NAME` est figé au moment de la compilation : s'y
 * fier obligerait à recompiler pour changer un nom. Une propriété partagée par
 * Inertia suit la configuration du serveur.
 *
 * ⚠️ Le repli en dur reste "faktur.lu". Il ne sert que si la propriété manque,
 * cas qui ne devrait pas exister, et il vaut mieux afficher l'ancien nom qu'une
 * valeur vide au milieu d'une phrase.
 */

const propsPartagees = vi.hoisted(() => ({ valeur: {} }));

vi.mock("@inertiajs/vue3", () => ({
    usePage: () => ({ props: propsPartagees.valeur }),
}));

const { useMarque } = await import("./useMarque");

describe("useMarque", () => {
    it("suit ce que le serveur envoie", () => {
        propsPartagees.valeur = {
            marque: { nom: "kolux.lu", domaine: "kolux.lu", url: "https://kolux.lu" },
        };

        const { nom, domaine, url } = useMarque();

        expect(nom.value).toBe("kolux.lu");
        expect(domaine.value).toBe("kolux.lu");
        expect(url.value).toBe("https://kolux.lu");
    });

    it("retombe sur le nom actuel quand la propriété manque", () => {
        propsPartagees.valeur = {};

        const { nom, url } = useMarque();

        expect(nom.value).toBe("faktur.lu");
        expect(url.value).toBe("https://faktur.lu");
    });

    it("ne lit pas la variable figée dans le bundle", async () => {
        // Le jour du changement, une variable d'environnement doit suffire.
        // VITE_APP_NAME imposerait une recompilation.
        const { readFileSync } = await import("node:fs");
        const { resolve } = await import("node:path");
        const source = readFileSync(
            resolve(process.cwd(), "resources/js/Composables/useMarque.js"),
            "utf8",
        );

        // Commentaires retirés : ils citent VITE_APP_NAME pour expliquer
        // pourquoi on ne s'en sert pas. C'est le CODE qu'on vérifie.
        const code = source.replace(/\/\*[\s\S]*?\*\//g, "");

        expect(code).not.toContain("import.meta.env");
        expect(code).toContain("usePage");
    });
});
