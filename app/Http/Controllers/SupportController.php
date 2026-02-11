<?php

namespace App\Http\Controllers;

use App\Mail\NewSupportTicketNotification;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    /**
     * Display a listing of the user's tickets.
     */
    public function index(Request $request): Response
    {
        $tickets = $request->user()->supportTickets()
            ->with(['latestMessage'])
            ->withCount('messages')
            ->latest()
            ->paginate(10);

        return Inertia::render('Support/Index', [
            'tickets' => $tickets,
            'statuses' => SupportTicket::STATUSES,
        ]);
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create(): Response
    {
        return Inertia::render('Support/Create', [
            'categories' => SupportTicket::CATEGORIES,
        ]);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|in:general,technical,billing,suggestion,other',
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'reference' => SupportTicket::generateReference(),
            'subject' => $validated['subject'],
            'category' => $validated['category'],
        ]);

        $message = $ticket->messages()->create([
            'sender_type' => User::class,
            'sender_id' => $request->user()->id,
            'content' => $validated['message'],
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('support-attachments', 'public');

            $message->attachments()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        // Send notification email to admin
        $adminEmail = config('admin.support_email');
        if ($adminEmail) {
            $ticket->load('user');
            Mail::to($adminEmail)->send(new NewSupportTicketNotification($ticket, $validated['message']));
        }

        return redirect()
            ->route('support.show', $ticket)
            ->with('success', 'Votre demande a été envoyée. Référence : ' . $ticket->reference);
    }

    /**
     * Display the specified ticket.
     */
    public function show(SupportTicket $ticket): Response
    {
        $this->authorize('view', $ticket);

        // Mark as read when user views the ticket
        $ticket->markAsRead();

        $ticket->load([
            'messages' => function ($query) {
                $query->where('is_internal', false)
                    ->with(['sender', 'attachments'])
                    ->oldest();
            },
        ]);

        return Inertia::render('Support/Show', [
            'ticket' => $ticket,
            'statuses' => SupportTicket::STATUSES,
            'canReply' => $ticket->isOpen(),
        ]);
    }

    /**
     * Add a reply to the ticket.
     */
    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $message = $ticket->messages()->create([
            'sender_type' => User::class,
            'sender_id' => $request->user()->id,
            'content' => $validated['message'],
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('support-attachments', 'public');

            $message->attachments()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        // Reopen the ticket if it was waiting or resolved
        if (in_array($ticket->status, ['waiting', 'resolved'])) {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', 'Réponse envoyée.');
    }
}
