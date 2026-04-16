<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $locale = app()->getLocale() ?: 'fr';
        $source = $request->input('source', 'footer');
        $email = $validated['email'];

        $subscriber = NewsletterSubscriber::subscribe($email, $locale, $source);

        // Send confirmation email via Laravel (not Brevo DOI)
        if (!$subscriber->isConfirmed()) {
            Mail::to($email)->send(new NewsletterConfirmation($subscriber));
        }

        return back()->with('success', 'newsletter');
    }

    /**
     * Confirm subscription via token link in email.
     */
    public function confirm(string $token)
    {
        $subscriber = NewsletterSubscriber::where('confirm_token', $token)->first();

        if (!$subscriber) {
            return redirect(config('app.url'))->with('error', 'Lien invalide.');
        }

        $subscriber->confirm();

        // Now add confirmed contact to Brevo
        $this->addToBrevo($subscriber->email, $subscriber->locale, $subscriber->source);

        return view('emails.newsletter-confirmed');
    }

    public function unsubscribe(string $email, string $hash)
    {
        if (hash('sha256', $email . config('app.key')) !== $hash) {
            abort(403);
        }

        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return view('emails.newsletter-unsubscribed');
    }

    protected function addToBrevo(string $email, string $locale, string $source): void
    {
        $apiKey = config('services.brevo.api_key');
        $listId = (int) config('services.brevo.newsletter_list_id');

        if (!$apiKey || !$listId) {
            Log::warning('Brevo API key or list ID not configured');
            return;
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.brevo.com/v3/contacts', [
                'email' => $email,
                'listIds' => [$listId],
                'attributes' => [
                    'LOCALE' => $locale,
                    'SOURCE' => $source,
                ],
                'updateEnabled' => true,
            ]);

            if ($response->failed()) {
                Log::warning("Brevo API error: {$response->status()} - {$response->body()}");
            }
        } catch (\Exception $e) {
            Log::error("Brevo API exception: {$e->getMessage()}");
        }
    }
}
