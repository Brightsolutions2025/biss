<style>
    .compact-table td:first-child {
        min-width: 130px;
    }
    .compact-table td,
    .compact-table th {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
        white-space: nowrap;
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Edit Time Record') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">

                <div class="card shadow-sm">
                    <div class="card-body">

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

                        <form method="POST" action="{{ route('time_records.update', $timeRecord->id) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <!-- Employee -->
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Select Employee</label>
                                <select id="employee_id" name="employee_id" class="form-select" required>
                                    <option value="">-- Choose Employee --</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ old('employee_id', $timeRecord->employee_id) == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->employee_number }} - {{ $employee->last_name }}, {{ $employee->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Payroll Period -->
                            <div class="mb-4">
                                <label for="payroll_period_id" class="form-label">Select Payroll Period</label>
                                <select id="payroll_period_id" name="payroll_period_id" class="form-select" required>
                                    <option value="">-- Choose Payroll Period --</option>
                                    @foreach ($payrollPeriods as $period)
                                        <option value="{{ $period->id }}"
                                            data-start="{{ $period->start_date }}"
                                            data-end="{{ $period->end_date }}"
                                            {{ old('payroll_period_id', $timeRecord->payroll_period_id) == $period->id ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::parse($period->start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($period->end_date)->format('M d, Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Time Record Lines -->
                            <div class="table-responsive mb-4" style="zoom: 0.85;">
                                <table class="table table-bordered table-sm align-middle compact-table">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>Clock In</th>
                                            <th>Clock Out</th>
                                            <th>Late</th>
                                            <th>Undertime</th>
                                            <th>OT Start</th>
                                            <th>OT End</th>
                                            <th>OT Hours</th>
                                            <th>Offset Start</th>
                                            <th>Offset End</th>
                                            <th>Offset Hours</th>
                                            <th>Outbase Start</th>
                                            <th>Outbase End</th>
                                            <th>Leave Days</th>
                                            <th>Remaining Leave Credits</th>
                                            <th>With Pay?</th>
                                            <th style="min-width: 200px;">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($timeRecord->lines as $i => $line)
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="time_record_lines[{{ $i }}][id]" value="{{ $line->id }}">
                                                    <input type="text" readonly name="time_record_lines[{{ $i }}][date]" value="{{ $line->date }}" class="form-control form-control-sm text-center" />
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($line->date)->format('D') }}</td>
                                                <td class="text-center">{{ $line->clock_in }}</td>
                                                <td class="text-center">{{ $line->clock_out }}</td>
                                                <td class="text-center">{{ $line->late_minutes }}</td>
                                                <td class="text-center">{{ $line->undertime_minutes }}</td>
                                                <td class="text-center">{{ $line->overtime_time_start }}</td>
                                                <td class="text-center">{{ $line->overtime_time_end }}</td>
                                                <td class="text-center">{{ $line->overtime_hours }}</td>
                                                <td class="text-center">{{ $line->offset_time_start }}</td>
                                                <td class="text-center">{{ $line->offset_time_end }}</td>
                                                <td class="text-center">{{ $line->offset_hours }}</td>
                                                <td class="text-center">{{ $line->outbase_time_start }}</td>
                                                <td class="text-center">{{ $line->outbase_time_end }}</td>
                                                <td class="text-center">{{ $line->leave_days }}</td>
                                                <td class="text-center">{{ $line->remaining_leave_credits }}</td>
                                                <td class="text-center">{{ $line->leave_with_pay ? 'Yes' : 'No' }}</td>
                                                <td><input type="text" name="time_record_lines[{{ $i }}][remarks]" value="{{ $line->remarks }}" class="form-control form-control-sm" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if ($timeRecord->files->count())
                                <div class="mb-3">
                                    <label class="form-label">Attached Files</label>
                                    <ul class="list-unstyled">
                                        @foreach ($timeRecord->files as $file)
                                            <li class="mb-2">
                                                <a href="{{ route('files.download', $file->id) }}" target="_blank">
                                                    {{ $file->file_name }}
                                                </a>

                                                <a href="#" 
                                                class="btn btn-sm btn-outline-danger ms-2"
                                                onclick="event.preventDefault(); deleteFile({{ $file->id }})">
                                                    Delete
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Add More Files (Max: 5)</label>
                                <input type="file" name="files[]" multiple class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Update') }}
                                </button>

                                <a href="javascript:history.back()" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        function deleteFile(fileId) {
            if (!confirm('Delete this file?')) return;

            fetch(`/files/${fileId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => {
                if (res.ok) {
                    location.reload();
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
