<?php

namespace App\Services\Accounting;

use Illuminate\Support\Collection;

/**
 * Comptes de charges du plan comptable normalisé luxembourgeois.
 *
 * La liste est fixée par règlement grand-ducal et ne bouge qu'à chaque refonte
 * — une seule en dix ans. Elle est donc embarquée dans le dépôt
 * (`resources/data/pcn-class6.json`, généré par `php artisan pcn:build`)
 * plutôt qu'interrogée : il n'existe de toute façon aucune API.
 *
 * Seuls les comptes IMPUTABLES y figurent. Sur les 354 comptes de la classe 6,
 * 94 sont de simples titres : « 611 Loyers et charges locatives » n'accepte
 * aucune écriture, « 61112 Constructions / Bâtiments » si.
 */
class PcnAccountService
{
    /**
     * Correspondances entre le vocabulaire d'un utilisateur et un compte réel.
     *
     * Sans elles, la recherche ne sert à rien : personne ne tape « locations et
     * leasing opérationnel » en pensant « Loyer ». C'est la suggestion qui rend
     * le champ utilisable, la recherche n'étant que le recours.
     *
     * ⚠️ Chaque numéro cité ici doit exister dans le fichier généré ET y être
     * imputable — un test le vérifie compte par compte.
     *
     * @var array<string, string>
     */
    private const KEYWORD_MAP = [
        'loyer' => '61112',
        'location' => '61112',
        'bureau' => '61112',
        'charges locatives' => '6113',
        'copropriete' => '6113',
        'entretien' => '61228',
        'reparation' => '61228',
        'maintenance' => '61228',
        'nettoyage' => '61228',
        'assurance' => '6148',
        'responsabilite civile' => '6146',
        'banque' => '61333',
        'frais bancaires' => '61333',
        'comptable' => '61342',
        'fiduciaire' => '61342',
        'expert comptable' => '61342',
        'avocat' => '61341',
        'juridique' => '61341',
        'telephone' => '61532',
        'internet' => '61532',
        'telecom' => '61532',
        'poste' => '61531',
        'affranchissement' => '61531',
        'electricite' => '61845',
        'eau' => '61844',
        'gaz' => '61843',
        'chauffage' => '61842',
        'carburant' => '6036',
        'essence' => '6036',
        'vehicule' => '61223',
        'voiture' => '61223',
        'deplacement' => '61523',
        'mission' => '61523',
        'voyage' => '61523',
        'restaurant' => '61524',
        'repas' => '61524',
        'reception' => '61524',
        'publicite' => '61511',
        'marketing' => '61518',
        'fournitures' => '61851',
        'papeterie' => '61851',
        'petit equipement' => '61852',
        'logiciel' => '6413',
        'licence' => '6413',
        'informatique' => '6132',
        'hebergement' => '6132',
        'formation' => '6182',
        'cotisation' => '6187',
        'sous-traitance' => '6121',
    ];

    /** @var Collection<int, array{ref: string, parent: string, fr: string, de: string, en: string}>|null */
    private ?Collection $accounts = null;

    /**
     * @return Collection<int, array<string, string>>
     */
    public function all(): Collection
    {
        if ($this->accounts !== null) {
            return $this->accounts;
        }

        $path = resource_path('data/pcn-class6.json');

        if (! is_readable($path)) {
            return $this->accounts = collect();
        }

        return $this->accounts = collect(json_decode((string) file_get_contents($path), true) ?: []);
    }

    /**
     * Recherche sur le numéro ET sur le libellé.
     *
     * Taper « 611 » ou « loyer » doit mener au même endroit : l'un est le
     * réflexe du comptable, l'autre celui de l'entrepreneur.
     *
     * @return array<int, array<string, string>>
     */
    public function search(string $term, string $locale = 'fr', int $limit = 25): array
    {
        $term = trim($term);
        $needle = $this->normalize($term);

        $results = $this->all()
            ->map(fn (array $a) => $a + ['label' => $this->label($a, $locale)])
            ->filter(function (array $a) use ($term, $needle) {
                if ($needle === '') {
                    return true;
                }

                return str_starts_with($a['ref'], $term)
                    || str_contains($this->normalize($a['label']), $needle)
                    || str_contains($this->normalize($a['parent']), $needle);
            })
            // Une correspondance sur le numéro passe devant : celui qui tape des
            // chiffres sait ce qu'il cherche.
            ->sortBy(fn (array $a) => (str_starts_with($a['ref'], $term) ? '0' : '1').$a['ref'])
            ->take($limit)
            ->values();

        return $results->map(fn (array $a) => [
            'ref' => $a['ref'],
            'label' => $a['label'],
            'parent' => $a['parent'],
        ])->all();
    }

    /**
     * Compte suggéré à partir du libellé d'une catégorie, ou null.
     *
     * La correspondance la plus longue l'emporte : « charges locatives » doit
     * gagner sur « charges » seul si les deux figurent au dictionnaire.
     */
    public function suggestFor(string $label): ?string
    {
        $haystack = $this->normalize($label);

        if ($haystack === '') {
            return null;
        }

        $best = null;
        $bestLength = 0;

        foreach (self::KEYWORD_MAP as $keyword => $ref) {
            $needle = $this->normalize($keyword);

            if (str_contains($haystack, $needle) && strlen($needle) > $bestLength) {
                $best = $ref;
                $bestLength = strlen($needle);
            }
        }

        return $best;
    }

    /**
     * Existence d'un compte dans la liste officielle.
     */
    public function exists(string $ref): bool
    {
        return $this->all()->contains(fn (array $a) => $a['ref'] === $ref);
    }

    public function find(string $ref, string $locale = 'fr'): ?array
    {
        $account = $this->all()->firstWhere('ref', $ref);

        return $account ? $account + ['label' => $this->label($account, $locale)] : null;
    }

    /**
     * Libellé officiel dans la langue demandée.
     *
     * Le PCN n'existe qu'en français, allemand et anglais : le luxembourgeois
     * et le portugais retombent sur le français. Traduire soi-même un intitulé
     * réglementaire serait une faute — ce n'est pas notre texte.
     */
    private function label(array $account, string $locale): string
    {
        return match ($locale) {
            'de' => $account['de'] !== '' ? $account['de'] : $account['fr'],
            'en' => $account['en'] !== '' ? $account['en'] : $account['fr'],
            default => $account['fr'],
        };
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
        ]);

        return preg_replace('/\s+/', ' ', $value);
    }
}
