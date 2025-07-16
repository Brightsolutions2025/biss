<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Time Log') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

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

                <form method="POST" action="{{ route('time_logs.update', $timeLog) }}" enctype="multipart/form-data" class="card card-body">
                    @csrf
                    @method('PATCH')

                    <!-- Payroll Period -->
                    <div class="mb-3">
                        <label for="payroll_period_id" class="form-label">Payroll Period</label>
                        <select id="payroll_period_id" name="payroll_period_id" required class="form-select">
                            <option value="">Select a payroll period</option>
                            @foreach ($payrollPeriods as $period)
                                <option value="{{ $period->id }}" {{ old('payroll_period_id', $timeLog->payroll_period_id) == $period->id ? 'selected' : '' }}>
                                    {{ $period->start_date }} - {{ $period->end_date }}
                                </option>
                            @endforeach
                        </select>
                        @error('payroll_period_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    @php
                        $fields = [
                            'employee_name' => 'Employee Name',
                            'department_name' => 'Department Name',
                            'employee_id' => 'Employee ID',
                            'employee_type' => 'Employee Type',
                            'attendance_group' => 'Attendance Group',
                            'date' => 'Date',
                            'weekday' => 'Weekday',
                            'shift' => 'Shift',
                            'attendance_time' => 'Attendance Time',
                            'about_the_record' => 'About The Record',
                            'attendance_result' => 'Attendance Result',
                            'attendance_address' => 'Attendance Address',
                            'note' => 'Note',
                            'attendance_method' => 'Attendance Method',
                            'attendance_photo' => 'Attendance Photo URL',
                        ];

                        $types = [
                            'date' => 'date',
                            'attendance_time' => 'datetime-local',
                            'attendance_photo' => 'url'
                        ];
                    @endphp

                    @foreach ($fields as $field => $label)
                        <div class="mb-3">
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <input
                                type="{{ $types[$field] ?? 'text' }}"
                                name="{{ $field }}"
                                id="{{ $field }}"
                                class="form-control"
                                value="{{ old($field, $timeLog->$field) }}"
                                {{ $field !== 'note' ? 'required' : '' }}
                            >
                            @error($field)
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('time_logs.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Time Log</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
