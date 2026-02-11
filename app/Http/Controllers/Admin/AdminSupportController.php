<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSession;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\AdminAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSupportController extends Controller
{
    public function __construct(
        protected AdminAuthService $adminAuth
    ) {}
    /**
     * Display a listing of all tickets.
     */
    public function index(Request $request): Response
    {
        $query = SupportTicket::with(['user:id,name,email'])
            ->withCount('messages');

        // Apply filters
        $query->status($request->get('status'))
            ->category($request->get('category'))
            ->priority($request->get('priority'))
            ->search($request->get('search'));

        // Filter by period
        if ($period = $request->get('period')) {
            $query->when($period === 'today', fn($q) => $q->whereDate('created_at', today()))
                ->when($period === 'week', fn($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                ->when($period === 'month', fn($q) => $q->whereMonth('created_at', now()->month));
        }

        $tickets = $query->latest()->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'new' => SupportTicket::where('status', 'new')->count(),
            'open' => SupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
            'waiting' => SupportTicket::where('status', 'waiting')->count(),
            'resolved_today' => SupportTicket::where('status', 'resolved')
                ->whereDate('resolved_at', today())->count(),
            'total' => SupportTicket::count(),
        ];

        return Inertia::render('Admin/Support/Index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'filters' => $request->only(['status', 'category', 'priority', 'period', 'search']),
            'categories' => SupportTicket::CATEGORIES,
            'statuses' => SupportTicket::STATUSES,
            'priorities' => SupportTicket::PRIORITIES,
        ]);
    }

    /**
     * Display the specified ticket.
     */
    public function show(SupportTicket $ticket): Response
    {
        $ticket->load([
            'user',
            'messages' => fn($q) => $q->with(['sender', 'attachments'])->oldest(),
        ]);

        return Inertia::render('Admin/Support/Show', [
            'ticket' => $ticket,
            'categories' => SupportTicket::CATEGORIES,
            'statuses' => SupportTicket::STATUSES,
            'priorities' => SupportTicket::PRIORITIES,
        ]);
    }

    /**
     * Add a reply to the ticket.
     */
    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'is_internal' => 'boolean',
            'status' => 'nullable|in:new,open,in_progress,waiting,resolved,closed',
        ]);

        $adminSession = $this->adminAuth->getSession($request);

        $ticket->messages()->create([
            'sender_type' => AdminSession::class,
            'sender_id' => $adminSession?->id ?? 0,
            'content' => $validated['message'],
            'is_internal' => $validated['is_internal'] ?? false,
        ]);

        // Track first response time
        if (!$ticket->first_response_at && !($validated['is_internal'] ?? false)) {
            $ticket->first_response_at = now();
        }

        // Update status
        if ($validated['status'] ?? null) {
            $ticket->status = $validated['status'];
            if ($validated['status'] === 'resolved') {
                $ticket->resolved_at = now();
            }
        } else {
            $ticket->status = 'in_progress';
        }

        $ticket->save();

        return back()->with('success', 'Réponse envoyée.');
    }

    /**
     * Update ticket status and/or priority.
     */
    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:new,open,in_progress,waiting,resolved,closed',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'assigned_admin_id' => 'nullable|integer',
        ]);

        if (isset($validated['status'])) {
            $ticket->status = $validated['status'];
            if ($validated['status'] === 'resolved' && !$ticket->resolved_at) {
                $ticket->resolved_at = now();
            }
        }

        if (isset($validated['priority'])) {
            $ticket->priority = $validated['priority'];
        }

        if (array_key_exists('assigned_admin_id', $validated)) {
            $ticket->assigned_admin_id = $validated['assigned_admin_id'];
        }

        $ticket->save();

        return back()->with('success', 'Ticket mis à jour.');
    }

    /**
     * Delete the specified ticket.
     */
    public function destroy(SupportTicket $ticket): RedirectResponse
    {
        $ticket->delete();

        return redirect()
            ->route('admin.support.index')
            ->with('success', 'Ticket supprimé.');
    }
}
