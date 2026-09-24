<?php

namespace App\Services;

use App\Models\EmailSettings;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

class EmailProviderService
{
    public const FEATURE = 'custom_email_provider';

    public function __construct(protected PlanService $plans) {}

    /**
     * Le plan de l'utilisateur lui permet-il d'envoyer par son propre
     * fournisseur ? Réservé au plan Pro (FEAT-131) ; l'essai donne les
     * fonctionnalités Pro, donc oui pendant l'essai.
     */
    public function customProviderAllowed(User $user): bool
    {
        return $this->plans->hasFeature($user, self::FEATURE);
    }

    /**
     * Get the mailer for a specific user.
     *
     * Type de retour volontairement le CONTRAT et non la classe concrète :
     * Mail::mailer() peut renvoyer un MailFake pendant les tests, et une
     * signature figée sur Illuminate\Mail\Mailer rendait tout ce service
     * intestable (TypeError dès que Mail::fake() est utilisé).
     */
    public function getMailerForUser(User $user): MailerContract
    {
        $settings = $user->emailSettings;

        if (! $settings || $settings->provider === EmailSettings::PROVIDER_FAKTUR) {
            return Mail::mailer();
        }

        // Plan sans la fonctionnalité (compte revenu en Gratuit après l'essai) :
        // la configuration est conservée, mais les envois repassent par la
        // plateforme jusqu'à un plan qui l'inclut.
        if (! $this->customProviderAllowed($user)) {
            return Mail::mailer();
        }

        return match ($settings->provider) {
            EmailSettings::PROVIDER_SMTP => $this->createSmtpMailer($settings),
            EmailSettings::PROVIDER_BREVO => $this->createBrevoMailer($settings),
            EmailSettings::PROVIDER_POSTMARK => $this->createPostmarkMailer($settings),
            EmailSettings::PROVIDER_RESEND => $this->createResendMailer($settings),
            default => Mail::mailer(),
        };
    }

    /**
     * Create a SMTP mailer from user settings.
     */
    protected function createSmtpMailer(EmailSettings $settings): Mailer
    {
        $config = $settings->provider_config;

        if (! $config) {
            return Mail::mailer();
        }

        $mailerName = 'user_smtp_'.$settings->user_id;

        Config::set("mail.mailers.{$mailerName}", [
            'transport' => 'smtp',
            'host' => $config['host'] ?? '',
            'port' => $config['port'] ?? 587,
            'encryption' => $config['encryption'] ?? 'tls',
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'timeout' => 30,
        ]);

        // Set the from address
        Config::set("mail.mailers.{$mailerName}.from", [
            'address' => $settings->getEffectiveFromAddress(),
            'name' => $settings->getEffectiveFromName(),
        ]);

        return Mail::mailer($mailerName);
    }

    /**
     * Create a Brevo mailer from user settings.
     */
    protected function createBrevoMailer(EmailSettings $settings): Mailer
    {
        $config = $settings->provider_config;

        if (! $config || empty($config['api_key'])) {
            return Mail::mailer();
        }

        return $this->relaySmtp($settings, 'brevo', 'smtp-relay.brevo.com', $config['username'] ?? '', $config['api_key']);
    }

    /**
     * Mailer SMTP vers le relais d'un fournisseur (Brevo, Postmark, Resend).
     *
     * Les transports « postmark » et « resend » intégrés à Laravel exigent des
     * bibliothèques absentes du dépôt : leur simple création levait une
     * erreur PHP fatale (« Class "Resend" not found ») qui échappait au filet
     * de l'envoi de test et laissait l'utilisateur avec un code de référence
     * (retour du 2026-09-24). Chaque fournisseur expose un relais SMTP
     * authentifié par sa clé : aucune dépendance, et le même chemin que
     * Brevo, éprouvé en production.
     */
    protected function relaySmtp(EmailSettings $settings, string $name, string $host, string $username, string $password): Mailer
    {
        $mailerName = "user_{$name}_{$settings->user_id}";

        Config::set("mail.mailers.{$mailerName}", [
            'transport' => 'smtp',
            'host' => $host,
            'port' => 587,
            'encryption' => 'tls',
            'username' => $username,
            'password' => $password,
            'timeout' => 30,
        ]);

        Config::set("mail.mailers.{$mailerName}.from", [
            'address' => $settings->getEffectiveFromAddress(),
            'name' => $settings->getEffectiveFromName(),
        ]);

        return Mail::mailer($mailerName);
    }

    /**
     * Create a Postmark mailer from user settings.
     */
    protected function createPostmarkMailer(EmailSettings $settings): Mailer
    {
        $config = $settings->provider_config;

        if (! $config || empty($config['token'])) {
            return Mail::mailer();
        }

        // Postmark : le jeton de serveur sert d'identifiant et de mot de passe.
        return $this->relaySmtp($settings, 'postmark', 'smtp.postmarkapp.com', $config['token'], $config['token']);
    }

