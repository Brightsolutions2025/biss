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
    .late-minutes-input,
    .undertime-minutes-input {
        min-width: 80px;
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

                        <form method="POST" action="{{ route('time_records.update', $timeRecord->id) }}" enctype="multipart/form-data"
                            onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerText='Submitting...';">
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

                            <!-- Time Record Lines Table -->
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
                                            <th>Compensatory Time-Off Start</th>
                                            <th>Compensatory Time-Off End</th>
                                            <th>Compensatory Time-Off Hours</th>
                                            <th>Outbase Start</th>
                                            <th>Outbase End</th>
                                            <th>Leave Days</th>
                                            <th>Remaining Leave</th>
                                            <th>With Pay?</th>
                                            <th>Schedule Remark</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($timeRecord->lines as $i => $line)
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="time_record_lines[{{ $i }}][id]" value="{{ $line->id }}">
                                                    <input type="text" readonly name="time_record_lines[{{ $i }}][date]" value="{{ $line->date }}" class="form-control form-control-sm text-center" />
                                                </td>
                                                <td class="text-center">{{ \Carbon\Carbon::parse($line->date)->format('D') }}</td>
                                                <td>
                                                    <input type="time" name="time_record_lines[{{ $i }}][clock_in]" value="{{ $line->clock_in }}" class="form-control form-control-sm text-center" />
                                                </td>
                                                <td>
                                                    <input type="time" name="time_record_lines[{{ $i }}][clock_out]" value="{{ $line->clock_out }}" class="form-control form-control-sm text-center" />
                                                </td>
                                                <td>
                                                    <input type="number" readonly step="0.01"
                                                        name="time_record_lines[{{ $i }}][late_minutes]"
                                                        class="form-control form-control-sm late-minutes-input text-center"
                                                        value="{{ number_format($line->late_minutes ?? 0, 2, '.', '') }}">
                                                </td>
                                                <td>
                                                    <input type="number" readonly step="0.01"
                                                        name="time_record_lines[{{ $i }}][undertime_minutes]"
                                                        class="form-control form-control-sm undertime-minutes-input text-center"
                                                        value="{{ number_format($line->undertime_minutes ?? 0, 2, '.', '') }}">
                                                </td>
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
                                                <td class="text-warning fw-semibold text-center">
                                                    {{ $dayOffChangeMap[$line->date] ?? '' }}
                                                </td>
                                                <td>
                                                    <input type="text" name="time_record_lines[{{ $i }}][remarks]" value="{{ $line->remarks }}" class="form-control form-control-sm" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Files -->
                            @if ($timeRecord->files->count())
                                <div class="mb-3">
                                    <label class="form-label">Attached Files</label>
                                    <ul class="list-unstyled">
                                        @foreach ($timeRecord->files as $file)
                                            <li class="mb-2">
                                                <a href="{{ route('files.download', $file->id) }}" target="_blank">
                                                    {{ $file->file_name }}
                                                </a>
                                                <a href="#" class="btn btn-sm btn-outline-danger ms-2" onclick="event.preventDefault(); deleteFile({{ $file->id }})">
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
                                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                                <a href="javascript:history.back()" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const attendancePolicy = @json($attendancePolicy);
        const scheduledTimeIn = @json(optional(optional($timeRecord->employee->employeeShift)->shift)->time_in);
        const scheduledTimeOut = @json(optional(optional($timeRecord->employee->employeeShift)->shift)->time_out);

        function timeToMinutes(time) {
            if (!time) return null;
            const [h, m] = time.split(':').map(Number);
            return h * 60 + m;
        }

        function isLegacyAttendanceDate(dateStr) {
            return dateStr < attendancePolicy.effective_date;
        }

        function legacyBreakOverlapMinutes(start, end) {
            const breakStart = timeToMinutes(attendancePolicy.legacy_break_start || '12:00');
            const breakEnd = timeToMinutes(attendancePolicy.legacy_break_end || '13:00');
            if (end <= breakStart || start >= breakEnd) return 0;
            return Math.max(0, Math.min(end, breakEnd) - Math.max(start, breakStart));
        }

        function calculateLateMinutes(scheduled, actual, useLegacyBreak = false) {
            if (!scheduled || !actual) return 0;
            let diff = timeToMinutes(actual) - timeToMinutes(scheduled);
            if (useLegacyBreak) {
                diff -= legacyBreakOverlapMinutes(timeToMinutes(scheduled), timeToMinutes(actual));
            }
            return Math.max(0, diff);
        }

        function calculateUndertimeMinutes(scheduledOut, actualOut, useLegacyBreak = false) {
            if (!scheduledOut || !actualOut) return 0;
            let diff = timeToMinutes(scheduledOut) - timeToMinutes(actualOut);
            if (useLegacyBreak) {
                diff -= legacyBreakOverlapMinutes(timeToMinutes(actualOut), timeToMinutes(scheduledOut));
            }
            return Math.max(0, diff);
        }

        function calculateLegacyFlexibleUndertime(clockIn, clockOut) {
            if (!clockIn || !clockOut) return 0;

            let start = timeToMinutes(clockIn);
            let end = timeToMinutes(clockOut);
            if (end <= start) end += 24 * 60;

            const worked = Math.max(0, end - start - legacyBreakOverlapMinutes(start, end));
            const required = Number(attendancePolicy.required_work_minutes || 480);
            return worked < required ? required - worked : 0;
        }

        function calculateFlexibleUndertime(clockIn, clockOut) {
            if (!clockIn || !clockOut) return 0;

            let start = timeToMinutes(clockIn);
            let end = timeToMinutes(clockOut);
            if (end <= start) end += 24 * 60;

            const breakMinutes = Math.max(0, Number(attendancePolicy.break_minutes || 60));
            const worked = Math.max(0, (end - start) - breakMinutes);
            const required = Number(attendancePolicy.required_work_minutes || 480);
            return worked < required ? required - worked : 0;
        }

        function getAttendancePolicy(dateStr) {
            return isLegacyAttendanceDate(dateStr)
                ? attendancePolicy.legacy_policy
                : attendancePolicy.policy_after_effective_date;
        }

        function recomputeAllLateUndertime() {
            document.querySelectorAll('table tbody tr').forEach(row => {
                const dateInput = row.querySelector('input[name$="[date]"]');
                const clockIn = row.querySelector('input[name$="[clock_in]"]');
                const clockOut = row.querySelector('input[name$="[clock_out]"]');
                const lateInput = row.querySelector('input[name$="[late_minutes]"]');
                const undertimeInput = row.querySelector('input[name$="[undertime_minutes]"]');
                const dateStr = dateInput?.value || '';
                const policy = getAttendancePolicy(dateStr);
                const legacyDate = isLegacyAttendanceDate(dateStr);

                if (lateInput) lateInput.value = 0;
                if (undertimeInput) undertimeInput.value = 0;

                if (policy === 'fixed') {
                    if (scheduledTimeIn && clockIn?.value && lateInput) {
                        lateInput.value = calculateLateMinutes(scheduledTimeIn, clockIn.value, legacyDate);
                    }
                    if (scheduledTimeOut && clockOut?.value && undertimeInput) {
                        undertimeInput.value = calculateUndertimeMinutes(scheduledTimeOut, clockOut.value, legacyDate);
                    }
                    return;
                }

                if (policy === 'legacy_flexible') {
                    if (clockIn?.value && clockOut?.value && undertimeInput) {
                        undertimeInput.value = calculateLegacyFlexibleUndertime(clockIn.value, clockOut.value);
                    }
                    return;
                }

                if (policy === 'flexible_cutoff' && clockIn?.value && lateInput) {
                    lateInput.value = calculateLateMinutes(
                        attendancePolicy.flexible_cutoff_time,
                        clockIn.value,
                        false
                    );
                }

                if (clockIn?.value && clockOut?.value && undertimeInput) {
                    undertimeInput.value = calculateFlexibleUndertime(clockIn.value, clockOut.value);
                }
            });
        }

        document.querySelectorAll('input[type="time"]').forEach(input => input.addEventListener('change', recomputeAllLateUndertime));
        document.addEventListener('DOMContentLoaded', recomputeAllLateUndertime);

        function deleteFile(fileId) {
            if (!confirm('Delete this file?')) return;
            fetch(`/files/${fileId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }).then(res => { if (res.ok) location.reload(); });
        }
    </script>
    @endpush
</x-app-layout>
