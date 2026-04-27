<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\TicketActivity;
use App\Models\TicketAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasPermission('ticket.browse')) {
            // abort(403);
        }

        $companyId = $user->preference->company_id;

        $query = Ticket::with(['ticketType', 'assignedTo'])
            ->where('company_id', $companyId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->latest()
                         ->paginate(10)
                         ->appends($request->query());

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('ticket.create')) {
            // abort(403);
        }

        $ticketTypes = TicketType::where('company_id', auth()->user()->preference->company_id)
            ->where('is_active', true)
            ->get();

        $users = User::all(); // for assignment

        return view('tickets.create', compact('ticketTypes', 'users'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('ticket.create')) {
            // abort(403);
        }

        $validated = $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'name'           => 'required|string|max:255',
            'subject'        => 'required|string|max:255',
            'description'    => 'nullable|string',
            'priority'       => 'required|in:low,medium,high,urgent',
            'severity'       => 'required|in:low,medium,high',
            'assigned_to'    => 'nullable|exists:users,id',
        ]);

        $validated['company_id'] = auth()->user()->preference->company_id;
        $validated['created_by'] = auth()->id();

        // Generate ticket number
        $validated['ticket_number'] = 'TCK-' . strtoupper(Str::random(8));

        $ticket = Ticket::create($validated);

        // Activity log
        $this->logActivity($ticket->id, 'created', 'Ticket created');

        // Assignment
        if (!empty($validated['assigned_to'])) {
            $this->assignTicket($ticket, $validated['assigned_to']);
        }

        Log::info('Ticket created', [
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
        ]);

        return redirect()->route('tickets.index')
            ->with('status', 'Ticket created successfully.');
    }

    public function show(Ticket $ticket)
    {
        $this->authorizeCompany($ticket->company_id);

        if (!auth()->user()->hasPermission('ticket.read')) {
            // abort(403);
        }

        $ticket->load([
            'ticketType',
            'assignedTo',
            'comments.user',
            'activities',
            'ratings'
        ]);

        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        $this->authorizeCompany($ticket->company_id);

        if (!auth()->user()->hasPermission('ticket.update')) {
            // abort(403);
        }

        $ticketTypes = TicketType::where('company_id', $ticket->company_id)->get();
        $users = User::all();

        return view('tickets.edit', compact('ticket', 'ticketTypes', 'users'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorizeCompany($ticket->company_id);

        if (!auth()->user()->hasPermission('ticket.update')) {
            // abort(403);
        }

        $validated = $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'subject'        => 'required|string|max:255',
            'description'    => 'nullable|string',
            'priority'       => 'required|in:low,medium,high,urgent',
            'severity'       => 'required|in:low,medium,high',
            'status'         => 'required',
            'assigned_to'    => 'nullable|exists:users,id',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update($validated);

        // Status change activity
        if ($oldStatus !== $validated['status']) {
            $this->logActivity(
                $ticket->id,
                'status_changed',
                "Status changed from {$oldStatus} to {$validated['status']}"
            );
        }

        // Assignment update
        if (!empty($validated['assigned_to'])) {
            $this->assignTicket($ticket, $validated['assigned_to']);
        }

        // Resolution logic
        if ($validated['status'] === 'resolved') {
            $ticket->update(['resolved_at' => now()]);
        }

        if ($validated['status'] === 'closed') {
            $ticket->update(['closed_at' => now()]);
        }

        Log::info('Ticket updated', [
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('status', 'Ticket updated successfully.');
    }

    public function destroy(Ticket $ticket)
    {
        $this->authorizeCompany($ticket->company_id);

        if (!auth()->user()->hasPermission('ticket.delete')) {
            // abort(403);
        }

        $ticket->delete();

        Log::info('Ticket deleted', [
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
        ]);

        return redirect()->route('tickets.index')
            ->with('status', 'Ticket deleted successfully.');
    }

    /*
    |--------------------------------
    | ASSIGNMENT LOGIC
    |--------------------------------
    */
    protected function assignTicket(Ticket $ticket, $userId)
    {
        // Close previous assignment
        TicketAssignment::where('ticket_id', $ticket->id)
            ->where('is_current', true)
            ->update([
                'is_current'    => false,
                'unassigned_at' => now(),
            ]);

        // New assignment
        TicketAssignment::create([
            'ticket_id'   => $ticket->id,
            'assigned_to' => $userId,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
            'is_current'  => true,
        ]);

        $ticket->update([
            'assigned_to' => $userId,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        $this->logActivity($ticket->id, 'assigned', "Assigned to user ID {$userId}");
    }

    /*
    |--------------------------------
    | ACTIVITY LOGGER
    |--------------------------------
    */
    protected function logActivity($ticketId, $action, $description = null)
    {
        TicketActivity::create([
            'ticket_id'  => $ticketId,
            'user_id'    => auth()->id(),
            'action'     => $action,
            'description'=> $description,
        ]);
    }

    protected function authorizeCompany($companyId)
    {
        $user = auth()->user();

        if (
            $user->preference->company_id != $companyId ||
            !$user->companies->contains('id', $companyId)
        ) {
            abort(403, 'Unauthorized company access.');
        }
    }
}