<?php

namespace App\Providers;

use App\Support\TraducteurAvecMarque;
use Illuminate\Translation\TranslationServiceProvider as FournisseurDeBase;

/**
 * Remplace le traducteur de Laravel par celui qui connaît le nom de la marque.
 *
 * ⚠️ Le fournisseur d'origine est DIFFÉRÉ : il se charge à la première
 * résolution de `translator` et réenregistre le service. Se contenter d'un
 * `singleton` dans AppServiceProvider ne suffit donc pas, l'enregistrement est
 * écrasé au premier appel à `__()`. C'est ce qui est arrivé.
 *
 * En déclarant ce fournisseur dans `bootstrap/providers.php`, il n'est plus
 * différé et c'est lui qui fournit le service.
 */
class TranslationServiceProvider extends FournisseurDeBase
{
    public function register(): void
    {
        $this->registerLoader();

        $this->app->singleton('translator', function ($app) {
            $traducteur = new TraducteurAvecMarque(
                $app['translation.loader'],
                $app->getLocale()
            );

            $traducteur->setFallback($app->getFallbackLocale());

            return $traducteur;
        });
    }
}
