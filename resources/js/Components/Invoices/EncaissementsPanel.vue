<script setup>
/**
 * Encaissements d'une facture (FEAT-114).
 *
 * Extrait de `Invoices/Show.vue` : le brouillon doit accueillir l'acompte, et
 * un brouillon s'ouvre dans `Invoices/Edit.vue`. Deux pages, 440 lignes de
 * gabarit et une douzaine de règles de saisie — un composant plutôt que deux
 * copies qui divergeront.
 *
 * Toute la validation vit sur le serveur : plafond au reste dû, statut dérivé,
 * marquage « connu avant l'émission ». Ce composant ne fait que présenter.
 */
import { Link, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { useTranslations } from "@/Composables/useTranslations";

const { t } = useTranslations();

const props = defineProps({
    invoice: { type: Object, required: true },
    payments: { type: Array, default: () => [] },
    paymentSummary: { type: Object, required: true },
    paymentMethods: { type: Array, default: () => [] },
});

/** Date du jour au fuseau local — `toISOString()` renverrait la veille au soir. */
const todayLocal = () => {
    const d = new Date();
    const decalage = d.getTimezoneOffset() * 60000;

    return new Date(d.getTime() - decalage).toISOString().slice(0, 10);
};

const formatCurrency = (montant, devise = "EUR") =>
    new Intl.NumberFormat("fr-FR", { style: "currency", currency: devise }).format(montant ?? 0);

const formatMontant = (montant) => formatCurrency(montant, props.invoice.currency ?? "EUR");

const formEncaissement = useForm({
    amount: null,
    paid_at: "",
    method: null,
    // Ce que la facture écrira. Laissé vide, le PDF déduit « Acompte versé
    // le … » ou « Règlement du … » de la date, mais c'est un texte que le
    // client lit : il doit rester au choix de l'utilisateur.
    label: "",
    reference: "",
});

const saisieOuverte = ref(false);

/**
 * Le montant reste VIDE à l'ouverture.
 *
 * Il était pré-rempli avec le reste dû : ouvrir la saisie et valider
 * enregistrait alors la totalité, et la facture passait à « payée » sans
 * qu'on l'ait voulu. Un acompte est un cas courant — il doit être le geste
 * naturel, pas celui qui demande de corriger un champ.
 *
 * Le reste dû reste proposé, mais par un bouton explicite.
 */
const ouvrirSaisie = () => {
    formEncaissement.amount = null;
    formEncaissement.paid_at = todayLocal();
    formEncaissement.method = null;
    formEncaissement.label = "";
    formEncaissement.reference = "";
    saisieOuverte.value = true;
};

/** Raccourci : solder la facture en un clic, quand c'est bien l'intention. */
const solderLaFacture = () => {
    formEncaissement.amount = props.paymentSummary.due;
};

/**
 * Raccourci d'acompte, quand le devis en annonçait un.
 *
 * Ne s'affiche qu'avant tout encaissement : une fois qu'un versement existe,
 * l'acompte a été reçu et le bouton n'a plus de sens.
 */
const acompteAttendu = computed(() => {
    const acompte = props.paymentSummary.deposit;

    return acompte && props.paymentSummary.paid === 0 ? acompte : null;
});

const saisirLAcompte = () => {
    formEncaissement.amount = acompteAttendu.value;
};

const enregistrerEncaissement = () => {
    formEncaissement.post(route("invoices.payments.store", props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            saisieOuverte.value = false;
        },
    });
};

/**
 * Correction d'un encaissement.
 *
 * Toujours possible, même sur une facture soldée : changer le moyen, la date
 * ou la référence ne touche ni au statut ni aux montants. C'est ce qui rattrape
 * le cas où l'on marque une facture payée depuis la liste, sans moyen.
 */
const encaissementEnEdition = ref(null);

const formCorrection = useForm({
    amount: null,
    paid_at: "",
    method: null,
    label: "",
    reference: "",
});

const ouvrirCorrection = (encaissement) => {
    encaissementEnEdition.value = encaissement.id;
    formCorrection.amount = encaissement.amount;
    formCorrection.paid_at = encaissement.paid_at;
    formCorrection.method = encaissement.method;
    formCorrection.label = encaissement.label || "";
    formCorrection.reference = encaissement.reference || "";
};

/**
 * Plafond de correction : le reste dû, augmenté du montant de l'encaissement
 * en cours d'édition — sinon repasser 500 € à 500 € sur une facture soldée
 * serait refusé par son propre montant.
 */
const plafondCorrection = computed(() => {
    const encaissement = props.payments.find(
        (p) => p.id === encaissementEnEdition.value,
    );

    return (
        Math.round(
            (props.paymentSummary.due + (encaissement?.amount || 0)) * 100,
        ) / 100
    );
});

