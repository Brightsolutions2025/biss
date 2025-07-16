<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of users in the active company.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $users = $query->paginate(10)->appends($request->query());

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user in the company.
     */
    public function create()
    {
        $company = auth()->user()->preference->company;

        $roles = \App\Models\Role::where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user and optionally assign roles.
     */
    public function store(Request $request)
    {
        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'role_ids'   => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'rolesInput' => 'nullable|string|regex:/^(\d+,)*\d+$/', // ✅ Validates comma-separated role IDs
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => bcrypt($validated['password']),
            ]);

            // Attach to company
            DB::table('company_user')->insert([
                'company_id' => $companyId,
                'user_id'    => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create user preference for the company
            $user->preference()->create([
                'company_id' => $companyId,
            ]);

            $idArray = explode(',', $request->input('rolesInput'));
            $idArray = array_map('intval', $idArray);
            $roles   = Role::whereIn('id', $idArray)->get();

            if ($roles->isNotEmpty()) {
                foreach ($roles as $role) {
                    $user->assignRole($role, $companyId); // Assuming assignRole handles pivot insert for company_id
                }
            }

            DB::commit();

            Log::info('User created', ['user_id' => $user->id, 'company_id' => $companyId]);

            return redirect()->route('users.index')->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create user', [
                'error'      => $e->getMessage(),
                'company_id' => $companyId,
            ]);

            return back()->withErrors('An error occurred while creating the user.');
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $companyId = auth()->user()->preference->company_id;
        $roles = $user->rolesForCompany($companyId); // uses the method above

        return view('users.show', compact('user', 'roles'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $companyId     = auth()->user()->preference->company_id;
        $roles         = Role::where('company_id', $companyId)->get();
        $assignedRoles = $user->roles()->wherePivot('company_id', $companyId)->get();

        return view('users.edit', compact('user', 'roles', 'assignedRoles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $companyId = auth()->user()->preference->company_id;

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password'   => 'nullable|string|min:8',
            'role_ids'   => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'rolesInput' => 'nullable|string|regex:/^(\d+,)*\d+$/', // ✅ Validates comma-separated role IDs
        ]);

        DB::beginTransaction();

        try {
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            if (!empty($validated['password'])) {
                $user->password = bcrypt($validated['password']);
            }
            $user->save();

            // Ensure the user is linked to the current company
            $exists = DB::table('company_user')
                ->where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->exists();

            if (! $exists) {
                DB::table('company_user')->insert([
                    'company_id' => $companyId,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $idArray = explode(',', $request->input('rolesInput'));
            $idArray = array_map('intval', $idArray);
            $roles   = Role::whereIn('id', $idArray)->get();

            $user->roles()->wherePivot('company_id', $companyId)->detach();

            if ($roles->isNotEmpty()) {
                foreach ($roles as $role) {
                    $user->assignRole($role, $companyId); // Assuming `assignRole()` accepts a Role and company context
                }
            }

            DB::commit();

            Log::info('User updated', ['user_id' => $user->id]);

            return redirect()->route('users.index')->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update user', [
                'error'   => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return back()->withErrors($e->getMessage());
        }
    }

    /**
     * Remove the specified user from the current company context.
     */
    public function destroy(User $user)
    {
        $companyId = auth()->user()->preference->company_id;

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own user account.');
        }

        DB::beginTransaction();

        try {
            // Check if the user is assigned to any admin role
            $adminRoleIds = Role::whereRaw('LOWER(name) = ?', ['admin'])->pluck('id');

            $userAdminRoleIds = $user->roles()->whereIn('roles.id', $adminRoleIds)->pluck('roles.id');

            if ($userAdminRoleIds->isNotEmpty()) {
                // For each admin role the user has, check if any other users are assigned to it
                foreach ($userAdminRoleIds as $roleId) {
                    $userCount = DB::table('role_user')
                        ->where('role_id', $roleId)
                        ->where('user_id', '!=', $user->id)
                        ->count();

                    if ($userCount === 0) {
                        return back()->with('error', 'Cannot delete this user. They are the only admin.');
                    }
                }
            }

            // Detach roles only within the current company
            $user->roles()->detach();
            $user->companies()->detach();
            $user->delete();

            DB::commit();

            Log::info('User roles detached from company', ['user_id' => $user->id, 'company_id' => $companyId]);

            return redirect()->route('users.index')->with('success', 'User removed from this company.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to remove user from company', [
                'error'      => $e->getMessage(),
                'user_id'    => $user->id,
                'company_id' => $companyId,
            ]);

            return back()->withErrors('An error occurred while removing the user. Please contact administrator.)' . $e->getMessage());
        }
    }

    /**
     * Ensure the user is part of the active company (via a role).
     */
    protected function authorizeUser(User $user)
    {
        $companyId = auth()->user()->preference->company_id;

        if (!$user->companies()->where('company_id', $companyId)->exists()) {
            abort(403, 'Unauthorized');
        }
    }
}