    /**
     * Create a Resend mailer from user settings.
     */
    protected function createResendMailer(EmailSettings $settings): Mailer
    {
        $config = $settings->provider_config;

        if (! $config || empty($config['api_key'])) {
            return Mail::mailer();
        }

        // Resend : identifiant fixe « resend », la clé API en mot de passe.
        return $this->relaySmtp($settings, 'resend', 'smtp.resend.com', 'resend', $config['api_key']);
    }

    /**
     * Test the email configuration by sending a test email.
     */
    public function testConfiguration(EmailSettings $settings): array
    {
        try {
            $mailer = match ($settings->provider) {
                EmailSettings::PROVIDER_SMTP => $this->createSmtpMailer($settings),
                EmailSettings::PROVIDER_BREVO => $this->createBrevoMailer($settings),
                EmailSettings::PROVIDER_POSTMARK => $this->createPostmarkMailer($settings),
                EmailSettings::PROVIDER_RESEND => $this->createResendMailer($settings),
                default => Mail::mailer(),
            };

            $testEmail = $settings->user->email;
            $fromAddress = $settings->getEffectiveFromAddress();
            $fromName = $settings->getEffectiveFromName();

            $mailer->raw(
                "Ceci est un email de test envoyé depuis faktur.lu.\n\n".
                "Votre configuration email fonctionne correctement !\n\n".
                "Provider: {$settings->provider}\n".
                "From: {$fromName} <{$fromAddress}>",
                function ($message) use ($testEmail, $fromAddress, $fromName) {
                    $message->to($testEmail)
                        ->from($fromAddress, $fromName)
                        ->subject('Test de configuration email - faktur.lu');
                }
            );

            // Update verified status
            $settings->update([
                'provider_verified' => true,
                'last_test_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "Email de test envoyé avec succès à {$testEmail}",
            ];
        } catch (\Throwable $e) {
            // Throwable et non Exception : une erreur PHP (classe absente,
            // type) doit elle aussi finir en message lisible, pas en code de
            // référence. On la journalise, c'est un défaut du code, pas de
            // la configuration de l'utilisateur.
            if ($e instanceof \Error) {
                report($e);
            }

            // Update verified status
            $settings->update([
                'provider_verified' => false,
                'last_test_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => 'Échec de l\'envoi : '.$this->parseErrorMessage($e),
            ];
        }
    }

    /**
     * Validate SMTP connection without sending.
     */
    public function validateSmtpConnection(array $config): array
    {
        try {
            $encryption = $config['encryption'] ?? 'tls';
            $scheme = $encryption === 'ssl' ? 'smtps' : 'smtp';

            $dsn = new Dsn(
                $scheme,
                $config['host'] ?? '',
                $config['username'] ?? '',
                $config['password'] ?? '',
                (int) ($config['port'] ?? 587)
            );

            $factory = new EsmtpTransportFactory;
            $transport = $factory->create($dsn);

            // Just creating the transport validates the DSN
            // For actual connection test, we'd need to try sending

            return [
                'success' => true,
                'message' => 'Configuration SMTP valide',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Configuration invalide : '.$this->parseErrorMessage($e),
            ];
        }
    }

    /**
     * Parse error message for user-friendly display.
     */
    protected function parseErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();

        // Common SMTP errors
        if (str_contains($message, 'Connection refused')) {
            return 'Connexion refusée. Vérifiez l\'hôte et le port.';
        }

        if (str_contains($message, 'Authentication failed') || str_contains($message, 'Failed to authenticate')) {
            return 'Authentification échouée. Vérifiez vos identifiants.';
        }

        if (str_contains($message, 'Timed out')) {
            return 'Délai d\'attente dépassé. Le serveur ne répond pas.';
        }

        if (str_contains($message, 'Certificate')) {
            return 'Erreur de certificat SSL/TLS.';
        }

        // Postmark errors
        if (str_contains($message, 'Invalid token')) {
            return 'Token Postmark invalide.';
        }

        // Resend errors
        if (str_contains($message, 'API key')) {
            return 'Clé API Resend invalide.';
        }

        // Expéditeur inconnu du fournisseur : domaine non vérifié (Resend),
        // signature d'expéditeur absente (Postmark), expéditeur non validé (Brevo).
        if (str_contains($message, 'not verified') || str_contains($message, 'Sender Signature') || str_contains($message, 'unverified')) {
            return 'Adresse d\'expédition inconnue de votre fournisseur : vérifiez-y le domaine ou l\'expéditeur, puis réessayez.';
        }

        // Return a truncated version of the original message
        return mb_substr($message, 0, 100);
    }
}
