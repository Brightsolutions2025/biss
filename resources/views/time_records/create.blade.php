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

                        <form method="POST" action="{{ route('time_records.store') }}" enctype="multipart/form-data"
                            onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerText='Submitting...';">
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

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="corrected_start_date" class="form-label">Corrected Start Date</label>
                                    <input type="date" id="corrected_start_date" name="corrected_start_date" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label for="corrected_end_date" class="form-label">Corrected End Date</label>
                                    <input type="date" id="corrected_end_date" name="corrected_end_date" class="form-control">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" id="apply-date-correction" class="btn btn-outline-primary">
                                        Apply Dates
                                    </button>
                                </div>
                            </div>

                            <!-- Time Record Lines -->
                            <div class="table-responsive mb-4" id="record-lines-wrapper" style="zoom: 0.85;">
                                <table class="table table-bordered table-sm align-middle text-center mb-0 compact-table">
                                    <thead class="table-light sticky-top">
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
                                            <th>Schedule Remark</th>
                                            <th>Remarks</th>
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
                                <input id="files" name="files[]" type="file" class="form-control" multiple>
                                @error('files.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <a href="javascript:history.back()" class="btn btn-secondary">Cancel</a>
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

        function extractTimeOnly(datetime) {
            if (!datetime) return '';
            const match = datetime.match(/(\d{2}:\d{2})/);
            return match ? match[0] : '';
        }

        function timeToMinutes(time) {
            const [h, m] = time.split(':').map(Number);
            return h * 60 + m;
        }

        function calculateLateMinutes(scheduled, actual) {
            if (!scheduled || !actual) return 0;
            const sched = timeToMinutes(scheduled);
            const act = timeToMinutes(actual);
            if (act <= sched) return 0;
            let diff = act - sched;
            // lunch overlap adjustment
            const lunchStart = 12*60, lunchEnd = 13*60;
            const overlapStart = Math.max(sched, lunchStart), overlapEnd = Math.min(act, lunchEnd);
            if (overlapEnd > overlapStart) diff -= (overlapEnd - overlapStart);
            return Math.max(0, diff);
        }

        function calculateUndertimeMinutes(scheduledOut, actualOut) {
            if (!scheduledOut || !actualOut) return 0;
            const sched = timeToMinutes(scheduledOut);
            const act = timeToMinutes(actualOut);
            if (act >= sched) return 0;
            let diff = sched - act;
            // lunch overlap adjustment
            const lunchStart = 12*60, lunchEnd = 13*60;
            const overlapStart = Math.max(act, lunchStart), overlapEnd = Math.min(sched, lunchEnd);
            if (overlapEnd > overlapStart) diff -= (overlapEnd - overlapStart);
            return Math.max(0, diff);
        }

        function determineClockInOrOut(attendanceTime, shiftIn, shiftOut) {
            if (!attendanceTime) return { clockIn: '', clockOut: '' };
            const logMins = timeToMinutes(attendanceTime);
            const inDiff = shiftIn ? Math.abs(logMins - timeToMinutes(shiftIn)) : Infinity;
            const outDiff = shiftOut ? Math.abs(logMins - timeToMinutes(shiftOut)) : Infinity;
            return inDiff <= outDiff ? { clockIn: attendanceTime, clockOut: '' } : { clockIn: '', clockOut: attendanceTime };
        }

        async function fetchTimeData(startDate, endDate) {
            const [timeLogs, overtimeRequests, outbaseRequests, offsetRequests, leaveRequests, dayOffChanges] = await Promise.all([
                fetch(`/time_records/${currentEmployeeId}/${startDate}/${endDate}`).then(r=>r.json()),
                fetch(`/overtime_requests/${currentEmployeeId}/${startDate}/${endDate}`).then(r=>r.json()),
                fetch(`/outbase_requests/${currentEmployeeId}/${startDate}/${endDate}`).then(r=>r.json()),
                fetch(`/offset_requests/${currentEmployeeId}/${startDate}/${endDate}`).then(r=>r.json()),
                fetch(`/leave_requests/${currentEmployeeId}/${startDate}/${endDate}`).then(r=>r.json()),
                fetch(`/day_off_change_requests/${currentEmployeeId}/${startDate}/${endDate}`).then(r=>r.json())
            ]);
            return { timeLogs, overtimeRequests, outbaseRequests, offsetRequests, leaveRequests, dayOffChanges };
        }

        function renderRows(data, startDate, endDate) {
            const tbody = document.getElementById('record-lines-body');
            tbody.innerHTML = '';
            const start = new Date(startDate);
            const end = new Date(endDate);
            for (let i=0, dt=new Date(start); dt<=end; dt.setDate(dt.getDate()+1), i++) {
                const dateStr = dt.toLocaleDateString('sv-SE');
                const logs = data.timeLogs[dateStr] || {};
                const overtime = data.overtimeRequests[dateStr] || { hours:0,start:'',end:'' };
                const outbase = data.outbaseRequests[dateStr] || { start:'', end:'' };
                const offset = data.offsetRequests[dateStr] || { hours:0,start:'',end:'' };
                const leave = data.leaveRequests.dates?.[dateStr] || { days:0, with_pay:false };
                const remainingCredits = data.leaveRequests.remaining_credits_by_date?.[dateStr] ?? '';
                const scheduleRemark = data.dayOffChanges[dateStr] ?? '';

                const timeEntries = Object.values(logs).map(extractTimeOnly).filter(t=>t.match(/^\d{2}:\d{2}$/));
                let clockIn='', clockOut='';
                if (timeEntries.length===1) {
                    const r = determineClockInOrOut(timeEntries[0], scheduledTimeIn, scheduledTimeOut);
                    clockIn=r.clockIn; clockOut=r.clockOut;
                } else if (timeEntries.length>=2) {
                    timeEntries.sort(); clockIn=timeEntries[0]; clockOut=timeEntries[1];
                }

                const row = document.createElement('tr');
                row.innerHTML=`
                    <td><input type="text" readonly name="time_record_lines[${i}][date]" class="form-control form-control-sm text-center" value="${dateStr}"></td>
                    <td>${dt.toLocaleDateString('en-US',{ weekday:'short'})}</td>
                    <td><input type="time" readonly name="time_record_lines[${i}][clock_in]" class="form-control form-control-sm clock-in-input" value="${clockIn}"></td>
                    <td><input type="time" readonly name="time_record_lines[${i}][clock_out]" class="form-control form-control-sm clock-out-input" value="${clockOut}"></td>
                    <td><input type="number" readonly name="time_record_lines[${i}][late_minutes]" class="form-control form-control-sm late-minutes-input" step="0.01"></td>
                    <td><input type="number" readonly name="time_record_lines[${i}][undertime_minutes]" class="form-control form-control-sm undertime-minutes-input" step="0.01"></td>
                    <td><input type="time" readonly name="time_record_lines[${i}][overtime_time_start]" class="form-control form-control-sm" value="${extractTimeOnly(overtime.start)}"></td>
                    <td><input type="time" readonly name="time_record_lines[${i}][overtime_time_end]" class="form-control form-control-sm" value="${extractTimeOnly(overtime.end)}"></td>
                    <td><input type="number" readonly name="time_record_lines[${i}][overtime_hours]" class="form-control form-control-sm" value="${overtime.hours}" step="0.01"></td>
                    <td><input type="time" readonly name="time_record_lines[${i}][offset_time_start]" class="form-control form-control-sm" value="${offset.start}"></td>
                    <td><input type="time" readonly name="time_record_lines[${i}][offset_time_end]" class="form-control form-control-sm" value="${offset.end}"></td>
                    <td><input type="number" readonly name="time_record_lines[${i}][offset_hours]" class="form-control form-control-sm" value="${offset.hours}" step="0.01"></td>
                    <td><input type="time" readonly name="time_record_lines[${i}][outbase_time_start]" class="form-control form-control-sm" value="${outbase.start}"></td>
                    <td><input type="time" readonly name="time_record_lines[${i}][outbase_time_end]" class="form-control form-control-sm" value="${outbase.end}"></td>
                    <td><input type="number" readonly name="time_record_lines[${i}][leave_days]" class="form-control form-control-sm" value="${parseFloat(leave.days||0).toFixed(2)}" step="0.01"></td>
                    <td><input type="number" readonly name="time_record_lines[${i}][remaining_leave_credits]" class="form-control form-control-sm" value="${remainingCredits}" step="0.01"></td>
                    <td><span>${leave.with_pay?'Yes':'No'}</span><input type="hidden" name="time_record_lines[${i}][leave_with_pay]" value="${leave.with_pay?1:0}"></td>
                    <td class="text-warning fw-semibold"><input type="text" name="time_record_lines[${i}][schedule_remark]" class="form-control form-control-sm" value="${scheduleRemark}"></td>
                    <td class="remarks-cell"><input type="text" name="time_record_lines[${i}][remarks]" class="form-control form-control-sm remarks-input"></td>
                `;
                tbody.appendChild(row);
            }
        }

        async function updateTable(startDate, endDate) {
            const data = await fetchTimeData(startDate,endDate);
            renderRows(data, startDate, endDate);
        }

        document.getElementById('payroll_period_id').addEventListener('change', function() {
            const selected = this.selectedOptions[0];
            const start = selected.dataset.start, end = selected.dataset.end;
            if (start && end) updateTable(start,end);
        });

        document.getElementById('apply-date-correction').addEventListener('click', function() {
            const start = document.getElementById('corrected_start_date').value;
            const end = document.getElementById('corrected_end_date').value;
            if (!start || !end) return alert('Please enter both start and end dates.');
            updateTable(start,end);
        });

        window.addEventListener('DOMContentLoaded', () => {
            const payrollSelect = document.getElementById('payroll_period_id');
            if (payrollSelect.value) payrollSelect.dispatchEvent(new Event('change'));
        });
    </script>
</x-app-layout>
