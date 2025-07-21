<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('client.browse')) {
            abort(403, 'Unauthorized to browse clients.');
        }

        $company = auth()->user()->preference->company;

        $query = Client::where('company_id', $company->id);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('industry')) {
            $query->where('industry', 'like', '%' . $request->industry . '%');
        }

        $clients = $query->paginate(10)->appends($request->query());

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('client.create')) {
            abort(403, 'Unauthorized to create clients.');
        }

        return view('clients.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('client.create')) {
            abort(403, 'Unauthorized to create clients.');
        }

        $companyId = auth()->user()->preference->company_id;

        $request->merge([
            'is_active' => $request->has('is_active'),
        ]);

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'contact_person'  => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:20',
            'address'         => 'nullable|string',
            'billing_address' => 'nullable|string',
            'industry'        => 'nullable|string|max:255',
            'tin'             => 'nullable|string|max:255',
            'category'        => 'nullable|string|max:255',
            'client_type'     => 'nullable|in:corporate,government,individual',
            'website'         => 'nullable|url|max:255',
            'notes'           => 'nullable|string',
            'rating'          => 'nullable|integer|min:1|max:5',
            'is_active'       => 'boolean',
            'payment_terms'   => 'nullable|string|max:255',
            'credit_limit'    => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $client = Client::create(array_merge($validated, [
                'company_id' => $companyId,
            ]));

            DB::commit();

            $this->logAudit('created', $client, ['after' => $client->toArray()], 'Client creation', 'clients.create');

            return redirect()->route('clients.index')->with('success', 'Client created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create client', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the client.');
        }
    }

    public function show(Client $client)
    {
        $this->authorizeClient($client);

        if (!auth()->user()->hasPermission('client.read')) {
            abort(403, 'Unauthorized to view client.');
        }

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        $this->authorizeClient($client);

        if (!auth()->user()->hasPermission('client.update')) {
            abort(403, 'Unauthorized to edit client.');
        }

        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        if (!auth()->user()->hasPermission('client.update')) {
            abort(403, 'Unauthorized to update client.');
        }

        $request->merge([
            'is_active' => $request->has('is_active'),
        ]);

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'contact_person'  => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:20',
            'address'         => 'nullable|string',
            'billing_address' => 'nullable|string',
            'industry'        => 'nullable|string|max:255',
            'tin'             => 'nullable|string|max:255',
            'category'        => 'nullable|string|max:255',
            'client_type'     => 'nullable|in:corporate,government,individual',
            'website'         => 'nullable|url|max:255',
            'notes'           => 'nullable|string',
            'rating'          => 'nullable|integer|min:1|max:5',
            'is_active'       => 'boolean',
            'payment_terms'   => 'nullable|string|max:255',
            'credit_limit'    => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $client->update($validated);

            DB::commit();

            $original = $client->getOriginal();
            $client->update($validated);
            $updated = $client->fresh()->toArray();

            $this->logAudit('updated', $client, [
                'before' => $original,
                'after'  => $updated
            ], 'Client update', 'clients.edit');

            return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update client', [
                'error'     => $e->getMessage(),
                'client_id' => $client->id,
                'user_id'   => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the client.');
        }
    }

    public function destroy(Client $client)
    {
        $this->authorizeClient($client);

        if (!auth()->user()->hasPermission('client.delete')) {
            abort(403, 'Unauthorized to delete client.');
        }

        DB::beginTransaction();

        try {
            $client->delete();

            DB::commit();

            $clientData = $client->toArray();
            $client->delete();

            $this->logAudit('deleted', $client, ['before' => $clientData], 'Client deletion', 'clients.index');

            return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete client', [
                'error'     => $e->getMessage(),
                'client_id' => $client->id,
                'user_id'   => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the client.');
        }
    }

    protected function authorizeClient(Client $client)
    {
        if ($client->company_id !== auth()->user()->preference->company_id) {
            abort(403, 'Unauthorized access to this client.');
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
