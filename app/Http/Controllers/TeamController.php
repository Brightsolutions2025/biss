<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    /**
     * Display a listing of teams under the active company.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('team.browse')) {
            abort(403, 'Unauthorized to browse teams.');
        }

        $companyId = auth()->user()->preference->company_id;

        $query = Team::whereHas('department', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->with('department');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        $teams = $query->paginate(10)->appends($request->query());

        return view('teams.index', compact('teams'));
    }

    /**
     * Show the form for creating a new team.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('team.create')) {
            abort(403, 'Unauthorized to create teams.');
        }

        $companyId = auth()->user()->preference->company_id;

        $departments = Department::where('company_id', $companyId)->get();

        return view('teams.create', compact('departments'));
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('team.create')) {
            abort(403, 'Unauthorized to create teams.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'department_id' => [
                'required',
                function ($attribute, $value, $fail) use ($companyId) {
                    if (!\App\Models\Department::where('id', $value)->where('company_id', $companyId)->exists()) {
                        $fail('Invalid department selected.');
                    }
                },
            ],
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $team = Team::create([
                'company_id'    => $companyId,
                'department_id' => $validated['department_id'],
                'name'          => $validated['name'],
                'description'   => $validated['description'] ?? null,
            ]);

            DB::commit();

            Log::info('Team created', [
                'team_id'    => $team->id,
                'company_id' => $companyId,
                'user_id'    => auth()->id(),
            ]);

            return redirect()->route('teams.index')->with('success', 'Team created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create team', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the team.');
        }
    }

    /**
     * Display the specified team.
     */
    public function show(Team $team)
    {
        $this->authorizeTeam($team);

        if (!auth()->user()->hasPermission('team.read')) {
            abort(403, 'Unauthorized to view team.');
        }

        $team->load('department');

        return view('teams.show', compact('team'));
    }

    /**
     * Show the form for editing the specified team.
     */
    public function edit(Team $team)
    {
        $this->authorizeTeam($team);

        if (!auth()->user()->hasPermission('team.update')) {
            abort(403, 'Unauthorized to edit team.');
        }

        $companyId   = auth()->user()->preference->company_id;
        $departments = Department::where('company_id', $companyId)->get();

        return view('teams.edit', compact('team', 'departments'));
    }

    /**
     * Update the specified team in storage.
     */
    public function update(Request $request, Team $team)
    {
        $this->authorizeTeam($team);

        if (!auth()->user()->hasPermission('team.update')) {
            abort(403, 'Unauthorized to update team.');
        }

        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'department_id' => [
                'required',
                function ($attribute, $value, $fail) use ($companyId) {
                    if (!\App\Models\Department::where('id', $value)->where('company_id', $companyId)->exists()) {
                        $fail('Invalid department selected.');
                    }
                },
            ],
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $team->update([
                'company_id'    => $companyId,
                'department_id' => $validated['department_id'],
                'name'          => $validated['name'],
                'description'   => $validated['description'] ?? null,
            ]);

            DB::commit();

            Log::info('Team created', [
                'team_id'    => $team->id,
                'company_id' => $companyId,
                'user_id'    => auth()->id(),
            ]);

            return redirect()->route('teams.index')->with('success', 'Team updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update team', [
                'error'   => $e->getMessage(),
                'team_id' => $team->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the team.');
        }
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Team $team)
    {
        $this->authorizeTeam($team);

        if (!auth()->user()->hasPermission('team.delete')) {
            abort(403, 'Unauthorized to delete team.');
        }

        DB::beginTransaction();

        try {
            $team->delete();

            DB::commit();

            Log::info('Team created', [
                'team_id'    => $team->id,
                'user_id'    => auth()->id(),
            ]);

            return redirect()->route('teams.index')->with('success', 'Team deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete team', [
                'error'   => $e->getMessage(),
                'team_id' => $team->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the team.');
        }
    }

    /**
     * Ensure the team belongs to a department in the user's active company.
     */
    protected function authorizeTeam(Team $team)
    {
        $companyId = auth()->user()->preference->company_id;

        if ($team->department->company_id != $companyId) {
            abort(403, 'Unauthorized');
        }
    }
}
