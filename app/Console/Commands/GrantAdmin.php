<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantAdmin extends Command
{
    protected $signature = 'user:grant-admin {email}';
    protected $description = 'Accorde les droits administrateur à un utilisateur';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Utilisateur '$email' introuvable.");
            return 1;
        }

        $user->update(['is_admin' => true]);

        $this->info("Droits admin accordés à {$user->name} ({$email})");
        $this->info("Accès admin : connectez-vous sur faktur.lu puis allez sur /" . config('admin.url_prefix'));

        return 0;
    }
}
