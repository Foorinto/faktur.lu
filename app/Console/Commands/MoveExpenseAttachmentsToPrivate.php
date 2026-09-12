<?php

namespace App\Console\Commands;

use App\Models\Expense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Bascule les justificatifs de dépenses déjà stockés sur le disque PUBLIC vers
 * le disque privé (faille HIGH-6 : ils étaient accessibles par URL sans
 * authentification).
 *
 * Les NOUVEAUX justificatifs vont déjà sur 'local' (Expense::registerMediaCollections).
 * Cette commande ne traite que l'existant. Idempotente : relançable sans risque.
 *
 * À exécuter une fois en production, après déploiement du correctif :
 *   php artisan expenses:privatize-attachments        (essai à blanc)
 *   php artisan expenses:privatize-attachments --reel  (déplacement effectif)
 */
class MoveExpenseAttachmentsToPrivate extends Command
{
    protected $signature = 'expenses:privatize-attachments {--reel : Effectue réellement le déplacement}';

    protected $description = 'Déplace les justificatifs de dépenses du disque public vers le disque privé';

    public function handle(): int
    {
        $reel = $this->option('reel');
        $public = Storage::disk('public');
        $prive = Storage::disk('local');

        $medias = Media::query()
            ->where('model_type', Expense::class)
            ->where('collection_name', 'attachments')
            ->where('disk', 'public')
            ->get();

        if ($medias->isEmpty()) {
            $this->info('Aucun justificatif de dépense sur le disque public : rien à faire.');

            return self::SUCCESS;
        }

        $deplaces = 0;

        foreach ($medias as $media) {
            $chemin = $media->getPathRelativeToRoot();

            if (! $public->exists($chemin)) {
                // Fichier déjà absent du public : on corrige seulement le disque en base.
                if ($reel) {
                    $media->disk = 'local';
                    $media->save();
                }
                $this->warn("#{$media->id} : fichier introuvable sur public, disque corrigé en base.");
                continue;
            }

            $this->line("#{$media->id} : {$chemin}");

            if ($reel) {
                $prive->put($chemin, $public->get($chemin));
                $public->delete($chemin);
                // Répertoire {id}/ désormais vide côté public.
                $dossier = dirname($chemin);
                if ($dossier !== '.' && empty($public->files($dossier))) {
                    $public->deleteDirectory($dossier);
                }
                $media->disk = 'local';
                $media->save();
            }

            $deplaces++;
        }

        if ($reel) {
            $this->info("{$deplaces} justificatif(s) déplacé(s) vers le disque privé.");
        } else {
            $this->warn("{$deplaces} justificatif(s) à déplacer. Relancer avec --reel pour appliquer.");
        }

        return self::SUCCESS;
    }
}
