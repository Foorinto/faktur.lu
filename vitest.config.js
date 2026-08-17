import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';

/**
 * Tests de la couche JavaScript.
 *
 * Séparés de `vite.config.js` : le plugin Laravel y injecte un manifeste et une
 * configuration de serveur de développement dont un test n'a que faire, et qui
 * le feraient échouer hors du contexte d'un `artisan serve`.
 *
 * Le périmètre est volontairement étroit. Tout ce qui relève de l'affichage se
 * teste mieux à l'œil, et le couvrir ici reviendrait à figer du balisage. Ce
 * qu'on vise, c'est le calcul : conversions HT/TTC, taux de TVA, régimes — la
 * logique qui produit un montant, et dont une erreur passerait inaperçue.
 */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        // jsdom et non happy-dom : ce dernier laissait passer une balise
        // `script` à travers DOMPurify. Le test aurait alors constaté une
        // absence de nettoyage sans que le code soit en cause — et, écrit dans
        // l'autre sens, il aurait pu certifier un nettoyage inexistant.
        environment: 'jsdom',
        include: ['resources/js/**/*.test.js'],
        globals: true,
    },
});
