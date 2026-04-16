<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Display the contact page.
     */
    public function index(): Response
    {
        return Inertia::render('Contact');
    }

    /**
     * Display the about page.
     */
    public function about(): Response
    {
        return Inertia::render('About');
    }

    /**
     * Display the partners page.
     */
    public function partners(): Response
    {
        return Inertia::render('Partners');
    }

    /**
     * Handle partner contact form submission.
     */
    public function partnerContact(Request $request)
    {
        $validated = $request->validate([
            'company' => 'required|string|max:150',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'clients_count' => 'required|string|max:50',
            'message' => 'nullable|string|max:5000',
        ]);

        $body = "Nouveau contact partenaire fiduciaire\n\n"
            . "Cabinet: {$validated['company']}\n"
            . "Nom: {$validated['name']}\n"
            . "Email: {$validated['email']}\n"
            . "Nombre de clients: {$validated['clients_count']}\n\n"
            . "Message:\n" . ($validated['message'] ?? 'Aucun message');

        Mail::raw($body, function ($mail) use ($validated) {
            $mail->to(config('mail.from.address'))
                ->replyTo($validated['email'], $validated['name'])
                ->subject("[faktur.lu Partenaire] {$validated['company']}");
        });

        return back()->with('success', true);
    }

    /**
     * Handle contact form submission.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:5000',
        ]);

        Mail::raw(
            "Nom: {$validated['name']}\nEmail: {$validated['email']}\nSujet: {$validated['subject']}\n\n{$validated['message']}",
            function ($mail) use ($validated) {
                $mail->to(config('mail.from.address'))
                    ->replyTo($validated['email'], $validated['name'])
                    ->subject("[faktur.lu Contact] {$validated['subject']}");
            }
        );

        return back()->with('success', true);
    }
}
