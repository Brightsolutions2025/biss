<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mews\Purifier\Facades\Purifier;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('project.browse')) {
            abort(403, 'Unauthorized to browse projects.');
        }

        $company = auth()->user()->preference->company;

        $query = Project::where('company_id', $company->id)
            ->with('client', 'projectManager');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortableFields = ['name', 'start_date', 'created_at'];
        $sortBy = $request->get('sort_by');

        if (in_array($sortBy, $sortableFields)) {
            $query->orderBy($sortBy);
        } else {
            $query->orderBy('created_at', 'desc'); // default sort
        }

        $projects = $query->paginate(10)->appends($request->query());

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('project.create')) {
            abort(403, 'Unauthorized to create projects.');
        }

        $clients = Client::where('company_id', auth()->user()->preference->company_id)->get();

        return view('projects.create', compact('clients'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('project.create')) {
            abort(403, 'Unauthorized to create projects.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'project_type'        => 'required|in:internal,external,client-based',
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'location'            => 'nullable|string|max:255',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'status'              => 'required|string|max:255',
            'budget'              => 'nullable|numeric|min:0',
            'priority'            => 'nullable|string|max:255',
            'project_manager_id'  => 'nullable|exists:users,id',
            'locked_budget'       => 'boolean',
            'budget_buffer'       => 'nullable|numeric|min:0',
            'completion_date_actual' => 'nullable|date',
            'risk_level'          => 'nullable|in:low,medium,high',
            'tags'                => 'nullable|json',
        ]);

        DB::beginTransaction();

        try {
            $validated['description'] = strip_tags($request->input('description'));
            $validated['name'] = strip_tags($request->input('name'));
            $validated['location'] = strip_tags($request->input('location'));
            $validated['priority'] = strip_tags($request->input('priority'));
            $validated['status'] = strip_tags($request->input('status'));

            if ($request->filled('tags')) {
                $validated['tags'] = Purifier::clean($request->input('tags'));
            }

            $project = Project::create(array_merge($validated, [
                'company_id' => $companyId,
                'created_by' => auth()->id(),
                'locked_budget' => $request->has('locked_budget'),
            ]));

            DB::commit();

            $this->logAudit('created', $project, ['after' => $project->toArray()], 'Project creation', 'projects.create');

            return redirect()->route('projects.index')->with('success', 'Project created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create project', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the project.');
        }
    }

    public function show(Project $project)
    {
        $this->authorizeProject($project);

        if (!auth()->user()->hasPermission('project.read')) {
            abort(403, 'Unauthorized to view project.');
        }

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $this->authorizeProject($project);

        if (!auth()->user()->hasPermission('project.update')) {
            abort(403, 'Unauthorized to edit project.');
        }

        $clients = Client::where('company_id', auth()->user()->preference->company_id)->get();

        return view('projects.edit', compact('project', 'clients'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        if (!auth()->user()->hasPermission('project.update')) {
            abort(403, 'Unauthorized to update project.');
        }

        $validated = $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'project_type'        => 'required|in:internal,external,client-based',
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'location'            => 'nullable|string|max:255',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'status'              => 'required|string|max:255',
            'budget'              => 'nullable|numeric|min:0',
            'priority'            => 'nullable|string|max:255',
            'project_manager_id'  => 'nullable|exists:users,id',
            'locked_budget'       => 'boolean',
            'budget_buffer'       => 'nullable|numeric|min:0',
            'completion_date_actual' => 'nullable|date',
            'risk_level'          => 'nullable|in:low,medium,high',
            'tags'                => 'nullable|json',
        ]);

        DB::beginTransaction();

        try {
            $validated['description'] = strip_tags($request->input('description'));
            $validated['name'] = strip_tags($request->input('name'));
            $validated['location'] = strip_tags($request->input('location'));
            $validated['priority'] = strip_tags($request->input('priority'));
            $validated['status'] = strip_tags($request->input('status'));

            if ($request->filled('tags')) {
                $validated['tags'] = Purifier::clean($request->input('tags'));
            }

            $original = $project->getOriginal();

            $project->update(array_merge($validated, [
                'locked_budget' => $request->has('locked_budget'),
            ]));

            DB::commit();

            $this->logAudit('updated', $project, [
                'before' => $original,
                'after'  => $project->fresh()->toArray(),
            ], 'Project update', 'projects.edit');

            return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update project', [
                'error'     => $e->getMessage(),
                'project_id' => $project->id,
                'user_id'   => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the project.');
        }
    }

    public function destroy(Project $project)
    {
        $this->authorizeProject($project);

        if (!auth()->user()->hasPermission('project.delete')) {
            abort(403, 'Unauthorized to delete project.');
        }

        DB::beginTransaction();

        try {
            $original = $project->toArray();
            $project->delete();

            DB::commit();

            $this->logAudit('deleted', $project, ['before' => $original], 'Project deletion', 'projects.index');

            return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete project', [
                'error'     => $e->getMessage(),
                'project_id' => $project->id,
                'user_id'   => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the project.');
        }
    }

    protected function authorizeProject(Project $project)
    {
        if ($project->company_id !== auth()->user()->preference->company_id) {
            abort(403, 'Unauthorized access to this project.');
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
