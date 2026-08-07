<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Toute route appelée depuis un composant Vue doit figurer dans `ziggy.js`.
 *
 * `resources/js/ziggy.js` est un fichier **généré et versionné**, embarqué dans
 * le bundle à la compilation. Ajouter une route dans `routes/web.php` ne suffit
 * donc pas : sans `php artisan ziggy:generate` suivi de `npm run build`, l'appel
 * `route('…')` lève « Ziggy error: route is not in the route list » et **la page
 * blanchit entièrement**, sans erreur serveur.
 *
 * Deux raisons pour lesquelles rien ne l'attrapait :
 * - les tests fonctionnels vérifient le rendu Inertia côté serveur, qui reste
 *   parfaitement valide ;
 * - `deploy.sh` ne régénère pas Ziggy, donc un commit incomplet casse aussi la
 *   production, pas seulement le poste de développement.
 *
 * D'où ce garde-fou, écrit après avoir cassé toutes les pages publiques en
 * ajoutant `for_accountants`.
 */
class ZiggyRoutesTest extends TestCase
{
    private static function basePath(string $relative): string
    {
        return dirname(__DIR__, 2).'/'.$relative;
    }

    /**
     * Noms de routes appelés littéralement dans les composants Vue.
     *
     * `localizedRoute('x')` reçoit un nom **sans suffixe de langue** : Ziggy
     * connaît alors `x.fr`, `x.de`… On accepte donc qu'un préfixe corresponde.
     *
     * @return array<int, string>
     */
    private function routesUsedInVue(): array
    {
        $names = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::basePath('resources/js'))
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'vue') {
                continue;
            }

            $relative = str_replace(self::basePath('resources/js/Pages').'/', '', $file->getPathname());

            if (in_array($relative, self::DEAD_PAGES, true)) {
                continue;
            }

            preg_match_all(
                "/\b(?:localizedRoute|route)\(\s*'([a-z][a-z0-9._-]*)'/",
                file_get_contents($file->getPathname()),
                $matches
            );

            foreach ($matches[1] as $name) {
                $names[$name] = true;
            }
        }

        return array_keys($names);
    }

    /**
     * Composants sans route, conservés volontairement.
     *
     * `Admin/Login.vue` et `Admin/TwoFactorChallenge.vue` datent d'une époque
     * où l'administration avait son propre formulaire de connexion. Elle
     * utilise désormais l'authentification normale de l'application, et
     * `AdminAuthController` n'est plus routé nulle part. Ces vues sont donc
     * inatteignables : leurs appels de route ne peuvent casser personne.
     *
     * @var array<int, string>
     */
    private const DEAD_PAGES = [
        'Admin/Login.vue',
        'Admin/TwoFactorChallenge.vue',
    ];

    public function test_les_routes_appelees_par_le_front_sont_dans_ziggy(): void
    {
        $ziggy = file_get_contents(self::basePath('resources/js/ziggy.js'));

        $this->assertNotEmpty($ziggy, 'resources/js/ziggy.js est vide ou absent.');

        $missing = array_values(array_filter(
            $this->routesUsedInVue(),
            // Le nom exact, ou une variante localisée « nom.fr ».
            fn ($name) => ! str_contains($ziggy, '"'.$name.'"') && ! str_contains($ziggy, '"'.$name.'.')
        ));

        $this->assertSame([], $missing, sprintf(
            "Ces routes sont appelées par un composant Vue mais absentes de ziggy.js : %s\n".
            "La page concernée blanchira dans le navigateur, sans erreur serveur.\n".
            "Corriger avec :  php artisan ziggy:generate  puis  npm run build",
            implode(', ', $missing)
        ));
    }

    /**
     * Une route localisée vit dans QUATRE endroits, et les oublier casse
     * différemment :
     *
     *   routes/web.php                          la route elle-même
     *   resources/js/ziggy.js                   généré, versionné, lu au build
     *   config/localized_routes.php             sitemap et liens hreflang
     *   resources/js/Composables/useLocalizedRoute.js   DEUX tables internes
     *
     * L'oubli de la dernière est le plus vicieux : `localizedRoute('x')` appelle
     * alors `route('x')` sans suffixe de langue. Ziggy ne connaît que `x.fr`,
     * `x.de`… et lève une exception qui fait blanchir toute page utilisant le
     * gabarit. Le serveur, lui, répond parfaitement.
     */
    public function test_le_composable_connait_les_memes_routes_localisees_que_la_config(): void
    {
        $config = array_keys(require self::basePath('config/localized_routes.php'));
        $js = file_get_contents(self::basePath('resources/js/Composables/useLocalizedRoute.js'));

        // Première table : la liste des noms qui exigent le paramètre de langue.
        preg_match('/const localizedRoutes = \[(.*?)\];/s', $js, $liste);
        preg_match_all("/'([a-z0-9._-]+)'/", $liste[1] ?? '', $declares);

        // Seconde table : le chemin localisé de chaque route.
        preg_match_all("/^\s{4}'([a-z0-9._-]+)':\s*\{/m", $js, $chemins);

        $absentesDeLaListe = array_values(array_diff($config, $declares[1] ?? []));
        $absentesDesChemins = array_values(array_diff($config, $chemins[1] ?? []));

        $this->assertSame([], $absentesDeLaListe, sprintf(
            "Déclarées dans config/localized_routes.php mais absentes du tableau `localizedRoutes` ".
            "de useLocalizedRoute.js : %s\nLa page blanchira dans le navigateur.",
            implode(', ', $absentesDeLaListe)
        ));

        $this->assertSame([], $absentesDesChemins, sprintf(
            'Absentes de la table des chemins localisés de useLocalizedRoute.js : %s',
            implode(', ', $absentesDesChemins)
        ));
    }

    public function test_les_routes_localisees_declarees_existent_dans_ziggy(): void
    {
        $ziggy = file_get_contents(self::basePath('resources/js/ziggy.js'));
        $config = require self::basePath('config/localized_routes.php');

        // `config/localized_routes.php` sert au sitemap et aux liens hreflang.
        // Une entrée qui n'existe pas côté Ziggy signale une route oubliée ou un
        // nom mal orthographié dans l'un des deux.
        $missing = array_values(array_filter(
            array_keys($config),
            fn ($name) => ! str_contains($ziggy, '"'.$name.'"') && ! str_contains($ziggy, '"'.$name.'.')
        ));

        $this->assertSame([], $missing, sprintf(
            'Déclarées dans config/localized_routes.php mais introuvables dans ziggy.js : %s',
            implode(', ', $missing)
        ));
    }
}
