<?php

namespace App\Http\Controllers\Auth;

use App\Auth\TrustedDevices;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Security\SecurityAlerter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le lien « ce n'était pas moi » d'une alerte de sécurité.
 *
 * Le titulaire arrive ici depuis sa boîte mail, souvent sans être connecté,
 * parfois depuis un compte dont il a déjà perdu la main. Le geste doit donc
 * tenir sans session : la signature de l'URL est la seule preuve, et elle
 * suffit parce que le gel ne fait que refuser, jamais modifier.
 *
 * Ce que le gel fait : toutes les sessions fermées, le jeton « se souvenir de
 * moi » renouvelé, la connexion refusée. Ce qui le lève : la réinitialisation
 * du mot de passe, qui passe par la boîte mail, c'est-à-dire par ce que
 * l'attaquant n'a pas si le titulaire est là.
 *
 * ⚠️ Rejouer le lien ne fait rien de plus : un compte déjà gelé le reste, et
 * la page l'explique. Un lien expiré ou altéré est refusé par le middleware
 * `signed` avant d'arriver ici.
 */
class SecurityLockController extends Controller
{
    public function lock(Request $request, int $user): Response
    {
        // ⚠️ L'identifiant n'est résolu qu'ici, après le contrôle de signature.
        // Avec une liaison de modèle implicite, la résolution passait avant le
        // middleware `signed` : un lien sans signature répondait 404 ou 403
        // selon que le numéro existait, un oracle d'existence des comptes.
        $user = User::findOrFail($user);

        $event = (string) $request->query('event', '');
        $dejaGele = $user->security_locked_at !== null;

        if (! $dejaGele) {
            $user->forceFill([
                'security_locked_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();
        }

        // Toutes les sessions, y compris celle qui a fait le changement : c'est
        // celle-là qu'on veut fermer.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
        }

        // Les appareils mémorisés pour le code par e-mail aussi : un compte
        // gelé ne garde aucun raccourci.
        app(TrustedDevices::class)->forgetAll($user);

        // Le journal est écrit directement : personne n'est connecté ici, et
        // le logger prendrait l'auteur dans la session.
        AuditLog::create([
            'user_id' => $user->getKey(),
            'action' => 'account.locked_by_owner',
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => AuditLog::STATUS_SUCCESS,
            'metadata' => ['event' => $event, 'already_locked' => $dejaGele],
        ]);

        return Inertia::render('Auth/AccountLocked', [
            'event' => in_array($event, SecurityAlerter::EVENTS, true) ? $event : null,
            'alreadyLocked' => $dejaGele,
        ]);
    }
}
