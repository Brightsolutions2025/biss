{{-- resources/views/overtime_pre_approvals/edit.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Overtime Pre-Approval') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
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

                <form method="POST" action="{{ route('overtime_pre_approvals.update', $overtimePreApproval) }}"
                    onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerText='Updating...';">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Employee</label>
                        <select name="employee_id" id="employee_id" class="form-select" required>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}"
                                    {{ old('employee_id', $overtimePreApproval->employee_id) == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->last_name }}, {{ $employee->first_name }}
                                    @if (!empty($employee->employee_number))
                                        ({{ $employee->employee_number }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Overtime Date</label>
                        <input type="date" name="date" id="date" class="form-control"
                               value="{{ old('date', optional($overtimePreApproval->date)->format('Y-m-d')) }}" required>
                        @error('date')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="planned_time_start" class="form-label">Planned Time Start</label>
                        <input type="time" name="planned_time_start" id="planned_time_start" class="form-control"
                               value="{{ old('planned_time_start', $overtimePreApproval->planned_time_start ? \Illuminate\Support\Carbon::parse($overtimePreApproval->planned_time_start)->format('H:i') : '') }}" required>
                        @error('planned_time_start')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="planned_time_end" class="form-label">Planned Time End</label>
                        <input type="time" name="planned_time_end" id="planned_time_end" class="form-control"
                               value="{{ old('planned_time_end', $overtimePreApproval->planned_time_end ? \Illuminate\Support\Carbon::parse($overtimePreApproval->planned_time_end)->format('H:i') : '') }}" required>
                        @error('planned_time_end')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="estimated_number_of_hours" class="form-label">Estimated Number of Hours</label>
                        <input type="number" name="estimated_number_of_hours" id="estimated_number_of_hours"
                               step="0.01" min="0.25" class="form-control"
                               value="{{ old('estimated_number_of_hours', $overtimePreApproval->estimated_number_of_hours) }}" required>
                        @error('estimated_number_of_hours')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Overtime</label>
                        <textarea name="reason" id="reason" rows="3" class="form-control" required>{{ old('reason', $overtimePreApproval->reason) }}</textarea>
                        @error('reason')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="planned_tasks" class="form-label">Planned Tasks / Deliverables</label>
                        <textarea name="planned_tasks" id="planned_tasks" rows="3" class="form-control"
                                  placeholder="Specify the tasks or deliverables to be completed during overtime.">{{ old('planned_tasks', $overtimePreApproval->planned_tasks) }}</textarea>
                        @error('planned_tasks')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Current Status</label>
                        <input type="text" class="form-control bg-light"
                               value="{{ ucfirst($overtimePreApproval->status ?? 'pending') }}" disabled>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Update Pre-Approval') }}
                        </button>

                        <a href="{{ route('overtime_pre_approvals.show', $overtimePreApproval) }}" class="btn btn-secondary">
                            {{ __('Cancel') }}
                        </a>

                        <a href="{{ route('overtime_pre_approvals.index') }}" class="btn btn-outline-secondary ms-auto">
                            {{ __('Back to List') }}
                        </a>
                    </div>
                </form>

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