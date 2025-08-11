<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class TicketController extends Controller
{
    public function index(Request $request)
    {
    $user = auth()->user();
    $companyId = $user->preference->company_id;

    $query = Ticket::with(['ticketType', 'company', 'department', 'team'])
        ->where('company_id', $companyId);

    if ($request->filled('subject')) {
        $query->where('subject', 'like', '%' . $request->subject . '%');
    }

    $tickets = $query->orderByDesc('created_at')->paginate(10);

    return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('ticket.create')) {
            // abort(403, 'Unauthorized to create tickets.');
        }

        $ticketTypes = TicketType::where('company_id', auth()->user()->preference->company_id)
                                 ->where('is_active', true)
                                 ->orderBy('sort_order')
                                 ->get();

        return view('tickets.create', compact('ticketTypes'));
    }

public function store(Request $request)
{
    if (!auth()->user()->hasPermission('ticket.create')) {
        // abort(403, 'Unauthorized to create tickets.');
    }

    $validated = $request->validate([
        'ticket_type_id' => 'required|exists:ticket_types,id',
        'subject'        => 'required|string|max:255',
        'description'    => 'nullable|string',
        'priority'       => 'required|in:low,medium,high,urgent',
        'due_at'         => 'nullable|date',
        'attachments'    => 'nullable|array',
    ]);

    $user = auth()->user();
    $companyId = $user->preference->company_id;

    DB::transaction(function () use (&$ticket, $validated, $user, $companyId) {
        $validated['company_id']     = $companyId;
        $validated['department_id']  = $user->preference->department_id;
        $validated['team_id']        = $user->preference->team_id;
        $validated['created_by']     = $user->id;

        // Retry until unique ticket_number is found
        do {
            $ticketNumber = Ticket::generateTicketNumber();
        } while (Ticket::where('ticket_number', $ticketNumber)->exists());

        $validated['ticket_number'] = $ticketNumber;

        $ticket = Ticket::create($validated);

        Log::info('Ticket created', [
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
        ]);
    });

    return redirect()->route('tickets.index')->with('status', 'Ticket created successfully.');
}

    public function show(Ticket $ticket)
    {
        $this->authorizeCompany($ticket->company_id);

        if (!auth()->user()->hasPermission('ticket.read')) {
            // abort(403, 'Unauthorized to view tickets.');
        }

        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        $this->authorizeCompany($ticket->company_id);

        if (!auth()->user()->hasPermission('ticket.update')) {
            // abort(403, 'Unauthorized to edit tickets.');
        }

        $ticketTypes = TicketType::where('company_id', auth()->user()->preference->company_id)
                                 ->where('is_active', true)
                                 ->orderBy('sort_order')
                                 ->get();

        return view('tickets.edit', compact('ticket', 'ticketTypes'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorizeCompany($ticket->company_id);

        if (!auth()->user()->hasPermission('ticket.update')) {
            // abort(403, 'Unauthorized to update tickets.');
        }

        $validated = $request->validate([
            'ticket_type_id' => 'exists:ticket_types,id',
            'subject'        => 'required|string|max:255',
            'description'    => 'nullable|string',
            'priority'       => 'required|in:low,medium,high,urgent',
            'status'         => 'in:open,pending_approval,approved,in_progress,resolved,closed,rejected',
            'due_at'         => 'nullable|date',
            'resolved_at'    => 'nullable|date',
        ]);

        $ticket->update($validated);

        Log::info('Ticket updated', [
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
        ]);

        return redirect()->route('tickets.index')->with('status', 'Ticket updated successfully.');
    }

    public function destroy(Ticket $ticket)
    {
        $this->authorizeCompany($ticket->company_id);

        if (!auth()->user()->hasPermission('ticket.delete')) {
            // abort(403, 'Unauthorized to delete tickets.');
        }

        $ticket->delete();

        Log::info('Ticket deleted', [
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
        ]);

        return redirect()->route('tickets.index')->with('status', 'Ticket deleted successfully.');
    }

    protected function authorizeCompany($companyId)
    {
        $userCompanyId = auth()->user()->preference->company_id;

        if ($userCompanyId != $companyId || !auth()->user()->companies->contains('id', $companyId)) {
            abort(403, 'Unauthorized company access.');
        }
    }
}