<?php

namespace Tests\Unit;

use App\Http\Middleware\HandleInertiaRequests;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Toute clé appelée par `t('…')` côté Vue doit arriver jusqu'au navigateur.
 *
 * Le composable `useTranslations` lit `page.props.translations.app.<clé>`. Deux
 * façons distinctes de casser ça, toutes deux déjà survenues :
 *
 * 1. **la clé est rangée dans un sous-tableau** (`app.faia.accounting_scope`).
 *    Facile à provoquer en insérant une clé par script : le point d'ancrage
 *    choisi tombe à l'intérieur d'un sous-tableau sans qu'on le voie ;
 * 2. **la clé est filtrée par `HandleInertiaRequests`**, qui retire du paquet
 *    envoyé au front tout ce qui commence par `email_`, `pdf_` ou
 *    `mail_subject_`, sauf liste blanche. Une nouvelle clé `pdf_…` destinée à
 *    l'interface disparaît donc silencieusement.
 *
 * Dans les deux cas l'utilisateur voit la clé brute, et aucun test fonctionnel
 * ne s'en aperçoit : ils n'inspectent pas le texte rendu.
 *
 * Ce test interroge donc le middleware lui-même plutôt que le fichier de
 * langue : c'est le seul moyen de couvrir les deux causes à la fois.
 */
class TranslationKeysTest extends TestCase
{
    private const LOCALES = ['fr', 'en', 'de', 'lb', 'pt'];

    /**
     * Clés littérales appelées dans les composants Vue.
     *
     * Les clés terminées par « _ » sont écartées : ce sont des préfixes
     * complétés à l'exécution (`t('hr_event_type_' + type)`), qu'une analyse
     * statique ne peut pas résoudre.
     *
     * @return array<int, string>
     */
    private function keysUsedInVue(): array
    {
        $keys = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('js'))
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'vue') {
                continue;
            }

            preg_match_all("/\bt\('([a-z0-9_]+)'/", file_get_contents($file->getPathname()), $matches);

            foreach ($matches[1] as $key) {
                if (! str_ends_with($key, '_')) {
                    $keys[$key] = true;
                }
            }
        }

        return array_keys($keys);
    }

    /**
     * Traductions telles que le middleware les transmet à Inertia, filtrage
     * compris. Passe par la méthode réelle plutôt que d'en recopier la règle :
     * un test qui réimplémente ce qu'il vérifie ne vérifie rien.
     *
     * @return array<string, mixed>
     */
    private function translationsSentToBrowser(string $locale): array
    {
        $method = new \ReflectionMethod(HandleInertiaRequests::class, 'getTranslations');
        $method->setAccessible(true);

        return $method->invoke(new HandleInertiaRequests(), $locale)['app'] ?? [];
    }

    /** @return array<string, array<int, string>> */
    public static function locales(): array
    {
        return array_combine(
            self::LOCALES,
            array_map(fn ($locale) => [$locale], self::LOCALES)
        );
    }

    #[DataProvider('locales')]
    public function test_les_cles_appelees_par_le_front_lui_parviennent(string $locale): void
    {
        $translations = $this->translationsSentToBrowser($locale);

        $missing = array_values(array_filter(
            $this->keysUsedInVue(),
            fn ($key) => ! array_key_exists($key, $translations) || ! is_string($translations[$key])
        ));

        $this->assertSame([], $missing, sprintf(
            "Ces clés sont appelées par un composant Vue mais n'arrivent pas au navigateur en « %s » : %s\n".
            "Deux causes possibles : la clé est rangée dans un sous-tableau de resources/lang/%s/app.php, ".
            "ou son préfixe la fait filtrer par HandleInertiaRequests (ajoutez-la alors à \$frontendKept).",
            $locale,
            implode(', ', $missing),
            $locale
        ));
    }
}
