import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createApp, nextTick } from 'vue';

/**
 * Choix d'un article sur une ligne de facture, en deux temps pour une famille
 * (FEAT-120) : la famille d'abord, puis la déclinaison.
 *
 * Le composant se monte pour de vrai : le défaut signalé par Didier le
 * 25/09/2026 (« en cliquant il ne me propose pas la liste avec les nuances »)
 * n'apparaît que dans l'enchaînement des gestionnaires d'un vrai clic, qu'une
 * lecture du source ne peut pas rejouer.
 */
vi.mock('@/Composables/useTranslations', () => ({ useTranslations: () => ({ t: (cle) => cle }) }));

import ProductAutocomplete from './ProductAutocomplete.vue';

const famille = { id: 64, designation: 'GL40', reference: 'GL40', variants_count: 3, unit_price_ht: '12.50', vat_rate: '17' };
const nuances = ['Rouge cerise', 'Bleu nuit', 'Vert sapin'].map((nuance, i) => ({
    id: 65 + i, parent_id: 64, designation: 'GL40', variant_label: nuance, reference: `GL40-0${i + 1}`,
    unit_price_ht: '12.50', vat_rate: '17', display_name: `GL40 - ${nuance}`,
}));
const autreArticle = { id: 70, designation: 'GL30', reference: 'GL30', variants_count: 0, unit_price_ht: '9.00', vat_rate: '17' };

const attendre = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

let conteneur;
let application;

const monter = () => {
    conteneur = document.createElement('div');
    document.body.appendChild(conteneur);
    application = createApp(ProductAutocomplete, { modelValue: '', inputId: 'titre' });
    application.mount(conteneur);
};

// Tape un terme et laisse passer l'anti-rebond de la recherche (250 ms).
const taper = async (texte) => {
    const champ = conteneur.querySelector('input');
    champ.value = texte;
    champ.dispatchEvent(new Event('input', { bubbles: true }));
    await attendre(320);
    await nextTick();
};

const ligneFamille = () => [...conteneur.querySelectorAll('ul li')].find((li) => li.textContent.includes('GL40') && li.textContent.includes('variants_count'));
const texteListe = () => (conteneur.querySelector('ul')?.textContent ?? '').replace(/\s+/g, ' ');

beforeEach(() => {
    vi.stubGlobal('route', (nom, params) => (nom === 'products.search' ? `/products/search?q=${params.q}` : `/products/${params}/variants`));
    vi.stubGlobal('fetch', vi.fn(async (url) => ({
        json: async () => {
            if (url.includes('/variants')) return { axis: 'Nuance', family: 'GL40', variants: nuances };
            return { products: url.includes('q=GL3') ? [autreArticle] : [famille] };
        },
    })));
});

afterEach(() => {
    application?.unmount();
    conteneur?.remove();
    vi.unstubAllGlobals();
});

describe('ProductAutocomplete : famille puis déclinaison', () => {
    it("un vrai clic sur une famille montre ses déclinaisons au lieu de fermer la liste", async () => {
        // Sur un vrai clic, le navigateur laisse Vue redessiner entre le
        // gestionnaire de la ligne et celui du document : la ligne cliquée est
        // déjà retirée de la page quand le composant décide si le clic était
        // « dehors ». On rejoue cet ordre en détachant la ligne juste après son
        // gestionnaire, avant celui du composant (enregistré au montage).
        const detacherLaLigne = (evenement) => evenement.target.closest('li')?.remove();
        document.addEventListener('mousedown', detacherLaLigne);
        monter();
        await taper('GL40');
        expect(ligneFamille()).toBeTruthy();

        ligneFamille().dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
        await attendre(20);
        await nextTick();
        document.removeEventListener('mousedown', detacherLaLigne);

        expect(conteneur.querySelector('ul')).not.toBeNull();
        expect(texteListe()).toContain('Rouge cerise');
        expect(texteListe()).toContain('Vert sapin');
        expect(texteListe()).toContain('Nuance');
    });

    it('reprendre la frappe quitte la famille ouverte et montre la nouvelle recherche', async () => {
        monter();
        await taper('GL40');
        ligneFamille().dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
        await attendre(20);
        await nextTick();
        expect(texteListe()).toContain('Rouge cerise');

        await taper('GL3');

        expect(texteListe()).toContain('GL30');
        expect(texteListe()).not.toContain('Rouge cerise');
    });

    it('un clic hors du composant ferme toujours la liste', async () => {
        monter();
        await taper('GL40');
        expect(conteneur.querySelector('ul')).not.toBeNull();

        document.body.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        await nextTick();

        expect(conteneur.querySelector('ul')).toBeNull();
    });
});
