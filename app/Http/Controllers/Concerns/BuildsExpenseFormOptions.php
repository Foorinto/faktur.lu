<?php

namespace App\Http\Controllers\Concerns;

use App\Models\BusinessSettings;
use App\Models\Expense;

/**
 * Les listes déroulantes communes à toute saisie d'achat.
 *
 * Une dépense et une charge fixe décrivent le même achat : mêmes catégories,
 * mêmes taux, mêmes régimes de TVA, mêmes moyens de paiement. Les construire
 * à un seul endroit garantit qu'un régime ajouté demain apparaîtra dans les
 * deux écrans, et pas dans un seul.
 */
trait BuildsExpenseFormOptions
{
    /**
     * @return array<string, mixed>
     */
    protected function expenseFormOptions(): array
    {
        return [
            'categories' => $this->getCategoriesForSelect(),
            'vatRates' => $this->getVatRates(),
            'vatRatesByCountry' => $this->getVatRatesByCountry(),
            'vatRegimes' => $this->getVatRegimesForSelect(),
            'countries' => Expense::getSupplierCountries(),
            'homeCountry' => BusinessSettings::getInstance()?->country_code ?? 'LU',
            // Le taux d'autoliquidation est celui du pays de l'entreprise, pas
            // celui du fournisseur : c'est l'acheteur qui déclare.
            'homeStandardRate' => Expense::defaultReverseChargeRate(),
            'paymentMethods' => $this->getPaymentMethodsForSelect(),
            // Les fournisseurs déjà saisis, les plus fréquents d'abord. Une
            // simple liste de suggestions : le champ reste libre, on ne force
            // personne à choisir dans un catalogue qui n'existe pas encore.
            'providers' => $this->fournisseursDejaSaisis(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function fournisseursDejaSaisis(): array
    {
        return Expense::query()
            ->whereNotNull('provider_name')
            ->where('provider_name', '!=', '')
            ->select('provider_name')
            ->groupBy('provider_name')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(100)
            ->pluck('provider_name')
            ->all();
    }

    protected function getCategoriesForSelect(): array
    {
        return collect(Expense::getCategories())->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->toArray();
    }

    protected function getVatRates(): array
    {
        $settings = BusinessSettings::getInstance();

        // Get country-specific VAT rates
        $countryRates = $settings?->getVatRates() ?? config('countries.LU.vat_rates', []);

        return $this->normalizeRates($countryRates);
    }

    protected function getVatRatesByCountry(): array
    {
        $byCountry = [];

        foreach (config('countries', []) as $code => $country) {
            if (! empty($country['vat_rates'])) {
                $byCountry[$code] = $this->normalizeRates($country['vat_rates']);
            }
        }

        return $byCountry;
    }

    protected function getVatRegimesForSelect(): array
    {
        return collect(Expense::getVatRegimes())->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->toArray();
    }

    protected function getPaymentMethodsForSelect(): array
    {
        return collect(Expense::getPaymentMethods())->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->toArray();
    }

    protected function normalizeRates(array $rates): array
    {
        return collect($rates)->map(fn ($rate) => [
            'value' => $rate['value'],
            'label' => $rate['label'],
            'default' => $rate['default'] ?? false,
        ])->values()->toArray();
    }
}
