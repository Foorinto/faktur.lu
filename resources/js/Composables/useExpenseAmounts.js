import { computed, ref, watch } from 'vue';

/**
 * Logique de saisie des montants d'une dépense.
 *
 * Extraite parce que les formulaires de création et de modification la
 * partagent intégralement : la dupliquer garantissait qu'une correction
 * n'atterrisse que dans l'un des deux.
 *
 * Trois règles y cohabitent :
 *  - le montant se saisit indifféremment en HT ou en TTC, l'autre se déduit ;
 *  - la grille de taux suit le pays du fournisseur, et bascule en saisie libre
 *    pour les pays dont la configuration ne contient pas de grille ;
 *  - le régime de TVA contraint le taux et la déductibilité.
 */
export function useExpenseAmounts(form, props) {
    const regimesWithoutVat = ['reverse_charge', 'exempt'];

    // --- Grille de taux du pays sélectionné -------------------------------

    const ratesForCountry = computed(
        () => props.vatRatesByCountry?.[form.supplier_country] ?? null,
    );

    const hasRateGrid = computed(
        () => Array.isArray(ratesForCountry.value) && ratesForCountry.value.length > 0,
    );

    const gridValues = computed(() =>
        hasRateGrid.value ? ratesForCountry.value.map((r) => Number(r.value)) : [],
    );

    const rateIsInGrid = (rate) => gridValues.value.includes(Number(rate));

    // « custom » ouvre la saisie libre. C'est le mode par défaut dès que le
    // pays n'a pas de grille configurée : proposer une grille approximative
    // pour la Slovaquie serait pire que de laisser recopier le taux de la
    // facture.
    const vatMode = ref(rateIsInGrid(form.vat_rate) ? String(Number(form.vat_rate)) : 'custom');

    watch(vatMode, (mode) => {
        if (mode !== 'custom') form.vat_rate = Number(mode);
    });

    // --- Réaction au changement de pays -----------------------------------

    watch(
        () => form.supplier_country,
        (country, previous) => {
            if (country === previous) return;

            // Le régime n'est ajusté que sur un changement délibéré de pays,
            // et reste modifiable : c'est une proposition, pas un verrou.
            if (country === props.homeCountry) {
                form.vat_regime = 'national';
            } else if (form.vat_regime === 'national') {
                form.vat_regime = 'foreign_vat';
            }

            const grid = props.vatRatesByCountry?.[country];

            if (!grid?.length) {
                vatMode.value = 'custom';
                return;
            }

            if (!rateIsInGrid(form.vat_rate)) {
                const fallback = grid.find((r) => r.default) ?? grid[0];
                vatMode.value = String(Number(fallback.value));
                form.vat_rate = Number(fallback.value);
            }
        },
    );

    // --- Conséquences du régime -------------------------------------------

    // Autoliquidation et exonération : la facture du fournisseur ne porte
    // aucune TVA. Le taux est ramené à zéro et verrouillé, sans quoi un taux
    // hérité de la saisie précédente inventerait une taxe jamais payée.
    const vatRateLocked = computed(() => regimesWithoutVat.includes(form.vat_regime));

    const showForeignVatNotice = computed(() => form.vat_regime === 'foreign_vat');

    const isReverseCharge = computed(() => form.vat_regime === 'reverse_charge');

    watch(
        () => form.vat_regime,
        (regime) => {
            if (regimesWithoutVat.includes(regime)) {
                form.vat_rate = 0;
                vatMode.value = rateIsInGrid(0) ? '0' : 'custom';
            }

            // En autoliquidation, l'acheteur déclare la TVA de SON pays, pas
            // celle du fournisseur : le taux proposé est donc le taux normal
            // national, indépendant du pays sélectionné plus haut.
            if (regime === 'reverse_charge') {
                form.reverse_charge_vat_rate ||= props.homeStandardRate ?? 17;
            }

            // La déductibilité suit le régime dans les deux sens. Ne la
            // relever qu'à la main laisserait une dépense luxembourgeoise
            // définitivement non déductible après un simple aller-retour par
            // un fournisseur étranger. La case reste décochable ensuite : tant
            // que le régime ne change plus, ce choix-là tient.
            if (regime === 'foreign_vat') {
                form.is_deductible = false;
            } else if (regime === 'national') {
                form.is_deductible = true;
            }
        },
    );

    // --- Conversion HT / TTC ----------------------------------------------

    const multiplier = computed(() => 1 + (parseFloat(form.vat_rate) || 0) / 100);

    const round2 = (value) => (Number.isFinite(value) ? value.toFixed(2) : '0.00');

    const isTtcMode = computed(() => form.amount_input_mode === 'ttc');

    /** Montant HT : saisi, ou déduit du TTC. */
    const amountHtDisplay = computed(() => {
        if (!isTtcMode.value) return form.amount_ht;
        if (!form.amount_ttc) return '';

        return round2(parseFloat(form.amount_ttc) / multiplier.value);
    });

    /** Montant TTC : saisi, ou déduit du HT. */
    const amountTtcDisplay = computed(() => {
        if (isTtcMode.value) return form.amount_ttc;
        if (!form.amount_ht) return '';

        return round2(parseFloat(form.amount_ht) * multiplier.value);
    });

    const calculatedVat = computed(() => {
        const ht = parseFloat(amountHtDisplay.value);
        const ttc = parseFloat(amountTtcDisplay.value);

        if (!Number.isFinite(ht) || !Number.isFinite(ttc)) return '0.00';

        return round2(ttc - ht);
    });

    /** TVA que l'acheteur se facture à lui-même, à déclarer des deux côtés. */
    const reverseChargeVat = computed(() => {
        if (!isReverseCharge.value) return '0.00';

        const ht = parseFloat(amountHtDisplay.value);
        if (!Number.isFinite(ht)) return '0.00';

        return round2((ht * (parseFloat(form.reverse_charge_vat_rate) || 0)) / 100);
    });

    // Basculer d'une unité à l'autre reporte le montant converti dans le
    // nouveau champ : l'utilisateur qui se trompe d'unité en cours de saisie
    // ne perd pas ce qu'il a tapé.
    watch(
        () => form.amount_input_mode,
        (mode, previous) => {
            if (mode === previous) return;

            if (mode === 'ttc') {
                const ht = parseFloat(form.amount_ht);
                form.amount_ttc = Number.isFinite(ht) ? round2(ht * multiplier.value) : '';
            } else {
                const ttc = parseFloat(form.amount_ttc);
                form.amount_ht = Number.isFinite(ttc) ? round2(ttc / multiplier.value) : '';
            }
        },
    );

    return {
        vatMode,
        ratesForCountry,
        hasRateGrid,
        vatRateLocked,
        showForeignVatNotice,
        isReverseCharge,
        reverseChargeVat,
        isTtcMode,
        amountHtDisplay,
        amountTtcDisplay,
        calculatedVat,
    };
}
