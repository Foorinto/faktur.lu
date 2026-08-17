import { describe, expect, it } from 'vitest';
import { nextTick, reactive } from 'vue';
import { useExpenseAmounts } from './useExpenseAmounts';

/**
 * Saisie des montants d'une dépense.
 *
 * Ce module décide de ce que l'utilisateur voit ET de ce qui part en base : le
 * montant HT, la TVA, la déductibilité, le taux d'autoliquidation. Une erreur
 * ici ne casse rien de visible — elle produit un chiffre faux, qui se retrouve
 * dans une déclaration de TVA.
 */

const GRILLES = {
    LU: [
        { value: 17, default: true },
        { value: 14 },
        { value: 8 },
        { value: 3 },
        { value: 0 },
    ],
    // Le 0 % figure dans les deux grilles : c'est ce qui permet de vérifier
    // qu'un taux commun survit à un changement de pays.
    DE: [{ value: 19, default: true }, { value: 7 }, { value: 0 }],
    // La Slovaquie n'a pas de grille : le champ doit basculer en saisie libre
    // plutôt que de proposer des taux inventés.
    SK: [],
};

/** Formulaire minimal, dans l'état où le contrôleur le livre. */
const formulaire = (surcharges = {}) => reactive({
    supplier_country: 'LU',
    vat_regime: 'national',
    vat_rate: 17,
    amount_input_mode: 'ht',
    amount_ht: '',
    amount_ttc: '',
    reverse_charge_vat_rate: null,
    is_deductible: true,
    ...surcharges,
});

const contexte = (surcharges = {}) => ({
    vatRatesByCountry: GRILLES,
    homeCountry: 'LU',
    homeStandardRate: 17,
    ...surcharges,
});

describe('conversion HT / TTC', () => {
    it('déduit le TTC du HT saisi', () => {
        const form = formulaire({ amount_ht: '100' });
        const { amountTtcDisplay, calculatedVat } = useExpenseAmounts(form, contexte());

        expect(amountTtcDisplay.value).toBe('117.00');
        expect(calculatedVat.value).toBe('17.00');
    });

    it('déduit le HT du TTC saisi', () => {
        const form = formulaire({ amount_input_mode: 'ttc', amount_ttc: '117' });
        const { amountHtDisplay, calculatedVat } = useExpenseAmounts(form, contexte());

        expect(amountHtDisplay.value).toBe('100.00');
        expect(calculatedVat.value).toBe('17.00');
    });

    /**
     * Le cas qui a motivé la saisie TTC : un ticket de caisse ne porte que le
     * total. 21,40 € à 17 % ne tombent pas juste — c'est précisément là qu'une
     * erreur d'arrondi se loge.
     */
    it('arrondit au centime sans perdre le total', () => {
        const form = formulaire({ amount_input_mode: 'ttc', amount_ttc: '21.40' });
        const { amountHtDisplay, calculatedVat } = useExpenseAmounts(form, contexte());

        expect(amountHtDisplay.value).toBe('18.29');
        expect(calculatedVat.value).toBe('3.11');

        // Et la somme doit refaire le ticket, au centime près.
        const total = Number(amountHtDisplay.value) + Number(calculatedVat.value);
        expect(total.toFixed(2)).toBe('21.40');
    });

    it('ne montre rien tant que rien n’est saisi', () => {
        const { amountTtcDisplay, calculatedVat } = useExpenseAmounts(formulaire(), contexte());

        expect(amountTtcDisplay.value).toBe('');
        expect(calculatedVat.value).toBe('0.00');
    });

    it('traite un taux à 0 % sans inventer de TVA', () => {
        const form = formulaire({ vat_rate: 0, amount_ht: '250' });
        const { amountTtcDisplay, calculatedVat } = useExpenseAmounts(form, contexte());

        expect(amountTtcDisplay.value).toBe('250.00');
        expect(calculatedVat.value).toBe('0.00');
    });

    /**
     * Se tromper d'unité en cours de saisie est fréquent. Le montant déjà tapé
     * doit être reporté converti, non effacé : le perdre pousse à ressaisir, et
     * une ressaisie est une occasion de se tromper.
     */
    it('reporte le montant converti quand on change d’unité', async () => {
        const form = formulaire({ amount_ht: '100' });
        useExpenseAmounts(form, contexte());

        form.amount_input_mode = 'ttc';
        await nextTick();

        expect(form.amount_ttc).toBe('117.00');
    });
});

