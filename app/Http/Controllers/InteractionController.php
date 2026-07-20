<?php

namespace App\Http\Controllers;

use App\Mail\CrmEmail;
use App\Models\Client;
use App\Models\Interaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InteractionController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        $rules = [
            'type' => ['required', 'string', 'in:' . implode(',', Interaction::TYPES)],
            'content' => ['required', 'string', 'max:5000'],
            'contacted_at' => ['required', 'date'],
            'send_email' => ['sometimes', 'boolean'],
            'subject' => ['required_if:send_email,true', 'nullable', 'string', 'max:255'],
        ];

        $validated = $request->validate($rules);

        $sendEmail = $validated['type'] === 'email'
            && !empty($validated['send_email'])
            && !empty($client->email);

        $contentToStore = $validated['content'];
        if ($sendEmail && !empty($validated['subject'])) {
            $contentToStore = "<p><strong>[{$validated['subject']}]</strong></p>{$validated['content']}";
        }

        $client->interactions()->create([
            'type' => $validated['type'],
            'content' => $contentToStore,
            'contacted_at' => $validated['contacted_at'],
        ]);

        if ($sendEmail) {
            try {
                $user = Auth::user();

                Mail::to($client->email)->send(new CrmEmail(
                    client: $client,
                    emailSubject: $validated['subject'],
                    emailBody: $validated['content'],
                    senderName: $user?->name,
                    senderEmail: $user?->email,
                ));

                Log::info("CRM email sent to {$client->email} - subject: {$validated['subject']}");

                return back()->with('success', __('app.interactions_flash.email_sent', ['email' => $client->email]));
            } catch (\Exception $e) {
                Log::error("CRM email failed to {$client->email}: {$e->getMessage()}");

                return back()->with('error', \App\Support\UserError::report($e, 'crm.email'));
            }
        }

        return back()->with('success', __('app.interactions_flash.created'));
    }

    public function update(Request $request, Interaction $interaction): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', Interaction::TYPES)],
            'content' => ['required', 'string', 'max:5000'],
            'contacted_at' => ['required', 'date'],
        ]);

        $interaction->update($validated);

        return back()->with('success', __('app.interactions_flash.updated'));
    }

    public function destroy(Interaction $interaction): RedirectResponse
    {
        $interaction->delete();

        return back()->with('success', __('app.interactions_flash.deleted'));
    }
}
