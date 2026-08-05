<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Toute clé appelée par `t('…')` côté Vue doit exister au premier niveau.
 *
 * Le composable `useTranslations` lit `page.props.translations.app.<clé>` : une
 * clé rangée dans un sous-tableau (`app.faia.accounting_scope`) reste
 * introuvable, et l'interface affiche alors la clé brute à l'utilisateur.
 *
 * L'erreur est facile à commettre en insérant une clé par script : le point
 * d'ancrage choisi peut tomber à l'intérieur d'un sous-tableau. Elle est
 * invisible aux tests fonctionnels, qui n'inspectent pas le texte rendu.
 * D'où ce garde-fou.
 */
class TranslationKeysTest extends TestCase
{
    private const LOCALES = ['fr', 'en', 'de', 'lb', 'pt'];

    /**
     * Chemins résolus depuis ce fichier plutôt que par `resource_path()` :
     * ce test n'amorce pas l'application, donc aucun conteneur n'est disponible.
     */
    private static function basePath(string $relative): string
    {
        return dirname(__DIR__, 2).'/'.$relative;
    }

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
            new \RecursiveDirectoryIterator(self::basePath('resources/js'))
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

    /** @return array<string, array<int, string>> */
    public static function locales(): array
    {
        return array_combine(
            self::LOCALES,
            array_map(fn ($locale) => [$locale], self::LOCALES)
        );
    }

    #[DataProvider('locales')]
    public function test_les_cles_appelees_par_le_front_existent_au_premier_niveau(string $locale): void
    {
        $translations = require self::basePath("resources/lang/{$locale}/app.php");

        $missing = array_values(array_filter(
            $this->keysUsedInVue(),
            fn ($key) => ! array_key_exists($key, $translations) || ! is_string($translations[$key])
        ));

        $this->assertSame([], $missing, sprintf(
            "Ces clés sont appelées par un composant Vue mais absentes du premier niveau de resources/lang/%s/app.php : %s\n".
            "Une clé rangée dans un sous-tableau s'affiche telle quelle à l'utilisateur.",
            $locale,
            implode(', ', $missing)
        ));
    }
}
