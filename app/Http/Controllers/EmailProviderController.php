<?php

namespace App\Http\Controllers;

use App\Models\EmailSettings;
use App\Services\EmailProviderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmailProviderController extends Controller
{
    public function __construct(
        protected EmailProviderService $emailProviderService
    ) {}

    /**
     * Show the email provider settings page.
     */
    public function index(Request $request)
    {
        $settings = EmailSettings::getOrCreate($request->user());

        return Inertia::render('Settings/EmailProvider', [
            'settings' => [
                'id' => $settings->id,
                'provider' => $settings->provider,
                'from_address' => $settings->from_address,
                'from_name' => $settings->from_name,
                'provider_verified' => $settings->provider_verified,
                'last_test_at' => $settings->last_test_at?->format('d/m/Y H:i'),
                // Don't send the actual config, just indicate if it's set
                'has_config' => $settings->provider_config !== null,
            ],
            'providers' => EmailSettings::PROVIDERS,
            'smtpFields' => EmailSettings::getSmtpConfigFields(),
            'brevoFields' => EmailSettings::getBrevoConfigFields(),
            'postmarkFields' => EmailSettings::getPostmarkConfigFields(),
            'resendFields' => EmailSettings::getResendConfigFields(),
            'userEmail' => $request->user()->email,
        ]);
    }

    /**
     * Update the email provider settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::in(array_keys(EmailSettings::PROVIDERS))],
            'from_address' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'config' => 'nullable|array',
            // SMTP config validation
            'config.host' => 'required_if:provider,smtp|nullable|string|max:255',
            'config.port' => 'required_if:provider,smtp|nullable|integer|min:1|max:65535',
            'config.encryption' => 'nullable|in:tls,ssl,',
            'config.username' => 'required_if:provider,smtp|nullable|string|max:255',
            'config.password' => 'required_if:provider,smtp|nullable|string|max:255',
            // Brevo config validation
            'config.username' => 'required_if:provider,brevo|nullable|email|max:255',
            // Postmark config validation
            'config.token' => 'required_if:provider,postmark|nullable|string|max:255',
            // Resend config validation (also used by Brevo for api_key)
            'config.api_key' => 'required_if:provider,resend|required_if:provider,brevo|nullable|string|max:255',
        ]);

        $settings = EmailSettings::getOrCreate($request->user());

        $updateData = [
            'provider' => $validated['provider'],
            'from_address' => $validated['from_address'] ?? null,
            'from_name' => $validated['from_name'] ?? null,
            'provider_verified' => false, // Reset verification when config changes
        ];

        // Only update config if provider requires it and config is provided
        if ($validated['provider'] !== EmailSettings::PROVIDER_FAKTUR && !empty($validated['config'])) {
            $updateData['provider_config'] = $validated['config'];
        } elseif ($validated['provider'] === EmailSettings::PROVIDER_FAKTUR) {
            $updateData['provider_config'] = null;
        }

        $settings->update($updateData);

        return back()->with('success', __('app.email_provider_flash.updated'));
    }

    /**
     * Test the email configuration.
     */
    public function test(Request $request)
    {
        $settings = EmailSettings::getOrCreate($request->user());

        if ($settings->provider === EmailSettings::PROVIDER_FAKTUR) {
            return back()->with('success', __('app.email_provider_flash.using_default'));
        }

        if (!$settings->provider_config) {
            return back()->withErrors(['config' => 'Veuillez d\'abord configurer le provider.']);
        }

        $result = $this->emailProviderService->testConfiguration($settings);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['config' => $result['message']]);
    }

    /**
     * Validate SMTP configuration without saving.
     */
    public function validateSmtp(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'encryption' => 'nullable|in:tls,ssl,',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $result = $this->emailProviderService->validateSmtpConnection($validated);

        return response()->json($result);
    }
}
