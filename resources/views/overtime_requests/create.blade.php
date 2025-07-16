<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Add Overtime Request') }}</h2>
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

                @php
                    $employee = auth()->user()->employee;
                @endphp

                @if (!$employee)
                    <div class="alert alert-warning fw-semibold">
                        You need to complete your employee profile before submitting an overtime request. 
                        <a href="{{ route('employees.create') }}" class="text-decoration-underline">Click here to set up your profile</a>.
                    </div>
                @else
                    <form method="POST" action="{{ route('overtime_requests.store') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Employee Display -->
                        <div class="mb-3">
                            <label for="employee_display" class="form-label">Employee</label>
                            <select id="employee_display" class="form-select bg-light" disabled>
                                <option>
                                    {{ $employee->last_name }}, {{ $employee->first_name }} ({{ $employee->employee_number }})
                                </option>
                            </select>
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        </div>

                        <!-- Overtime Date -->
                        <div class="mb-3">
                            <label for="date" class="form-label">Overtime Date</label>
                            <input type="date" name="date" id="date" class="form-control"
                                   value="{{ old('date') }}" required>
                            @error('date')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Time Start -->
                        <div class="mb-3">
                            <label for="time_start" class="form-label">Time Start</label>
                            <input type="time" name="time_start" id="time_start" class="form-control"
                                   value="{{ old('time_start') }}" required>
                            @error('time_start')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Time End -->
                        <div class="mb-3">
                            <label for="time_end" class="form-label">Time End</label>
                            <input type="time" name="time_end" id="time_end" class="form-control"
                                   value="{{ old('time_end') }}" required>
                            @error('time_end')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Number of Hours -->
                        <div class="mb-3">
                            <label for="number_of_hours" class="form-label">Number of Hours</label>
                            <input type="number" name="number_of_hours" id="number_of_hours"
                                   step="0.01" min="0" class="form-control"
                                   value="{{ old('number_of_hours') }}" required>
                            @error('number_of_hours')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea name="reason" id="reason" rows="3" class="form-control"
                                      required>{{ old('reason') }}</textarea>
                            @error('reason')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Attachments -->
                        <div class="mb-4">
                            <label for="files" class="form-label">Supporting Documents (optional)</label>
                            <input
                                id="files"
                                name="files[]"
                                type="file"
                                class="form-control"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx"
                            >
                            @error('files.*')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Submit') }}
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
</x-app-layout>
