<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Employee Shift Details') }}</h2>
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

                        <div class="mb-3">
                            <label class="form-label fw-bold">Employee Name</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ $employeeShift->employee->last_name }}, {{ $employeeShift->employee->first_name }} {{ $employeeShift->employee->middle_name }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Employee Number</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ $employeeShift->employee->employee_number }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Department</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ $employeeShift->employee->department->name ?? 'N/A' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Team</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ $employeeShift->employee->team->name ?? 'N/A' }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Shift Name</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ $employeeShift->shift->name }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Shift Time</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ \Carbon\Carbon::parse($employeeShift->shift->time_in)->format('h:i A') }} - {{ \Carbon\Carbon::parse($employeeShift->shift->time_out)->format('h:i A') }}">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Night Shift</label>
                            <input type="text" class="form-control" disabled
                                   value="{{ $employeeShift->shift->is_night_shift ? 'Yes' : 'No' }}">
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('employee_shifts.edit', $employeeShift->id) }}" class="btn btn-primary">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('employee_shifts.destroy', $employeeShift->id) }}" onsubmit="return confirm('Are you sure you want to delete this shift assignment?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    Delete
                                </button>
                            </form>
                            <a href="javascript:history.back()" class="btn btn-secondary">
                                Back
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
