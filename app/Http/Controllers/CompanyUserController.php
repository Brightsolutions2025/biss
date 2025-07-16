<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyUserController extends Controller
{
    /**
     * Display a list of users for the given company.
     */
    public function index(Request $request)
    {
        $query = CompanyUser::with(['company', 'user']);

        if ($request->filled('name')) {
            $searchTerm = $request->input('name');

            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            })->orWhereHas('company', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
        }

        $companyUsers = $query->paginate(10)->appends($request->query());

        return view('company_users.index', compact('companyUsers'));
    }

    /**
     * Show form to assign users to a company.
     */
    public function create()
    {
        $companies = Company::all();
        $company = auth()->user()->preference->company;
        $assignedUserIds = $company->users()->pluck('user_id')->toArray();
        $users = User::all();
        //$users = CompanyUser::with(['user', 'company'])->get();

        return view('company_users.create', compact('company', 'assignedUserIds', 'companies', 'users'));
    }

    /**
     * Store new user assignments to the company.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'user_id' => 'required|exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            $company = Company::findOrFail($validated['company_id']);

            if (!$company->users()->where('user_id', $validated['user_id'])->exists()) {
                $company->users()->attach($validated['user_id']);
            }

            DB::commit();

            Log::info('Company users updated', [
                'company_id' => $company->id,
                'user_id' => auth()->id(),
                'assigned_user' => $validated['user_id'],
            ]);

            return redirect()->route('company_users.index')->with('success', 'User assigned to company successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to assign users to company', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage());
        }
    }

    public function show(CompanyUser $companyUser)
    {
        return view('company_users.show', compact('companyUser'));
    }

    public function edit(CompanyUser $companyUser)
    {
        $companies = Company::all();
        $users = User::all();

        return view('company_users.edit', compact('companyUser', 'companies', 'users'));
    }

    public function update(Request $request, CompanyUser $companyUser)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'user_id' => 'required|exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            // Detach old relationship
            DB::table('company_user')
                ->where('company_id', $companyUser->company_id)
                ->where('user_id', $companyUser->user_id)
                ->delete();

            // Attach new relationship
            DB::table('company_user')->insert([
                'company_id' => $validated['company_id'],
                'user_id' => $validated['user_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info('CompanyUser updated', [
                'old_company_id' => $companyUser->company_id,
                'old_user_id' => $companyUser->user_id,
                'new_company_id' => $validated['company_id'],
                'new_user_id' => $validated['user_id'],
                'action_by' => auth()->id(),
            ]);

            return redirect()->route('company_users.index')->with('success', 'Assignment updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update company-user assignment', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('Update failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove a user from a company.
     */
    public function destroy(CompanyUser $companyUser)
    {
        DB::beginTransaction();

        try {
            Company::find($companyUser->company_id)
                ->users()
                ->detach($companyUser->user_id);

            DB::commit();

            Log::info('User removed from company', [
                'company_id' => $companyUser->company_id,
                'user_id' => $companyUser->user_id,
                'action_by' => auth()->id(),
            ]);

            return redirect()->route('company_users.index')->with('success', 'User removed from company successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to remove user from company', [
                'error' => $e->getMessage(),
                'company_id' => $companyUser->company_id,
                'user_id' => $companyUser->user_id,
                'action_by' => auth()->id(),
            ]);

            return back()->withErrors($e->getMessage());
        }
    }
}
