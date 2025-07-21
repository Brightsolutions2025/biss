<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiftController extends Controller
{
    /**
     * Display a listing of the company's shifts.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermission('shift.browse')) {
            abort(403, 'Unauthorized to browse shifts.');
        }

        $companyId = auth()->user()->preference->company_id;

        $query = Shift::where('company_id', $companyId);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        $shifts = $query->paginate(10)->appends($request->query());

        return view('shifts.index', compact('shifts'));
    }

    /**
     * Show the form for creating a new shift.
     */
    public function create()
    {
        if (!auth()->user()->hasPermission('shift.create')) {
            abort(403, 'Unauthorized to create shifts.');
        }

        return view('shifts.create');
    }

    /**
     * Store a newly created shift in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('shift.create')) {
            abort(403, 'Unauthorized to create shifts.');
        }

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'time_in'        => 'required|date_format:H:i',
            'time_out'       => 'required|date_format:H:i',
            'is_night_shift' => 'nullable|boolean',
        ], [
            'time_in.required'     => 'Please reselect the time in.',
            'time_in.date_format'  => 'The time in must be a valid time (H:i format). Please reselect it.',
            'time_out.required'    => 'Please reselect the time out.',
            'time_out.date_format' => 'The time out must be a valid time (H:i format). Please reselect it.',
        ]);

        $companyId                   = auth()->user()->preference->company_id;
        $validated['is_night_shift'] = $request->boolean('is_night_shift');

        DB::beginTransaction();

        try {
            $shift                 = new Shift();
            $shift->company_id     = $companyId;
            $shift->name           = $validated['name'];
            $shift->time_in        = $validated['time_in'];
            $shift->time_out       = $validated['time_out'];
            $shift->is_night_shift = $validated['is_night_shift'] ?? false; // default to false if not set
            $shift->save();

            DB::commit();

            Log::info('Shift created', [
                'shift_id'   => $shift->id,
                'company_id' => $companyId,
                'user_id'    => auth()->id(),
            ]);

            return redirect()->route('shifts.index')->with('success', 'Shift created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create shift', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while creating the shift.');
        }
    }

    /**
     * Display the specified shift.
     */
    public function show(Shift $shift)
    {
        $this->authorizeShift($shift);

        if (!auth()->user()->hasPermission('shift.read')) {
            abort(403, 'Unauthorized to view shift.');
        }

        return view('shifts.show', compact('shift'));
    }

    /**
     * Show the form for editing the specified shift.
     */
    public function edit(Shift $shift)
    {
        $this->authorizeShift($shift);

        if (!auth()->user()->hasPermission('shift.update')) {
            abort(403, 'Unauthorized to edit shift.');
        }

        return view('shifts.edit', compact('shift'));
    }

    /**
     * Update the specified shift in storage.
     */
    public function update(Request $request, Shift $shift)
    {
        $this->authorizeShift($shift);

        if (!auth()->user()->hasPermission('shift.update')) {
            abort(403, 'Unauthorized to update shift.');
        }

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'time_in'        => 'required|date_format:H:i',
            'time_out'       => 'required|date_format:H:i',
            'is_night_shift' => 'nullable|boolean',
        ], [
            'time_in.required'     => 'Please reselect the time in.',
            'time_in.date_format'  => 'The time in must be a valid time (H:i format). Please reselect it.',
            'time_out.required'    => 'Please reselect the time out.',
            'time_out.date_format' => 'The time out must be a valid time (H:i format). Please reselect it.',
        ]);

        $companyId                   = auth()->user()->preference->company_id;
        // Checkbox default value if not submitted
        $validated['is_night_shift'] = $request->boolean('is_night_shift');

        DB::beginTransaction();

        try {
            $shift->company_id     = $companyId;
            $shift->update($validated);

            DB::commit();

            Log::info('Shift updated', ['shift_id' => $shift->id, 'user_id' => auth()->id()]);

            return redirect()->route('shifts.index')->with('success', 'Shift updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update shift', [
                'error'    => $e->getMessage(),
                'shift_id' => $shift->id,
                'user_id'  => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while updating the shift.');
        }
    }

    /**
     * Remove the specified shift from storage.
     */
    public function destroy(Shift $shift)
    {
        $this->authorizeShift($shift);

        if (!auth()->user()->hasPermission('shift.delete')) {
            abort(403, 'Unauthorized to delete shift.');
        }

        DB::beginTransaction();

        try {
            $shift->delete();

            DB::commit();

            Log::info('Shift deleted', ['shift_id' => $shift->id, 'user_id' => auth()->id()]);

            return redirect()->route('shifts.index')->with('success', 'Shift deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete shift', [
                'error'    => $e->getMessage(),
                'shift_id' => $shift->id,
                'user_id'  => auth()->id(),
            ]);

            return back()->withErrors('An error occurred while deleting the shift.');
        }
    }

    /**
     * Private helper to ensure the shift belongs to the active company.
     */
    protected function authorizeShift(Shift $shift)
    {
        $companyId = $shift->company_id;
        $user      = auth()->user();

        // Ensure the user is assigned to the company
        if (!$user->companies->contains($companyId)) {
            abort(403, 'Unauthorized: Company access denied.');
        }

        // Ensure the user's *active* preference matches the given company (enforcing scope)
        if ($user->preference->company_id != $companyId) {
            abort(403, 'Unauthorized: Company mismatch.');
        }
    }
}
