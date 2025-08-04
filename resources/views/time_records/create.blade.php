<?php use App\Models\OvertimeRequest; ?>

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

    .remarks-cell {
        min-width: 250px;
        width: 25%;
    }

    .remarks-input {
        width: 100%;
    }

    /* Optional: Prevent squishing on mobile */
    @media (max-width: 768px) {
        .remarks-cell {
            min-width: 200px;
        }
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Add New Time Record') }}
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

                        <form method="POST" action="{{ route('time_records.store') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Employee</label>
                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                <input type="text" class="form-control-plaintext" disabled
                                    value="{{ $employee->employee_number . ' - ' . $employee->last_name . ', ' . $employee->first_name }}">
                            </div>

                            <div class="mb-4">
                                <label for="payroll_period_id" class="form-label">Select Payroll Period</label>
                                <select id="payroll_period_id" name="payroll_period_id" class="form-select" required>
                                    <option value="">-- Choose Payroll Period --</option>
                                    @foreach ($payrollPeriods as $period)
                                        <option value="{{ $period->id }}"
                                            data-start="{{ $period->start_date }}"
                                            data-end="{{ $period->end_date }}"
                                            {{ old('payroll_period_id') == $period->id ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::parse($period->start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($period->end_date)->format('M d, Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="table-responsive mb-4" id="record-lines-wrapper" style="zoom: 0.85;">
                                <table class="table table-bordered table-sm align-middle text-center mb-0 compact-table">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th scope="col">Date</th>
                                            <th scope="col">Day</th>
                                            <th scope="col">Clock In</th>
                                            <th scope="col">Clock Out</th>
                                            <th scope="col">Late</th>
                                            <th scope="col">Undertime</th>
                                            <th scope="col">OT Start</th>
                                            <th scope="col">OT End</th>
                                            <th scope="col">OT Hours</th>
                                            <th scope="col">Offset Start</th>
                                            <th scope="col">Offset End</th>
                                            <th scope="col">Offset Hours</th>
                                            <th scope="col">Outbase Start</th>
                                            <th scope="col">Outbase End</th>
                                            <th scope="col">Leave Days</th>
                                            <th scope="col">Remaining Leave Credits</th>
                                            <th scope="col">With Pay?</th>
                                            <th scope="col">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody id="record-lines-body">
                                        <!-- JavaScript-generated rows -->
                                    </tbody>
                                </table>
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
                                >
                                @error('files.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Submit') }}
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

    @php
        $overtimeByDate = OvertimeRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$payrollPeriodStart ?? now()->startOfMonth(), $payrollPeriodEnd ?? now()->endOfMonth()])
            ->get()
            ->groupBy('date')
            ->mapWithKeys(fn($group, $date) => [$date => $group->sum('number_of_hours')]);
    @endphp

    <script>
        const currentEmployeeId = @json($employee->id);
        const isFlexible = @json($employee->flexible_time);
        const scheduledTimeIn = @json(optional(optional($employee->employeeShift)->shift)->time_in);
        const scheduledTimeOut = @json(optional(optional($employee->employeeShift)->shift)->time_out);
        const approvedOvertimeHours = @json($overtimeByDate);

        function determineClockInOrOut(attendanceTime, shiftIn, shiftOut) {
            if (!attendanceTime || !shiftIn || !shiftOut) return { clockIn: '', clockOut: '' };

            const logMinutes = timeToMinutes(attendanceTime);
            const shiftInMinutes = timeToMinutes(shiftIn);
            const shiftOutMinutes = timeToMinutes(shiftOut);

            const diffToIn = Math.abs(logMinutes - shiftInMinutes);
            const diffToOut = Math.abs(logMinutes - shiftOutMinutes);

            if (diffToIn <= diffToOut) {
                return { clockIn: attendanceTime, clockOut: '' };
            } else {
                return { clockIn: '', clockOut: attendanceTime };
            }
        }

        function timeToMinutes(time) {
            const [h, m] = time.split(':').map(Number);
            return h * 60 + m;
        }

        function extractTimeOnly(datetime) {
            if (typeof datetime !== 'string') return '';
            const match = datetime.match(/(\d{2}:\d{2})(:\d{2})?/);
            return match ? match[0].substring(0, 5) : '';
        }

        function calculateLateMinutes(scheduled, actual) {
            if (!scheduled || !actual) return 0;
            const [shH, shM] = scheduled.split(':').map(Number);
            const [acH, acM] = actual.split(':').map(Number);
            return Math.max(0, (acH * 60 + acM) - (shH * 60 + shM));
        }

        function calculateUndertimeMinutes(scheduledOut, actualOut) {
            if (!scheduledOut || !actualOut) return 0;
            const [shH, shM] = scheduledOut.split(':').map(Number);
            const [acH, acM] = actualOut.split(':').map(Number);
            return Math.max(0, (shH * 60 + shM) - (acH * 60 + acM));
        }

        document.getElementById('payroll_period_id').addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const startDate = selected.getAttribute('data-start');
            const endDate = selected.getAttribute('data-end');
            const tbody = document.getElementById('record-lines-body');
            tbody.innerHTML = '';

            if (!startDate || !endDate) return;

            Promise.all([
                fetch(`/time_records/${currentEmployeeId}/${startDate}/${endDate}`).then(res => res.json()),
                fetch(`/overtime_requests/${currentEmployeeId}/${startDate}/${endDate}`).then(res => res.json()),
                fetch(`/outbase_requests/${currentEmployeeId}/${startDate}/${endDate}`).then(res => res.json()),
                fetch(`/offset_requests/${currentEmployeeId}/${startDate}/${endDate}`).then(res => res.json()),
                fetch(`/leave_requests/${currentEmployeeId}/${startDate}/${endDate}`).then(res => res.json())
            ]).then(([timeLogs, overtimeRequests, outbaseRequests, offsetRequests, leaveRequests]) => {
                const start = new Date(startDate);
                const end = new Date(endDate);

                if (start > end) {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td colspan="18" class="text-muted text-center">No data for selected payroll period.</td>`;
                    tbody.appendChild(row);
                    return;
                }

                for (let i = 0, dt = new Date(start); dt <= end; dt.setDate(dt.getDate() + 1), i++) {
                    const dateStr = dt.toISOString().split('T')[0];
                    const logsForDate = timeLogs[dateStr] || {};
                    const overtimeForDate = overtimeRequests[dateStr] || { hours: 0, start: '', end: '' };
                    const outbaseForDate = outbaseRequests[dateStr] || { start: '', end: '' };
                    const offsetForDate = offsetRequests[dateStr] || { hours: 0, start: '', end: '' };
                    const leaveForDate = leaveRequests.dates?.[dateStr] || { days: 0, with_pay: '' };
                    const remainingCredits = leaveRequests.remaining_credits_by_date?.[dateStr] ?? '';

                    const timeEntries = Object.values(logsForDate)
                        .map(val => extractTimeOnly(val))
                        .filter(time => /^\d{2}:\d{2}$/.test(time));

                    let clockIn = '';
                    let clockOut = '';

                    if (timeEntries.length === 1) {
                        const actualTime = timeEntries[0];
                        const result = determineClockInOrOut(actualTime, scheduledTimeIn, scheduledTimeOut);
                        clockIn = result.clockIn;
                        clockOut = result.clockOut;
                    } else {
                        // If there's multiple entries, try to assign Clock In and Out based on time proximity
                        timeEntries.sort(); // earliest to latest
                        if (timeEntries.length >= 2) {
                            clockIn = timeEntries[0];
                            clockOut = timeEntries[1]; // or last, depending on your policy
                        }
                    }


                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><input type="text" readonly name="time_record_lines[${i}][date]" class="form-control form-control-sm text-center" value="${dateStr}"></td>
                        <td>${new Date(dateStr).toLocaleDateString('en-US', { weekday: 'short' })}</td>
                        <td><input type="time" readonly name="time_record_lines[${i}][clock_in]" class="form-control form-control-sm clock-in-input" value="${clockIn}"></td>
                        <td><input type="time" readonly name="time_record_lines[${i}][clock_out]" class="form-control form-control-sm clock-out-input" value="${clockOut}"></td>
                        <td><input type="number" readonly name="time_record_lines[${i}][late_minutes]" 
                            class="form-control form-control-sm late-minutes-input" 
                            style="min-width: 80px;" step="0.01">
                        </td>
                        <td><input type="number" readonly name="time_record_lines[${i}][undertime_minutes]" class="form-control form-control-sm undertime-minutes-input" step="0.01"></td>
                        <td><input type="time" readonly name="time_record_lines[${i}][overtime_time_start]" class="form-control form-control-sm" value="${extractTimeOnly(overtimeForDate.start)}"></td>
                        <td><input type="time" readonly name="time_record_lines[${i}][overtime_time_end]" class="form-control form-control-sm" value="${extractTimeOnly(overtimeForDate.end)}"></td>
                        <td><input type="number" readonly name="time_record_lines[${i}][overtime_hours]" class="form-control form-control-sm" value="${overtimeForDate.hours}" step="0.01"></td>
                        <td><input type="time" readonly name="time_record_lines[${i}][offset_time_start]" class="form-control form-control-sm" value="${offsetForDate.start}"></td>
                        <td><input type="time" readonly name="time_record_lines[${i}][offset_time_end]" class="form-control form-control-sm" value="${offsetForDate.end}"></td>
                        <td><input type="number" readonly name="time_record_lines[${i}][offset_hours]" class="form-control form-control-sm" value="${offsetForDate.hours}" step="0.01"></td>
                        <td><input type="time" readonly name="time_record_lines[${i}][outbase_time_start]" class="form-control form-control-sm" value="${outbaseForDate.start}"></td>
                        <td><input type="time" readonly name="time_record_lines[${i}][outbase_time_end]" class="form-control form-control-sm" value="${outbaseForDate.end}"></td>
                        <td><input type="number" readonly name="time_record_lines[${i}][leave_days]" class="form-control form-control-sm" value="${parseFloat(leaveForDate.days || 0).toFixed(2)}" step="0.01"></td>
                        <td><input type="number" readonly name="time_record_lines[${i}][remaining_leave_credits]" class="form-control form-control-sm" value="${remainingCredits}" step="0.01"></td>
                        <td>
                            <span>${leaveForDate.with_pay ? 'Yes' : 'No'}</span>
                            <input type="hidden" name="time_record_lines[${i}][leave_with_pay]" value="${leaveForDate.with_pay ? 1 : 0}">
                        </td>
                        <td class="remarks-cell"><input type="text" name="time_record_lines[${i}][remarks]" class="form-control form-control-sm remarks-input"></td>
                    `;
                    tbody.appendChild(row);

                    if (!isFlexible && scheduledTimeIn) {
                        const clockInInput = row.querySelector('.clock-in-input');
                        const lateInput = row.querySelector('.late-minutes-input');
                        if (clockInInput && clockInInput.value && lateInput) {
                            lateInput.value = calculateLateMinutes(scheduledTimeIn, clockInInput.value);
                        }
                    }

                    if (!isFlexible && scheduledTimeOut) {
                        const clockOutInput = row.querySelector('.clock-out-input');
                        const undertimeInput = row.querySelector('.undertime-minutes-input');
                        if (clockOutInput && clockOutInput.value && undertimeInput) {
                            undertimeInput.value = calculateUndertimeMinutes(scheduledTimeOut, clockOutInput.value);
                        }
                    }
                }
            });
        });

        window.addEventListener('DOMContentLoaded', () => {
            const payrollSelect = document.getElementById('payroll_period_id');
            if (payrollSelect.value) {
                payrollSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</x-app-layout>
