<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
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

        $subscriber = NewsletterSubscriber::subscribe($validated['email'], $locale, $source);

        if (!$subscriber->isConfirmed()) {
            Mail::to($subscriber->email)->send(new NewsletterConfirmation($subscriber));
        }

        return back()->with('newsletter_success', true);
    }

    public function confirm(string $token)
    {
        $subscriber = NewsletterSubscriber::where('confirm_token', $token)->first();

        if (!$subscriber) {
            return redirect('/')->with('error', 'Lien de confirmation invalide.');
        }

        $subscriber->confirm();

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
}
