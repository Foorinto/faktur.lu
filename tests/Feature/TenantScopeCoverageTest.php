<?php

namespace Tests\Feature;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Couverture de l'isolation multi-tenant.
 *
 * `MultiTenantIsolationTest` vérifie que le cloisonnement fonctionne sur les
 * modèles qu'il nomme. Celui-ci vérifie qu'aucun modèle n'y échappe — ce n'est
 * pas la même question, et c'est la seconde qui protège dans la durée.
 *
 * Le risque n'est pas que la portée globale cesse de fonctionner : c'est qu'un
 * modèle créé dans six mois porte un `user_id` sans le trait, et soit protégé
 * à la main, requête par requête. Il suffit alors d'un `where` oublié dans un
 * contrôleur pour qu'un client voie les données d'un autre. C'est le mode de
 * fuite classique du SaaS multi-tenant.
 *
 * Ce test a trouvé trois modèles dans ce cas dès sa première exécution.
 */
class TenantScopeCoverageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Modèles qui portent un `user_id` sans être cloisonnés, délibérément.
     *
     * Toute entrée ici est une décision, pas un oubli : elle doit dire
     * pourquoi. Ajouter un modèle à cette liste pour faire taire le test,
     * c'est ouvrir la fuite qu'il est chargé de prévenir.
     *
     * @var array<class-string, string>
     */
    private const EXCEPTIONS = [
        \App\Models\AuditLog::class => "Journal d'audit : l'administration doit pouvoir le lire pour tous les comptes, c'est sa raison d'être.",
        \App\Models\RequestMetric::class => 'Télémétrie technique, agrégée côté administration et jamais exposée à un utilisateur.',
        \App\Models\SupportTicket::class => 'Le support doit lire les tickets de tous les comptes pour y répondre.',
        \App\Models\SatisfactionSurvey::class => 'Réponses agrégées côté administration ; aucun écran utilisateur ne les liste.',
        \App\Models\DripEmail::class => "Séquence d'emails pilotée par le système, hors session authentifiée.",
        \App\Models\Organization::class => "Une organisation est partagée entre plusieurs comptes : la cloisonner par user_id la rendrait invisible à ses propres membres.",
        \App\Models\OrganizationMember::class => 'Même raison : le lien appartient à l\'organisation, pas à un utilisateur.',
        \App\Models\AccountantInvitation::class => "Portail comptable : lue hors session utilisateur, au moment où le comptable accepte l'invitation.",
        \App\Models\AccountantDownload::class => 'Portail comptable : écrite par le comptable, dans un contexte d\'authentification distinct.',
    ];

    /**
     * @return list<class-string<Model>>
     */
    private function modelesEloquent(): array
    {
        $racine = app_path('Models');
        $fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));

        $classes = [];

        foreach ($fichiers as $fichier) {
            if ($fichier->getExtension() !== 'php') {
                continue;
            }

            $relatif = str_replace([$racine.DIRECTORY_SEPARATOR, '.php'], '', $fichier->getPathname());
            $classe = 'App\\Models\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relatif);

            if (! class_exists($classe)) {
                continue;
            }

            $reflection = new \ReflectionClass($classe);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            $classes[] = $classe;
        }

        sort($classes);

        return $classes;
    }

    public function test_every_model_holding_a_user_id_is_either_scoped_or_justified(): void
    {
        $nonCloisonnes = [];

        foreach ($this->modelesEloquent() as $classe) {
            if (in_array(BelongsToUser::class, class_uses_recursive($classe), true)) {
                continue;
            }

            if (array_key_exists($classe, self::EXCEPTIONS)) {
                continue;
            }

            $table = (new $classe)->getTable();

            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                $nonCloisonnes[] = $classe;
            }
        }

        $this->assertSame(
            [],
            $nonCloisonnes,
            "Ces modèles portent un user_id sans portée globale d'isolation. Ajoutez-leur le trait "
            ."BelongsToUser, ou inscrivez-les dans EXCEPTIONS en disant pourquoi :\n  - "
            .implode("\n  - ", $nonCloisonnes)
        );
    }

    public function test_the_global_scope_is_actually_registered_on_scoped_models(): void
    {
        $sansPortee = [];

        foreach ($this->modelesEloquent() as $classe) {
            if (! in_array(BelongsToUser::class, class_uses_recursive($classe), true)) {
                continue;
            }

            // Le trait pourrait être présent et sa portée neutralisée par une
            // surcharge de `boot` dans le modèle : on vérifie l'effet, pas la
            // présence du trait.
            if (! array_key_exists('user', (new $classe)->getGlobalScopes())) {
                $sansPortee[] = $classe;
            }
        }

        $this->assertSame([], $sansPortee, 'Portée « user » absente sur : '.implode(', ', $sansPortee));
    }

    /**
     * Le garde-fou du garde-fou : si le trait cessait de cloisonner, les deux
     * tests ci-dessus resteraient verts alors que tout fuirait.
     */
    public function test_the_scope_really_hides_another_users_rows(): void
    {
        $alice = \App\Models\User::factory()->create();
        $bob = \App\Models\User::factory()->create();

        $this->actingAs($alice);
        \App\Models\Client::factory()->create(['name' => 'Client d\'Alice']);

        $this->actingAs($bob);
        \App\Models\Client::factory()->create(['name' => 'Client de Bob']);

        $this->assertSame(1, \App\Models\Client::count());
        $this->assertSame('Client de Bob', \App\Models\Client::first()->name);
    }
}
