<?php

namespace App\Console\Commands;

use App\Services\EncryptionKeyGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Contrôle que l'APP_KEY correspond toujours aux données chiffrées.
 *
 * Lancé par le planificateur toutes les heures : c'est ce qui borne les dégâts.
 * Un changement de clé passé inaperçu ne coûte pas la même chose selon qu'on
 * s'en aperçoit dans l'heure ou trois semaines plus tard, quand des centaines
 * d'enregistrements ont été écrits sous la nouvelle.
 */
class EncryptionCheckCommand extends Command
{
    protected $signature = 'encryption:check
                            {--accepter : Acte une rotation volontaire de la clé}';

    protected $description = "Vérifie que l'APP_KEY déchiffre toujours les données chiffrées";

    public function handle(EncryptionKeyGuard $garde): int
    {
        if ($this->option('accepter')) {
            $garde->accepterLaCleCourante('rotation acceptée en ligne de commande');
            $this->info('Nouvelle empreinte enregistrée. Les contrôles suivants s\'y compareront.');

            return self::SUCCESS;
        }

        $resultat = $garde->verifier();

        $this->line('');
        $this->line('  Valeurs éprouvées : '.$resultat['echantillons']);
        $this->line('  Échecs            : '.$resultat['echecs']);
        $this->line('');

        switch ($resultat['etat']) {
            case EncryptionKeyGuard::ETAT_CONFORME:
            case EncryptionKeyGuard::ETAT_PREMIER_ENREGISTREMENT:
                $this->info('✓ '.$resultat['message']);

                return self::SUCCESS;

            case EncryptionKeyGuard::ETAT_CHANGEE_SANS_DEGAT:
                $this->warn('⚠ '.$resultat['message']);
                Log::warning('[Encryption] '.$resultat['message']);
                $this->alerter('APP_KEY modifiée', $resultat['message']);

                return self::FAILURE;

            case EncryptionKeyGuard::ETAT_DONNEES_ILLISIBLES:
            default:
                $this->error('✗ '.$resultat['message']);
                Log::error('[Encryption] '.$resultat['message']);
                $this->alerter('DONNÉES CHIFFRÉES ILLISIBLES', $resultat['message']);

                return self::FAILURE;
        }
    }

    /**
     * L'alerte emprunte le canal des sauvegardes : même destinataire, même
     * réflexe. Un second canal serait un second endroit à surveiller.
     */
    private function alerter(string $objet, string $message): void
    {
        $destinataire = config('backup.notification_email');

        if (! $destinataire) {
            return;
        }

        $application = config('marque.nom');

        try {
            Mail::raw(
                $message."\n\n"
                ."Que faire :\n"
                ."1. Ne rien écrire dans l'application tant que la clé n'est pas rétablie.\n"
                ."2. Comparer l'APP_KEY du .env avec celle conservée dans le gestionnaire de mots de passe.\n"
                ."3. Si la rotation était voulue : php artisan encryption:check --accepter\n",
                function ($mail) use ($destinataire, $application, $objet) {
                    $mail->to($destinataire)->subject("[{$application}] {$objet} - action requise");
                }
            );
        } catch (\Throwable $e) {
            // Une alerte qui ne part pas ne doit pas masquer ce qu'elle
            // signalait : le journal garde la trace dans tous les cas.
            Log::error("[Encryption] Alerte non envoyée : {$e->getMessage()}");
        }
    }
}
