<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientContactController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('client_contact.browse')) {
            abort(403, 'Unauthorized to browse client contacts.');
        }

        $companyId = auth()->user()->preference->company_id;

        $query = ClientContact::where('company_id', $companyId);

        // --- Search by name or email ---
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // --- Filter by primary contact ---
        if ($request->filled('is_primary')) {
            $isPrimary = $request->is_primary == '1' ? 1 : 0;
            $query->where('is_primary', $isPrimary);
        }

        // --- Sorting ---
        $allowedSorts = ['name', 'email', 'created_at'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts) ? $request->get('sort_by') : 'name';

        $query->orderBy($sortBy);

        // --- Pagination ---
        $contacts = $query->paginate(10)->appends($request->query());

        return view('client_contacts.index', compact('contacts'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('client_contact.create')) {
            abort(403, 'Unauthorized to create client contacts.');
        }

        $companyId = auth()->user()->preference->company_id;

        $clients = Client::where('company_id', $companyId)->get();

        return view('client_contacts.create', compact('clients'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('client_contact.create')) {
            abort(403, 'Unauthorized to create client contacts.');
        }

        $validated = $request->validate([
            'client_id'    => 'required|exists:clients,id',
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:20',
            'position'     => 'nullable|string|max:255',
            'is_primary'   => 'boolean',
            'linkedin_url' => 'nullable|url|max:255',
        ]);

        $companyId = auth()->user()->preference->company_id;

        $client = Client::where('id', $validated['client_id'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            $contact = ClientContact::create([
                'company_id'   => $companyId,
                'client_id'    => $client->id,
                'name'         => $validated['name'],
                'email'        => $validated['email'] ?? null,
                'phone'        => $validated['phone'] ?? null,
                'position'     => $validated['position'] ?? null,
                'is_primary'   => $validated['is_primary'] ?? false,
                'linkedin_url' => $validated['linkedin_url'] ?? null,
            ]);

            DB::commit();

            $this->logAudit('created', $contact, ['after' => $contact->toArray()], 'Client Contact creation', 'client_contacts.create');

            return redirect()->route('client_contacts.index')->with('success', 'Contact created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create client contact', [
                'error'     => $e->getMessage(),
                'user_id'   => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the contact.');
        }
    }

    public function show(ClientContact $clientContact)
    {
        $this->authorizeContact($clientContact);

        if (!auth()->user()->hasPermission('client_contact.read')) {
            abort(403, 'Unauthorized to view client contact.');
        }

        return view('client_contacts.show', compact('clientContact'));
    }

    public function edit(ClientContact $clientContact)
    {
        $this->authorizeContact($clientContact);

        if (!auth()->user()->hasPermission('client_contact.update')) {
            abort(403, 'Unauthorized to edit client contact.');
        }

        $companyId = auth()->user()->preference->company_id;
        $client = $clientContact->client;

        return view('client_contacts.edit', compact('clientContact', 'client'));
    }

    public function update(Request $request, ClientContact $clientContact)
    {
        $this->authorizeContact($clientContact);

        if (!auth()->user()->hasPermission('client_contact.update')) {
            abort(403, 'Unauthorized to update client contact.');
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:20',
            'position'     => 'nullable|string|max:255',
            'is_primary'   => 'boolean',
            'linkedin_url' => 'nullable|url|max:255',
        ]);

        DB::beginTransaction();

        try {
            $original = $clientContact->getOriginal();

            $clientContact->update($validated);

            DB::commit();

            $this->logAudit('updated', $clientContact, [
                'before' => $original,
                'after'  => $clientContact->fresh()->toArray(),
            ], 'Client Contact update', 'client_contacts.edit');

            return redirect()->route('client_contacts.index')->with('success', 'Client contact updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update client contact', [
                'error'         => $e->getMessage(),
                'client_contact_id' => $clientContact->id,
                'user_id'       => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the client contact.');
        }
    }

    public function destroy(ClientContact $clientContact)
    {
        $this->authorizeContact($clientContact);

        if (!auth()->user()->hasPermission('client_contact.delete')) {
            abort(403, 'Unauthorized to delete client contact.');
        }

        DB::beginTransaction();

        try {
            $contactData = $clientContact->toArray();
            $clientContact->delete();

            DB::commit();

            $this->logAudit('deleted', $clientContact, ['before' => $contactData], 'Client Contact deletion', 'client_contacts.index');

            return redirect()->route('client_contacts.index')->with('success', 'Client contact deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete client contact', [
                'error'   => $e->getMessage(),
                'client_contact_id' => $clientContact->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the client contact.');
        }
    }

    protected function authorizeContact(ClientContact $contact)
    {
        if ($contact->company_id !== auth()->user()->preference->company_id) {
            abort(403, 'Unauthorized access to this contact.');
        }
    }

    protected function logAudit($action, $model, $changes = null, $context = null, $origin = null)
    {
        \App\Models\AuditLog::create([
            'company_id'    => auth()->user()->preference->company_id,
            'action'        => $action,
            'model_type'    => get_class($model),
            'model_id'      => $model->id,
            'changes'       => $changes,
            'performed_by'  => auth()->id(),
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'context'       => $context,
            'origin_screen' => $origin,
        ]);
    }
}
