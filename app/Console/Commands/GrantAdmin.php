<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantAdmin extends Command
{
    protected $signature = 'user:grant-admin {email} {--reset-password= : Réinitialiser le mot de passe}';
    protected $description = 'Accorde les droits administrateur à un utilisateur';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Utilisateur '$email' introuvable.");
            return 1;
        }

        // is_admin retire du fillable (champ sensible) -> forceFill obligatoire
        $user->forceFill(['is_admin' => true])->save();

        if ($password = $this->option('reset-password')) {
            $user->update(['password' => bcrypt($password)]);
            $this->info("Mot de passe réinitialisé.");
        }

        $this->info("Droits admin accordés à {$user->name} ({$email})");
        $this->info("Accès admin : connectez-vous sur faktur.lu puis allez sur /" . config('admin.url_prefix'));

        return 0;
    }
}