const enregistrerCorrection = (encaissement) => {
    formCorrection.patch(
        route("invoices.payments.update", [props.invoice.id, encaissement.id]),
        {
            preserveScroll: true,
            onSuccess: () => {
                encaissementEnEdition.value = null;
            },
        },
    );
};

const supprimerEncaissement = (encaissement) => {
    if (
        !confirm(
            t("payments_confirm_delete", {
                amount: formatMontant(encaissement.amount),
            }),
        )
    )
        return;

    router.delete(
        route("invoices.payments.destroy", [props.invoice.id, encaissement.id]),
        {
            preserveScroll: true,
        },
    );
};

</script>

<template>
    <!-- Encaissements (FEAT-114) -->
    <div
        v-if="
            invoice.status !== 'cancelled' && invoice.type !== 'credit_note'
        "
        class="rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50"
    >
        <div
            class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"
        >
            <h2
                class="text-lg font-medium text-slate-900 dark:text-white"
            >
                {{ t("payments_title") }}
            </h2>
            <button
                type="button"
                @click="ouvrirSaisie"
                class="rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-700"
            >
                {{ t("payments_add") }}
            </button>
        </div>

        <!-- Encaissé / reste dû : le calcul vient du serveur, qui
             connaît la règle du trop-perçu. -->
        <div class="grid grid-cols-2 gap-4 px-6 py-4 sm:grid-cols-3">
            <div>
                <p
                    class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400"
                >
                    {{ t("payments_total_due") }}
                </p>
                <p
                    class="mt-1 text-lg font-semibold text-slate-900 dark:text-white"
                >
                    {{ formatMontant(invoice.total_ttc) }}
                </p>
            </div>
            <div>
                <p
                    class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400"
                >
                    {{ t("payments_received") }}
                </p>
                <p
                    class="mt-1 text-lg font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    {{ formatMontant(paymentSummary.paid) }}
                </p>
            </div>
            <div>
                <p
                    class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400"
                >
                    {{ t("payments_remaining") }}
                </p>
                <p
                    class="mt-1 text-lg font-semibold"
                    :class="
                        paymentSummary.due > 0
                            ? 'text-amber-600 dark:text-amber-400'
                            : 'text-slate-400'
                    "
                >
                    {{ formatMontant(paymentSummary.due) }}
                </p>
            </div>
        </div>

        <!-- Saisie -->
        <form
            v-if="saisieOuverte"
            @submit.prevent="enregistrerEncaissement"
            class="border-t border-gray-200 px-6 py-4 dark:border-gray-700"
        >
            <div class="grid gap-4 sm:grid-cols-4">
                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >{{ t("payments_amount") }}</label
                    >
                    <input
                        v-model="formEncaissement.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        :max="paymentSummary.due"
                        :placeholder="formatMontant(paymentSummary.due)"
                        required
                        class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    />
                    <div class="mt-2 flex flex-wrap gap-2">
                        <SecondaryButton size="sm" @click="solderLaFacture">
                            <svg class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ t("payments_settle_shortcut") }}
                        </SecondaryButton>
                        <!--
                            Acompte annoncé sur le devis : proposé tant
                            qu'aucun versement n'a été enregistré.
                        -->
                        <SecondaryButton
                            v-if="acompteAttendu"
                            size="sm"
                            @click="saisirLAcompte"
                        >
                            <svg class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ t("payments_deposit_shortcut", { amount: formatCurrency(acompteAttendu) }) }}
                        </SecondaryButton>
                    </div>
                    <p
                        v-if="formEncaissement.errors.amount"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ formEncaissement.errors.amount }}
                    </p>
                </div>
                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >{{ t("payments_date") }}</label
                    >
                    <input
                        v-model="formEncaissement.paid_at"
                        type="date"
                        required
                        class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    />
                    <p
                        v-if="formEncaissement.errors.paid_at"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ formEncaissement.errors.paid_at }}
                    </p>
                </div>
                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >{{ t("payments_method") }}</label
                    >
                    <select
                        v-model="formEncaissement.method"
                        class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        <option :value="null">
                            {{ t("payments_method_unknown") }}
                        </option>
                        <option
                            v-for="m in paymentMethods"
                            :key="m.value"
                            :value="m.value"
                        >
                            {{ m.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >{{ t("payments_label") }}</label
                    >
                    <input
                        v-model="formEncaissement.label"
                        type="text"
                        maxlength="100"
                        :placeholder="t('payments_label_placeholder')"
                        class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    />
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t("payments_label_help") }}
                    </p>
                </div>
                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >{{ t("payments_reference") }}</label
                    >
                    <input
                        v-model="formEncaissement.reference"
                        type="text"
                        maxlength="255"
                        class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    />
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button
                    type="submit"
                    :disabled="formEncaissement.processing"
                    class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50"
                >
                    {{ t("save") }}
                </button>
                <button
                    type="button"
                    @click="saisieOuverte = false"
                    class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-gray-600 dark:text-slate-300"
                >
                    {{ t("cancel") }}
                </button>
            </div>
        </form>

        <!-- Liste -->
        <div
            v-if="payments.length"
            class="border-t border-gray-200 dark:border-gray-700"
        >
            <table
                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
            >
                <tbody
                    class="divide-y divide-gray-100 dark:divide-gray-800"
                >
                    <tr v-for="p in payments" :key="p.id">
                        <template v-if="encaissementEnEdition === p.id">
                            <td colspan="5" class="px-6 py-4">
                                <form
                                    @submit.prevent="
                                        enregistrerCorrection(p)
                                    "
                                    class="grid items-end gap-3 sm:grid-cols-5"
                                >
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-slate-600 dark:text-slate-400"
                                            >{{
                                                t("payments_amount")
                                            }}</label
                                        >
                                        <input
                                            v-model="
                                                formCorrection.amount
                                            "
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            :max="plafondCorrection"
                                            required
                                            class="mt-1 w-full rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        />
                                        <p
                                            v-if="
                                                formCorrection.errors
                                                    .amount
                                            "
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{
                                                formCorrection.errors
                                                    .amount
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-slate-600 dark:text-slate-400"
                                            >{{
                                                t("payments_date")
                                            }}</label
                                        >
                                        <input
                                            v-model="
                                                formCorrection.paid_at
                                            "
                                            type="date"
                                            required
                                            class="mt-1 w-full rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-slate-600 dark:text-slate-400"
                                            >{{
                                                t("payments_method")
                                            }}</label
                                        >
                                        <select
                                            v-model="
                                                formCorrection.method
                                            "
                                            class="mt-1 w-full rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        >
                                            <option :value="null">
                                                {{
                                                    t(
                                                        "payments_method_unknown",
                                                    )
                                                }}
                                            </option>
                                            <option
                                                v-for="m in paymentMethods"
                                                :key="m.value"
                                                :value="m.value"
                                            >
                                                {{ m.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-slate-600 dark:text-slate-400"
                                            >{{ t("payments_label") }}</label
                                        >
                                        <input
                                            v-model="formCorrection.label"
                                            type="text"
                                            maxlength="100"
                                            :placeholder="t('payments_label_placeholder')"
                                            class="mt-1 w-full rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-slate-600 dark:text-slate-400"
                                            >{{
                                                t("payments_reference")
                                            }}</label
                                        >
                                        <input
                                            v-model="
                                                formCorrection.reference
                                            "
                                            type="text"
                                            maxlength="255"
                                            class="mt-1 w-full rounded-xl border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        />
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            type="submit"
                                            :disabled="
                                                formCorrection.processing
                                            "
                                            class="rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50"
                                        >
                                            {{ t("save") }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="
                                                encaissementEnEdition =
                                                    null
                                            "
                                            class="rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:text-slate-300"
                                        >
                                            {{ t("cancel") }}
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </template>
                        <template v-else>
                            <td
                                class="px-6 py-3 text-sm text-slate-600 dark:text-slate-400"
                            >
                                {{ p.paid_at }}
                            </td>
                            <td class="px-3 py-3 text-sm">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs"
                                    :class="
                                        p.method
                                            ? 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-200'
                                            : 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                                    "
                                    >{{ p.method_label }}</span
                                >
                            </td>
                            <td
                                class="px-3 py-3 text-sm text-slate-500 dark:text-slate-400"
                            >
                                {{ p.reference || "-" }}
                            </td>
                            <td
                                class="px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                {{ formatMontant(p.amount) }}
                            </td>
                            <td class="px-6 py-3 text-right">
                                <button
                                    type="button"
                                    @click="ouvrirCorrection(p)"
                                    class="mr-1 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-gray-700"
                                    :title="t('payments_edit')"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z"
                                        />
                                    </svg>
                                    <span class="sr-only">{{
                                        t("payments_edit")
                                    }}</span>
                                </button>
                                <button
                                    type="button"
                                    @click="supprimerEncaissement(p)"
                                    class="rounded-lg p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
                                    :title="t('payments_delete')"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span class="sr-only">{{
                                        t("payments_delete")
                                    }}</span>
                                </button>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Une facture soldée verrouille ses encaissements : le
             statut « payée » est terminal côté modèle. -->
        <p
            v-if="paymentSummary.locked"
            class="border-t border-gray-200 px-6 py-3 text-xs text-slate-500 dark:border-gray-700 dark:text-slate-400"
        >
            {{ t("payments_locked_hint") }}
        </p>
    </div>
</template>
