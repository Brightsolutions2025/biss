<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketAssignmentController extends Controller
{
    public function edit($id)
    {
        $ticket = Ticket::findOrFail($id);

        // Only department head or authorized users can assign
        $this->authorize('assign', $ticket);

        // Get potential assignees (same department)
        $assignees = User::where('department_id', $ticket->department_id)->get();

        return view('tickets.assign', compact('ticket', 'assignees'));
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $this->authorize('assign', $ticket);

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $ticket->update([
            'assigned_to' => $request->assigned_to,
            'assigned_by' => Auth::id(),
            'assigned_at' => now(),
            'status'      => 'in_progress', // optional status change
        ]);

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Ticket assigned successfully.');
    }
}
