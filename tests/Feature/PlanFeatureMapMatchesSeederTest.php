<?php

namespace Tests\Feature;

use App\Models\Plan;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La table JavaScript des fonctionnalités ne doit pas dériver des plans.
 *
 * `resources/js/Composables/usePlanFeatures.js` duplique, sous forme de
 * « fonctionnalité → plan minimum », ce que `PlansSeeder` déclare sous forme de
 * « plan → fonctionnalités ». Son commentaire dit d'ailleurs qu'elle « doit
 * rester alignée » — une phrase qui n'oblige personne.
 *
 * La dérive est silencieuse dans les deux sens. Ouvrir une fonctionnalité au
 * plan Essentiel côté serveur sans toucher au JavaScript la laisse affichée
 * comme verrouillée : l'utilisateur ne la trouve jamais, alors qu'il l'a payée.
 * Dans l'autre sens, l'interface la propose et le serveur la refuse — l'écran
 * promet ce que la route interdit.
 *
 * Ce test lit le fichier JavaScript. C'est inhabituel, et c'est le seul moyen :
 * la table vit là-bas, et rien d'autre ne la confronte à la réalité.
 */
class PlanFeatureMapMatchesSeederTest extends TestCase
{
    use RefreshDatabase;

    private const CHEMIN_JS = 'resources/js/Composables/usePlanFeatures.js';

    /** Du plan le plus ouvert au plus restreint, dans l'ordre où le JS les classe. */
    private const RANGS = ['free' => 0, 'essentiel' => 1, 'pro' => 2];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);
    }

    /**
     * Extrait FEATURE_TO_MIN_PLAN du fichier JavaScript.
     *
     * @return array<string, string>
     */
    private function tableJavaScript(): array
    {
        $source = file_get_contents(base_path(self::CHEMIN_JS));

        $this->assertNotFalse($source, 'Le composable des plans est introuvable : '.self::CHEMIN_JS);

        preg_match('/const FEATURE_TO_MIN_PLAN = \{(.*?)\n\};/s', $source, $bloc);

        $this->assertNotEmpty($bloc, 'FEATURE_TO_MIN_PLAN introuvable — le composable a été renommé ou restructuré.');

        // Les clés sont tantôt nues (`invoices:`), tantôt entre guillemets
        // (`'2fa':`) : JavaScript impose les guillemets dès que l'identifiant
        // commence par un chiffre.
        preg_match_all("/^\s*'?([a-z0-9_]+)'?:\s*'(free|essentiel|pro)'/mi", $bloc[1], $lignes, PREG_SET_ORDER);

        $table = [];

        foreach ($lignes as $ligne) {
            $table[$ligne[1]] = $ligne[2];
        }

        $this->assertNotEmpty($table, 'Aucune fonctionnalité lue : la forme du fichier a changé.');

        return $table;
    }

    /**
     * Plan le plus accessible qui inclut la fonctionnalité, d'après la base.
     */
    private function planMinimumReel(string $feature): ?string
    {
        $plans = Plan::all()
            ->filter(fn (Plan $plan) => in_array($feature, $plan->features ?? [], true))
            ->sortBy(fn (Plan $plan) => self::RANGS[$plan->name] ?? 99);

        return $plans->first()?->name;
    }

    public function test_the_javascript_map_matches_the_plans(): void
    {
        $ecarts = [];

        foreach ($this->tableJavaScript() as $feature => $planJs) {
            $planReel = $this->planMinimumReel($feature);

            if ($planReel === null) {
                $ecarts[] = "{$feature} : annoncée « {$planJs} » côté interface, mais aucun plan ne la porte.";

                continue;
            }

            if ($planReel !== $planJs) {
                $ecarts[] = "{$feature} : « {$planJs} » côté interface, « {$planReel} » côté serveur.";
            }
        }

        $this->assertSame([], $ecarts, "La table de usePlanFeatures.js a dérivé des plans :\n  - ".implode("\n  - ", $ecarts)."\n");
    }

    /**
     * L'oubli inverse : une fonctionnalité livrée côté serveur mais absente du
     * JavaScript. Elle y est traitée comme du Pro — verrouillée pour tout le
     * monde sauf les abonnés Pro, sans que personne l'ait décidé.
     */
    public function test_no_feature_is_missing_from_the_javascript_map(): void
    {
        $connuesDuJs = array_keys($this->tableJavaScript());

        $oubliees = Plan::all()
            ->flatMap(fn (Plan $plan) => $plan->features ?? [])
            ->unique()
            ->reject(fn (string $feature) => in_array($feature, $connuesDuJs, true))
            ->values()
            ->all();

        $this->assertSame([], $oubliees,
            "Ces fonctionnalités existent côté serveur mais manquent à usePlanFeatures.js, "
            ."qui les verrouille donc au plan Pro : ".implode(', ', $oubliees)
        );
    }
}
