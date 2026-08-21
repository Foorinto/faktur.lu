<?php

namespace Tests\Unit;

use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Toute route appelée par une page vue sans être connecté doit figurer dans le
 * groupe `public` de Ziggy.
 *
 * Un visiteur anonyme ne reçoit que ces routes — 145 sur 547, ce qui allège
 * chaque page de 46 Ko. Le revers est brutal : `route('nom-absent')` lève une
 * exception côté client et **la page blanchit entièrement**, sans erreur
 * serveur, donc sans que rien ne le signale.
 *
 * Le prerendering couvre les 430 pages marketing — mais pas la connexion,
 * l'inscription, le mot de passe oublié ni les outils interactifs, et pas
 * davantage les gestionnaires de clic, qui ne s'exécutent pas au rendu. D'où
 * cette analyse statique, qui les lit tous.
 */
class PublicRouteGroupTest extends TestCase
{
    /**
     * Points d'entrée servis à un visiteur non connecté.
     *
     * @var array<int, string>
     */
    private const ENTREES = [
        'Pages/Welcome.vue', 'Pages/Pricing.vue', 'Pages/Contact.vue', 'Pages/About.vue',
        'Pages/FaiaValidator.vue', 'Pages/Glossary.vue', 'Pages/Partners.vue', 'Pages/WhyFaktur.vue',
        'Pages/Auth', 'Pages/Tools', 'Pages/Survey', 'Pages/Blog', 'Pages/Legal', 'Pages/Features',
        // Ajoutés le 2026-08-21 : les pages sectorielles et les pages de
        // segment appellent `sector-lead.store` depuis un formulaire public.
        // Leur absence ici a failli laisser passer un blanchiment — cette liste
        // est tenue à la main, et c'est sa faiblesse.
        'Pages/Sectors', 'Pages/Segments',
        'Layouts/MarketingLayout.vue', 'Layouts/GuestLayout.vue',
    ];

    public function test_every_route_called_by_a_public_page_is_published(): void
    {
        $groupe = config('ziggy.groups.public');

        $this->assertNotEmpty($groupe, 'Le groupe public de Ziggy est introuvable.');

        $manquantes = [];

        foreach ($this->fichiersPublics() as $fichier) {
            preg_match_all("/route\(\s*'([a-z0-9_.\-]+)'/", file_get_contents($fichier), $trouvees);

            foreach (array_unique($trouvees[1]) as $nom) {
                if (! Str::is($groupe, $nom)) {
                    $manquantes[$nom] = str_replace(resource_path('js').'/', '', $fichier);
                }
            }
        }

        $details = collect($manquantes)->map(fn ($ou, $nom) => "{$nom}  (appelée dans {$ou})")->values()->all();

        $this->assertSame([], $details, sprintf(
            "Ces routes sont appelées par une page publique mais absentes du groupe `public` de config/ziggy.php.\n"
            ."La page blanchira dans le navigateur, sans erreur serveur :\n  - %s\n",
            implode("\n  - ", $details)
        ));
    }

    /** @return array<int, string> */
    private function fichiersPublics(): array
    {
        $fichiers = [];

        foreach (self::ENTREES as $entree) {
            $chemin = resource_path("js/{$entree}");

            if (is_file($chemin)) {
                $fichiers[] = $chemin;

                continue;
            }

            if (! is_dir($chemin)) {
                continue;
            }

            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($chemin)) as $f) {
                if ($f->getExtension() === 'vue') {
                    $fichiers[] = $f->getPathname();
                }
            }
        }

        return $fichiers;
    }
}
