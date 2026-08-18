<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Redirection des requêtes en clair vers HTTPS.
 *
 * L'apex servait la même page sur les deux protocoles — contenu dupliqué pour
 * les moteurs, et pages non chiffrées pour qui arrive par un vieux lien. HSTS
 * ne couvrait pas ce cas : il ne s'applique qu'après une première visite en
 * HTTPS.
 */
class RedirectToHttpsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://faktur.lu']);
    }

    public function test_a_plain_request_is_permanently_redirected(): void
    {
        $this->get('http://faktur.lu/fr')
            ->assertStatus(301)
            ->assertRedirect('https://faktur.lu/fr');
    }

    public function test_the_path_and_query_survive(): void
    {
        $this->get('http://faktur.lu/fr/blog?page=2')
            ->assertRedirect('https://faktur.lu/fr/blog?page=2');
    }

    public function test_a_secure_request_passes_through(): void
    {
        $this->get('https://faktur.lu/fr')->assertOk();
    }

    /**
     * Une 301 sur un POST fait rejouer la requête en GET par la plupart des
     * clients : un webhook configuré en http cesserait de fonctionner au lieu
     * d'être corrigé. Le .htaccess racine documente déjà ce piège.
     */
    public function test_a_post_is_never_redirected(): void
    {
        $reponse = $this->post('http://faktur.lu/newsletter/subscribe', ['email' => 'test@exemple.lu']);

        $this->assertNotSame(301, $reponse->getStatusCode(),
            'Une redirection 301 casserait les webhooks configurés en http.');
    }

    /**
     * Une requête arrivant sous un autre nom — accès par IP, en-tête Host
     * inattendu, domaine parqué — serait renvoyée vers une adresse qui n'est
     * pas la sienne.
     */
    public function test_another_host_is_left_alone(): void
    {
        $this->get('http://autre-domaine.example/fr')->assertOk();
    }

    /**
     * Sur un poste de développement servi en clair, rediriger rendrait
     * l'application inaccessible.
     */
    public function test_a_plain_site_is_left_alone(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);

        $this->get('http://127.0.0.1:8000/fr')->assertOk();
    }
}
