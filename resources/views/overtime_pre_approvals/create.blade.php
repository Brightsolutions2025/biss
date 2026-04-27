<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Add Overtime Pre-Approval') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
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

                @if (!$employee)
                    <div class="alert alert-warning fw-semibold">
                        You need to complete your employee profile before submitting an overtime pre-approval.
                        <a href="{{ route('employees.create') }}" class="text-decoration-underline">Click here to set up your profile</a>.
                    </div>
                @else
                    <form method="POST" action="{{ route('overtime_pre_approvals.store') }}"
                        onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerText='Submitting...';">
                        @csrf

                        <div class="mb-3">
                            <label for="employee_display" class="form-label">Employee</label>
                            <select id="employee_display" class="form-select bg-light" disabled>
                                <option>
                                    {{ $employee->last_name }}, {{ $employee->first_name }} ({{ $employee->employee_number }})
                                </option>
                            </select>
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        </div>

                        <div class="mb-3">
                            <label for="date" class="form-label">Overtime Date</label>
                            <input type="date" name="date" id="date" class="form-control"
                                   value="{{ old('date') }}" required>
                            @error('date')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="planned_time_start" class="form-label">Planned Time Start</label>
                            <input type="time" name="planned_time_start" id="planned_time_start" class="form-control"
                                   value="{{ old('planned_time_start') }}" required>
                            @error('planned_time_start')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="planned_time_end" class="form-label">Planned Time End</label>
                            <input type="time" name="planned_time_end" id="planned_time_end" class="form-control"
                                   value="{{ old('planned_time_end') }}" required>
                            @error('planned_time_end')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="estimated_number_of_hours" class="form-label">Estimated Number of Hours</label>
                            <input type="number" name="estimated_number_of_hours" id="estimated_number_of_hours"
                                   step="0.01" min="0.25" class="form-control"
                                   value="{{ old('estimated_number_of_hours') }}" required>
                            @error('estimated_number_of_hours')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Overtime</label>
                            <textarea name="reason" id="reason" rows="3" class="form-control"
                                      required>{{ old('reason') }}</textarea>
                            @error('reason')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="planned_tasks" class="form-label">Planned Tasks / Deliverables</label>
                            <textarea name="planned_tasks" id="planned_tasks" rows="3" class="form-control"
                                      placeholder="Specify the tasks or deliverables to be completed during overtime.">{{ old('planned_tasks') }}</textarea>
                            @error('planned_tasks')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Submit Pre-Approval') }}
                            </button>

                            <a href="javascript:history.back()" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                @endif

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startInput = document.getElementById('planned_time_start');
            const endInput = document.getElementById('planned_time_end');
            const hoursInput = document.getElementById('estimated_number_of_hours');

            function computeHours() {
                const start = startInput.value;
                const end = endInput.value;

                if (start && end) {
                    const startTime = new Date(`1970-01-01T${start}:00`);
                    const endTime = new Date(`1970-01-01T${end}:00`);

                    let diff = (endTime - startTime) / (1000 * 60 * 60);

                    if (diff <= 0) {
                        diff += 24;
                    }

                    hoursInput.value = diff.toFixed(2);
                }
            }

            startInput.addEventListener('change', computeHours);
            endInput.addEventListener('change', computeHours);
        });
    </script>
    @endpush
</x-app-layout>