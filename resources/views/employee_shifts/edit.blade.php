<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Employee Shift Assignment') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('employee_shifts.update', $employeeShift->id) }}">
                            @csrf
                            @method('PATCH')

                            <!-- Hidden Company ID -->
                            <input type="hidden" name="company_id" value="{{ old('company_id', $employeeShift->company_id) }}">

                            <!-- Employee Selection (Disabled but with hidden field) -->
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Employee</label>
                                <select id="employee_id" class="form-select" disabled>
                                    <option value="">-- Select Employee --</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ old('employee_id', $employeeShift->employee_id) == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->last_name }}, {{ $employee->first_name }} ({{ $employee->employee_number }})
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="employee_id" value="{{ $employeeShift->employee_id }}">
                            </div>

                            <!-- Shift Selection -->
                            <div class="mb-4">
                                <label for="shift_id" class="form-label">Shift</label>
                                <select id="shift_id" name="shift_id" class="form-select" required>
                                    <option value="">-- Select Shift --</option>
                                    @foreach ($shifts as $shift)
                                        <option value="{{ $shift->id }}" {{ old('shift_id', $employeeShift->shift_id) == $shift->id ? 'selected' : '' }}>
                                            {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->time_in)->format('h:i A') }} - {{ \Carbon\Carbon::parse($shift->time_out)->format('h:i A') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('shift_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-3 mt-4">
                                <button type="submit" class="btn btn-primary">Update Assignment</button>
                                <a href="{{ route('employee_shifts.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
