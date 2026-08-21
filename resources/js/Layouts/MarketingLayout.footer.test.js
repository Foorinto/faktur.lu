import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * La grille du pied de page doit déclarer autant de colonnes qu'elle en remplit.
 *
 * Tailwind ne redistribue pas : une grille `md:grid-cols-5` qui reçoit six
 * colonnes fait simplement retomber la sixième sur une deuxième ligne, seule et
 * alignée à gauche. Rien n'échoue, rien ne s'affiche en rouge — la page est
 * juste de travers.
 *
 * C'est arrivé le 2026-08-21 en ajoutant la colonne « Par métier » : comme
 * cette colonne n'apparaît qu'en français, le pied de page restait correct
 * dans les quatre autres langues, et le défaut ne se voyait que sur `/fr`.
 *
 * Ce test lit le template. C'est grossier, mais monter le composant
 * exigerait Inertia, Ziggy et les traductions pour vérifier un nombre écrit
 * en clair dans une classe CSS.
 */

// Chemin depuis la racine du projet, et non `import.meta.url` : sous jsdom,
// vitest réécrit cette URL dans un schéma que `fileURLToPath` refuse.
const SOURCE = readFileSync(
    resolve(process.cwd(), "resources/js/Layouts/MarketingLayout.vue"),
    "utf8",
);

/**
 * Le bloc `<div class="grid …">` du pied de page, balises équilibrées.
 *
 * Lève une erreur ordinaire plutôt qu'un `expect` : appelée à l'évaluation du
 * `describe`, une assertion échouée ferait échouer la *collecte* du fichier,
 * et vitest annoncerait « no tests » sans nommer la cause.
 */
function grilleDuPied() {
    const debut = SOURCE.indexOf('class="grid gap-8 mb-8');

    if (debut === -1) {
        throw new Error(
            "Grille du pied de page introuvable : le bloc a été renommé.",
        );
    }

    const ouverture = SOURCE.lastIndexOf("<div", debut);
    let profondeur = 0;
    const jetons = /<div\b[^>]*?(\/?)>|<\/div>/g;
    jetons.lastIndex = ouverture;

    const enfants = [];
    let depart = null;
    let m;

    while ((m = jetons.exec(SOURCE)) !== null) {
        if (m[0] === "</div>") {
            profondeur -= 1;
            if (profondeur === 0) break;
            if (profondeur === 1 && depart !== null) {
                enfants.push(SOURCE.slice(depart, m.index + m[0].length));
                depart = null;
            }
        } else if (!m[1]) {
            if (profondeur === 1 && depart === null) depart = m.index;
            profondeur += 1;
        }
    }

    return { entete: SOURCE.slice(ouverture, debut + 200), enfants };
}

/** Somme des largeurs, une colonne par défaut, deux si `md:col-span-2`. */
function largeur(enfants) {
    return enfants.reduce(
        (total, enfant) => total + (enfant.includes("md:col-span-2") ? 2 : 1),
        0,
    );
}

describe("grille du pied de page", () => {
    const { entete, enfants } = grilleDuPied();

    it("remplit exactement ses cinq colonnes", () => {
        expect(entete).toContain("md:grid-cols-5");
        expect(largeur(enfants)).toBe(5);
    });

    it("ne conditionne plus aucune colonne à la langue", () => {
        // Les pages sectorielles existent dans les cinq langues depuis que les
        // URL sont traduites. Une colonne masquée selon la langue déséquilibrerait
        // à nouveau la grille, et seulement dans certaines langues — le défaut
        // le plus difficile à voir.
        for (const enfant of enfants) {
            expect(enfant).not.toMatch(/v-if="[^"]*[Ll]ocale/);
            expect(enfant).not.toContain("afficherMetiers");
        }
    });

    it("construit les liens métier avec le slug de la langue courante", () => {
        // Une URL française codée en dur renverrait un lecteur allemand vers
        // une adresse qui répond 404 dans sa langue.
        expect(SOURCE).not.toMatch(/\/fr\/logiciel-facturation-\$\{/);
        expect(SOURCE).toMatch(/localizedRoute\(\s*["']sectors\.show["']/);
    });

    it("garde les cinq liens légaux, hors de la grille", () => {
        const nav = SOURCE.match(
            /<nav[^>]*landing\.footer\.legal[^>]*>[\s\S]*?<\/nav>/,
        );

        expect(nav, "La ligne des liens légaux a disparu.").not.toBeNull();

        for (const page of ["mentions", "privacy", "terms", "cookies", "dpa"]) {
            expect(nav[0]).toContain(`legal.${page}`);
        }

        // Le DPA est rendu par une vue Blade : un Link Inertia attendrait une
        // réponse Inertia, recevrait du HTML et resterait sans effet au clic.
        expect(nav[0]).toMatch(
            /<a\s+:href="localizedRoute\(["']legal\.dpa["']\)"/,
        );

        expect(enfants.some((e) => e.includes("legal.terms"))).toBe(false);
    });
});
