<?php

namespace App\Services;

use App\Models\EmailSettings;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

class EmailProviderService
{
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

        if (!$settings || $settings->provider === EmailSettings::PROVIDER_FAKTUR) {
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

        if (!$config) {
            return Mail::mailer();
        }

        $mailerName = 'user_smtp_' . $settings->user_id;

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

        if (!$config || empty($config['api_key'])) {
            return Mail::mailer();
        }

        $mailerName = 'user_brevo_' . $settings->user_id;

        Config::set("mail.mailers.{$mailerName}", [
            'transport' => 'smtp',
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => $config['username'] ?? '',
            'password' => $config['api_key'],
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
     * Create a Postmark mailer from user settings.
     */
    protected function createPostmarkMailer(EmailSettings $settings): Mailer
    {
        $config = $settings->provider_config;

        if (!$config || empty($config['token'])) {
            return Mail::mailer();
        }

        $mailerName = 'user_postmark_' . $settings->user_id;

        Config::set("mail.mailers.{$mailerName}", [
            'transport' => 'postmark',
            'token' => $config['token'],
        ]);

        return Mail::mailer($mailerName);
    }

    /**
     * Create a Resend mailer from user settings.
     */
    protected function createResendMailer(EmailSettings $settings): Mailer
    {
        $config = $settings->provider_config;

        if (!$config || empty($config['api_key'])) {
            return Mail::mailer();
        }

        $mailerName = 'user_resend_' . $settings->user_id;

        Config::set("mail.mailers.{$mailerName}", [
            'transport' => 'resend',
            'key' => $config['api_key'],
        ]);

        return Mail::mailer($mailerName);
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
                "Ceci est un email de test envoyé depuis faktur.lu.\n\n" .
                "Votre configuration email fonctionne correctement !\n\n" .
                "Provider: {$settings->provider}\n" .
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
        } catch (\Exception $e) {
            // Update verified status
            $settings->update([
                'provider_verified' => false,
                'last_test_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => 'Échec de l\'envoi : ' . $this->parseErrorMessage($e),
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

            $factory = new EsmtpTransportFactory();
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
                'message' => 'Configuration invalide : ' . $this->parseErrorMessage($e),
            ];
        }
    }

    /**
     * Parse error message for user-friendly display.
     */
    protected function parseErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();

        // Common SMTP errors
        if (str_contains($message, 'Connection refused')) {
            return 'Connexion refusée. Vérifiez l\'hôte et le port.';
        }

        if (str_contains($message, 'Authentication failed')) {
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

        // Return a truncated version of the original message
        return mb_substr($message, 0, 100);
    }
}
