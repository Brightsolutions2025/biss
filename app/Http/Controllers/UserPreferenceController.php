<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserPreferenceController extends Controller
{
    /**
     * Display the user's preferences.
     */
    public function index()
    {
        $preference = auth()->user()->preference;

        return view('preferences.index', compact('preference'));
    }

    /**
     * Show the form for editing the user's preferences.
     */
    public function edit()
    {
        $preference = auth()->user()->preference;
        $companies  = auth()->user()->companies;

        return view('preferences.edit', compact('preference', 'companies'));
    }

    /**
     * Update the user's preferences.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_id'  => 'nullable|exists:companies,id',
            'preferences' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $user = auth()->user();

            $preference = UserPreference::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id'  => $validated['company_id']  ?? null,
                    'preferences' => $validated['preferences'] ?? [],
                ]
            );

            DB::commit();

            Log::info('User preferences updated', [
                'user_id'    => $user->id,
                'company_id' => $preference->company_id,
            ]);

            return redirect()->route('dashboard')->with('success', 'Preferences updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update preferences', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating preferences.');
        }
    }

    /**
     * Switch active company for the user (through preferences).
     */
    public function switchCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        if (!auth()->user()->companies->contains('id', $request->company_id)) {
            abort(403, 'Unauthorized to switch to this company.');
        }

        DB::beginTransaction();

        try {
            $user       = auth()->user();
            $preference = $user->preference ?? new UserPreference(['user_id' => $user->id]);

            $preference->company_id = $request->company_id;
            $preference->save();

            DB::commit();

            Log::info('User switched active company', [
                'user_id'    => $user->id,
                'company_id' => $request->company_id,
            ]);

            return back()->with('success', 'Switched active company.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to switch company', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while switching companies.');
        }
    }
}
