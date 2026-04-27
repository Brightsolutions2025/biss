<?php

namespace App\Http\Controllers;

use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketTypeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasPermission('ticket_type.browse')) {
            // abort(403, 'Unauthorized to browse ticket types.');
        }

        $companyId = $user->preference->company_id;

        $query = TicketType::where('company_id', $companyId);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $ticketTypes = $query->orderBy('sort_order')
            ->paginate(10)
            ->appends($request->query());

        return view('ticket_types.index', compact('ticketTypes'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('ticket_type.create')) {
            // abort(403, 'Unauthorized to create ticket types.');
        }

        return view('ticket_types.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('ticket_type.create')) {
            // abort(403, 'Unauthorized to create ticket types.');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['company_id'] = auth()->user()->preference->company_id;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active']  = $validated['is_active'] ?? true;

        $ticketType = TicketType::create($validated);

        Log::info('Ticket type created', [
            'ticket_type_id' => $ticketType->id,
            'user_id'        => auth()->id(),
        ]);

        return redirect()->route('ticket_types.index')
            ->with('status', 'Ticket type created successfully.');
    }

    public function show(TicketType $ticketType)
    {
        $this->authorizeCompany($ticketType->company_id);

        if (!auth()->user()->hasPermission('ticket_type.read')) {
            // abort(403, 'Unauthorized to view ticket types.');
        }

        return view('ticket_types.show', compact('ticketType'));
    }

    public function edit(TicketType $ticketType)
    {
        $this->authorizeCompany($ticketType->company_id);

        if (!auth()->user()->hasPermission('ticket_type.update')) {
            // abort(403, 'Unauthorized to edit ticket types.');
        }

        return view('ticket_types.edit', compact('ticketType'));
    }

    public function update(Request $request, TicketType $ticketType)
    {
        $this->authorizeCompany($ticketType->company_id);

        if (!auth()->user()->hasPermission('ticket_type.update')) {
            // abort(403, 'Unauthorized to update ticket types.');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'nullable|boolean',
        ]);

        $ticketType->update($validated);

        Log::info('Ticket type updated', [
            'ticket_type_id' => $ticketType->id,
            'user_id'        => auth()->id(),
        ]);

        return redirect()->route('ticket_types.index')
            ->with('status', 'Ticket type updated successfully.');
    }

    public function destroy(TicketType $ticketType)
    {
        $this->authorizeCompany($ticketType->company_id);

        if (!auth()->user()->hasPermission('ticket_type.delete')) {
            // abort(403, 'Unauthorized to delete ticket types.');
        }

        $ticketType->delete();

        Log::info('Ticket type deleted', [
            'ticket_type_id' => $ticketType->id,
            'user_id'        => auth()->id(),
        ]);

        return redirect()->route('ticket_types.index')
            ->with('status', 'Ticket type deleted successfully.');
    }

    protected function authorizeCompany($companyId)
    {
        $user = auth()->user();

        if ($user->preference->company_id != $companyId || !$user->companies->contains('id', $companyId)) {
            abort(403, 'Unauthorized company access.');
        }
    }
}