describe('régime de TVA', () => {
    /**
     * Autoliquidation et exonération : la facture du fournisseur ne porte
     * aucune TVA. Un taux hérité de la saisie précédente inventerait une taxe
     * jamais payée, et la rendrait déductible.
     */
    it.each(['reverse_charge', 'exempt'])('remet le taux à zéro en %s', async (regime) => {
        const form = formulaire({ vat_rate: 17, amount_ht: '100' });
        const { vatRateLocked, calculatedVat } = useExpenseAmounts(form, contexte());

        form.vat_regime = regime;
        await nextTick();

        expect(form.vat_rate).toBe(0);
        expect(vatRateLocked.value).toBe(true);
        expect(calculatedVat.value).toBe('0.00');
    });

    /**
     * En autoliquidation, l'acheteur déclare la TVA de SON pays — pas celle du
     * fournisseur. Le taux proposé est donc le taux national, quel que soit le
     * pays sélectionné plus haut.
     */
    it('propose le taux national en autoliquidation', async () => {
        const form = formulaire({ supplier_country: 'DE', amount_ht: '1000' });
        const { reverseChargeVat } = useExpenseAmounts(form, contexte());

        form.vat_regime = 'reverse_charge';
        await nextTick();

        expect(form.reverse_charge_vat_rate).toBe(17);
        expect(reverseChargeVat.value).toBe('170.00');
    });

    it('ne calcule aucune autoliquidation hors de ce régime', () => {
        const form = formulaire({ amount_ht: '1000', reverse_charge_vat_rate: 17 });
        const { reverseChargeVat } = useExpenseAmounts(form, contexte());

        expect(reverseChargeVat.value).toBe('0.00');
    });

    /**
     * Une TVA étrangère ne se déduit pas au Luxembourg. Elle se récupère par la
     * procédure de remboursement, mais elle n'a rien à faire dans la TVA
     * déductible d'une déclaration luxembourgeoise.
     */
    it('rend non déductible une TVA étrangère', async () => {
        const form = formulaire();
        useExpenseAmounts(form, contexte());

        form.vat_regime = 'foreign_vat';
        await nextTick();

        expect(form.is_deductible).toBe(false);
    });

    /**
     * Et dans l'autre sens. Ne relever la déductibilité qu'à la main laisserait
     * une dépense luxembourgeoise définitivement non déductible après un simple
     * aller-retour par un fournisseur étranger.
     */
    it('rétablit la déductibilité au retour au régime national', async () => {
        const form = formulaire({ vat_regime: 'foreign_vat', is_deductible: false });
        useExpenseAmounts(form, contexte());

        form.vat_regime = 'national';
        await nextTick();

        expect(form.is_deductible).toBe(true);
    });
});

describe('grille de taux selon le pays', () => {
    it('bascule en saisie libre pour un pays sans grille', async () => {
        const form = formulaire();
        const { vatMode, hasRateGrid } = useExpenseAmounts(form, contexte());

        form.supplier_country = 'SK';
        await nextTick();

        expect(hasRateGrid.value).toBe(false);
        expect(vatMode.value).toBe('custom');
    });

    it('retombe sur le taux normal du pays choisi', async () => {
        const form = formulaire({ vat_rate: 17 });
        const { vatMode } = useExpenseAmounts(form, contexte());

        form.supplier_country = 'DE';
        await nextTick();

        // 17 n'existe pas en Allemagne : on propose le taux normal, 19.
        expect(form.vat_rate).toBe(19);
        expect(vatMode.value).toBe('19');
    });

    /**
     * Un taux commun aux deux pays ne doit pas être écrasé : l'utilisateur qui
     * a délibérément choisi 0 % le garde en changeant de pays.
     */
    it('conserve un taux qui existe dans la grille du nouveau pays', async () => {
        const form = formulaire({ vat_rate: 0 });
        useExpenseAmounts(form, contexte());

        form.supplier_country = 'DE';
        await nextTick();

        expect(form.vat_rate).toBe(0);
    });

    it('bascule le régime en TVA étrangère hors du pays d’établissement', async () => {
        const form = formulaire();
        useExpenseAmounts(form, contexte());

        form.supplier_country = 'DE';
        await nextTick();

        expect(form.vat_regime).toBe('foreign_vat');
    });

    it('revient au régime national au retour au Luxembourg', async () => {
        const form = formulaire({ supplier_country: 'DE', vat_regime: 'foreign_vat' });
        useExpenseAmounts(form, contexte());

        form.supplier_country = 'LU';
        await nextTick();

        expect(form.vat_regime).toBe('national');
    });
});
