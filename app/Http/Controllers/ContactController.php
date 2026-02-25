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
