<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAssign;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketAssignController_01 extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasPermission('ticket_assignment.browse')) {
            // abort(403, 'Unauthorized to browse ticket assignments.');
        }

        $companyId = $user->preference->company_id;

        $query = TicketAssign::with(['ticket', 'assignedTo', 'assignedBy'])
            ->whereHas('ticket', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });

        if ($request->filled('ticket_id')) {
            $query->where('ticket_id', $request->ticket_id);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
  
        
        if ($request->filled('is_current')) {
            $query->where('is_current', $request->is_current);
        }

        $TicketAssigns = $query->latest()
            ->paginate(10)
            ->appends($request->query());

        return view('ticket_assignments.index', compact('TicketAssigns'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('ticket_assignment.create')) {
            // abort(403, 'Unauthorized to create ticket assignments.');
        }

        $companyId = auth()->user()->preference->company_id;

        $tickets = Ticket::where('company_id', $companyId)->get();
        $users = User::all();

        return view('ticket_assignments.create', compact('tickets', 'users'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('ticket_assignment.create')) {
            // abort(403, 'Unauthorized to create ticket assignments.');
        }

        $validated = $request->validate([
            'ticket_id'    => 'required|exists:tickets,id',
            'assigned_to'  => 'required|exists:users,id',
            'assigned_at'  => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);
        $this->authorizeCompany($ticket->company_id);

        // Close previous current assignment
        TicketAssign::where('ticket_id', $ticket->id)
            ->where('is_current', true)
            ->update([
                'is_current'    => false,
                'unassigned_at' => now(),
            ]);

        $TicketAssign = TicketAssign::create([
            'ticket_id'      => $ticket->id,
            'assigned_to'    => $validated['assigned_to'],
            'assigned_by'    => auth()->id(),
            'assigned_at'    => $validated['assigned_at'] ?? now(),
            'is_current'     => true,
            'notes'          => $validated['notes'] ?? null,
        ]);

        // Update current assignment in tickets table
        $ticket->update([
            'assigned_to'      => $validated['assigned_to'],
            'assigned_by'      => auth()->id(),
            'assigned_at'      => $validated['assigned_at'] ?? now(),
            'last_activity_at' => now(),
        ]);

        // Optional: auto move to in_progress when assigned
        if ($ticket->status === 'open' || $ticket->status === 'approved') {
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => 'in_progress',
            ]);

            TicketActivity::create([
                'ticket_id'    => $ticket->id,
                'user_id'      => auth()->id(),
                'action'       => 'status_changed',
                'field'        => 'status',
                'old_value'    => $oldStatus,
                'new_value'    => 'in_progress',
                'description'  => "Status changed from {$oldStatus} to in_progress due to assignment.",
            ]);
        }

        TicketActivity::create([
            'ticket_id'    => $ticket->id,
            'user_id'      => auth()->id(),
            'action'       => 'assigned',
            'field'        => 'assigned_to',
            'old_value'    => null,
            'new_value'    => (string) $validated['assigned_to'],
            'description'  => 'Ticket assigned to user ID ' . $validated['assigned_to'],
        ]);

        Log::info('Ticket assigned', [
            'ticket_assignment_id' => $TicketAssign->id,
            'ticket_id'            => $ticket->id,
            'assigned_to'          => $validated['assigned_to'],
            'user_id'              => auth()->id(),
        ]);

        return redirect()->route('ticket_assignments.index')
            ->with('status', 'Ticket assigned successfully.');
    }

    public function show(TicketAssign $TicketAssign)
    {
        $TicketAssign->load(['ticket', 'assignedTo', 'assignedBy']);
        $this->authorizeCompany($TicketAssign->ticket->company_id);

        if (!auth()->user()->hasPermission('ticket_assignment.read')) {
            // abort(403, 'Unauthorized to view ticket assignments.');
        }

        return view('ticket_assignments.show', compact('TicketAssign'));
    }

    public function edit(TicketAssign $TicketAssign)
    {
        $TicketAssign->load('ticket');
        $this->authorizeCompany($TicketAssign->ticket->company_id);

        if (!auth()->user()->hasPermission('ticket_assignment.update')) {
            // abort(403, 'Unauthorized to edit ticket assignments.');
        }

        $companyId = auth()->user()->preference->company_id;

        $tickets = Ticket::where('company_id', $companyId)->get();
        $users = User::all();

        return view('ticket_assignments.edit', compact('TicketAssign', 'tickets', 'users'));
    }

    public function update(Request $request, TicketAssign $TicketAssign)
    {
        $TicketAssign->load('ticket');
        $this->authorizeCompany($TicketAssign->ticket->company_id);

        if (!auth()->user()->hasPermission('ticket_assignment.update')) {
            // abort(403, 'Unauthorized to update ticket assignments.');
        }

        $validated = $request->validate([
            'assigned_to'   => 'required|exists:users,id',
            'assigned_at'   => 'nullable|date',
            'unassigned_at' => 'nullable|date',
            'is_current'    => 'nullable|boolean',
            'notes'         => 'nullable|string',
        ]);

        $ticket = $TicketAssign->ticket;
        $oldAssignedTo = $TicketAssign->assigned_to;

        // If setting this assignment as current, close others
        if (!empty($validated['is_current'])) {
            TicketAssign::where('ticket_id', $ticket->id)
                ->where('id', '!=', $TicketAssign->id)
                ->where('is_current', true)
                ->update([
                    'is_current'    => false,
                    'unassigned_at' => now(),
                ]);
        }

        $TicketAssign->update([
            'assigned_to'   => $validated['assigned_to'],
            'assigned_at'   => $validated['assigned_at'] ?? $TicketAssign->assigned_at,
            'unassigned_at' => $validated['unassigned_at'] ?? $TicketAssign->unassigned_at,
            'is_current'    => $validated['is_current'] ?? $TicketAssign->is_current,
            'notes'         => $validated['notes'] ?? $TicketAssign->notes,
            'assigned_by'   => auth()->id(),
        ]);

        // Sync current assignment to tickets table
        if ($TicketAssign->is_current) {
            $ticket->update([
                'assigned_to'      => $TicketAssign->assigned_to,
                'assigned_by'      => auth()->id(),
                'assigned_at'      => $TicketAssign->assigned_at ?? now(),
                'last_activity_at' => now(),
            ]);
        }

        TicketActivity::create([
            'ticket_id'    => $ticket->id,
            'user_id'      => auth()->id(),
            'action'       => 'assignment_updated',
            'field'        => 'assigned_to',
            'old_value'    => (string) $oldAssignedTo,
            'new_value'    => (string) $validated['assigned_to'],
            'description'  => 'Ticket assignment updated.',
        ]);

        Log::info('Ticket assignment updated', [
            'ticket_assignment_id' => $TicketAssign->id,
            'ticket_id'            => $ticket->id,
            'user_id'              => auth()->id(),
        ]);

        return redirect()->route('ticket_assignments.index')
            ->with('status', 'Ticket assignment updated successfully.');
    }

    public function destroy(TicketAssign $TicketAssign)
    {
        $TicketAssign->load('ticket');
        $this->authorizeCompany($TicketAssign->ticket->company_id);

        if (!auth()->user()->hasPermission('ticket_assignment.delete')) {
            // abort(403, 'Unauthorized to delete ticket assignments.');
        }

        $ticketId = $TicketAssign->ticket_id;
        $ticket = $TicketAssign->ticket;

        if ($TicketAssign->is_current) {
            $ticket->update([
                'assigned_to'      => null,
                'assigned_by'      => null,
                'assigned_at'      => null,
                'last_activity_at' => now(),
            ]);
        }

        TicketActivity::create([
            'ticket_id'    => $ticketId,
            'user_id'      => auth()->id(),
            'action'       => 'assignment_deleted',
            'field'        => 'assigned_to',
            'old_value'    => (string) $TicketAssign->assigned_to,
            'new_value'    => null,
            'description'  => 'Ticket assignment deleted.',
        ]);

        $TicketAssign->delete();

        Log::info('Ticket assignment deleted', [
            'ticket_assignment_id' => $TicketAssign->id,
            'ticket_id'            => $ticketId,
            'user_id'              => auth()->id(),
        ]);

        return redirect()->route('ticket_assignments.index')
            ->with('status', 'Ticket assignment deleted successfully.');
    }

    protected function authorizeCompany($companyId)
    {
        $user = auth()->user();

        if ($user->preference->company_id != $companyId || !$user->companies->contains('id', $companyId)) {
            abort(403, 'Unauthorized company access.');
        }
    }
}