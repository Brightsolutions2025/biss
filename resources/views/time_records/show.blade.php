<style>
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
            {{ __('Time Record Details') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12">

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

                <!-- Employee Info -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Employee</label>
                        <input type="text" class="form-control" disabled
                               value="{{ $timeRecord->employee->employee_number }} - {{ $timeRecord->employee->last_name }}, {{ $timeRecord->employee->first_name }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Payroll Period</label>
                        <input type="text" class="form-control" disabled
                               value="{{ \Carbon\Carbon::parse($timeRecord->payrollPeriod->start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($timeRecord->payrollPeriod->end_date)->format('M d, Y') }}">
                    </div>
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
                                <th>Offset Start</th>
                                <th>Offset End</th>
                                <th>Offset Hours</th>
                                <th>Outbase Start</th>
                                <th>Outbase End</th>
                                <th>Leave Days</th>
                                <th>Remaining Leave</th>
                                <th>With Pay?</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($timeRecord->lines as $line)
                                <tr>
                                    <td class="text-center">{{ $line->date }}</td>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($line->date)->format('l') }}</td>
                                    <td class="text-center">{{ $line->clock_in }}</td>
                                    <td class="text-center">{{ $line->clock_out }}</td>
                                    <td class="text-center">{{ $line->late_minutes == 0 ? '' : $line->late_minutes }}</td>
                                    <td class="text-center">{{ $line->undertime_minutes == 0 ? '' : $line->undertime_minutes }}</td>
                                    <td class="text-center">{{ $line->overtime_time_start }}</td>
                                    <td class="text-center">{{ $line->overtime_time_end }}</td>
                                    <td class="text-center">{{ $line->overtime_hours == 0 ? '' : $line->overtime_hours }}</td>
                                    <td class="text-center">{{ $line->offset_time_start }}</td>
                                    <td class="text-center">{{ $line->offset_time_end }}</td>
                                    <td class="text-center">{{ $line->offset_hours == 0 ? '' : $line->offset_hours }}</td>
                                    <td class="text-center">{{ $line->outbase_time_start }}</td>
                                    <td class="text-center">{{ $line->outbase_time_end }}</td>
                                    <td class="text-center">{{ $line->leave_days == 0 ? '' : $line->leave_days }}</td>
                                    <td class="text-center">{{ $line->remaining_leave_credits == 0 ? '' : $line->remaining_leave_credits }}</td>
                                    <td class="text-center">{{ $line->leave_with_pay ? 'Yes' : '' }}</td>
                                    <td>{{ $line->remarks }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @php $disabled = 'disabled class=form-control-plaintext'; @endphp

                {{-- Created At --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Filed On</label>
                    <input type="text" class="form-control" disabled value="{{ $timeRecord->created_at->format('F d, Y h:i A') }}">
                </div>

                {{-- Updated At --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Last Updated</label>
                    <input type="text" class="form-control" disabled value="{{ $timeRecord->updated_at->format('F d, Y h:i A') }}">
                </div>


                <!-- Status Display -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <input type="text" class="form-control" disabled value="{{ ucfirst($timeRecord->status) }}">
                </div>

                <!-- Approval Metadata -->
                @if ($timeRecord->status === 'approved' && $timeRecord->approver)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Approved By</label>
                        <input type="text" class="form-control" disabled value="{{ $timeRecord->approver->name }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Approval Date</label>
                        <input type="text" class="form-control" disabled value="{{ $timeRecord->approval_date }}">
                    </div>
                @elseif ($timeRecord->status === 'rejected')
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rejected By</label>
                        <input type="text" class="form-control" disabled value="{{ optional($timeRecord->approver)->name }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-danger">Rejection Reason</label>
                        <textarea class="form-control bg-light text-danger" disabled rows="3">{{ $timeRecord->rejection_reason }}</textarea>
                    </div>
                @endif

                @if ($timeRecord->files->count())
                    <div class="mb-4">
                        <label class="form-label">Attached Files</label>
                        <ul class="list-unstyled">
                            @foreach ($timeRecord->files as $file)
                                <li class="mb-2">
                                    <a href="{{ route('files.download', $file->id) }}" target="_blank" class="d-inline-flex align-items-center gap-2">
                                        @php
                                            $extension = pathinfo($file->file_name, PATHINFO_EXTENSION);
                                        @endphp

                                        @switch($extension)
                                            @case('pdf')
                                                <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                @break
                                            @case('jpg')
                                            @case('jpeg')
                                            @case('png')
                                                <i class="bi bi-image text-primary"></i>
                                                @break
                                            @case('doc')
                                            @case('docx')
                                                <i class="bi bi-file-earmark-word text-primary"></i>
                                                @break
                                            @case('xlsx')
                                                <i class="bi bi-file-earmark-excel text-success"></i>
                                                @break
                                            @default
                                                <i class="bi bi-paperclip"></i>
                                        @endswitch

                                        {{ $file->file_name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="{{ route('time_records.edit', $timeRecord->id) }}" class="btn btn-primary">Edit</a>
                    <form method="POST" action="{{ route('time_records.destroy', $timeRecord->id) }}" class="m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Are you sure you want to delete this time record?')" class="btn btn-danger">
                            Delete
                        </button>
                    </form>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        Back
                    </a>
                </div>

                <!-- Approve/Reject Actions -->
                @if (
                    $timeRecord->status === 'pending' &&
                    auth()->id() === ($timeRecord->employee->approver_id ?? null)
                )
                    <form method="POST" action="{{ route('time_records.approve', $timeRecord->id) }}" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success">Approve</button>
                    </form>

                    <form method="POST" action="{{ route('time_records.reject', $timeRecord->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-2">
                            <label for="reason" class="form-label text-danger">Reason for Rejection</label>
                            <input type="text" name="reason" id="reason" required class="form-control border-danger" value="{{ old('reason') }}">
                            @error('reason')
                                <div class="form-text text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-warning">Reject</button>
                    </form>
                @endif

                <!-- Download Buttons -->
                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('time_records.export.pdf', ['id' => $timeRecord->id]) }}" class="btn btn-outline-primary">Download PDF</a>
                    <a href="{{ route('time_records.export.excel', ['id' => $timeRecord->id]) }}" class="btn btn-outline-success">Export Excel</a>
                </div>

                <hr class="my-5">

                <h5 class="mt-5">Related Requests</h5>

                {{-- OVERTIME REQUESTS --}}
                @if ($overtimeRequests->count())
                    <h6 class="mt-4">Overtime Requests</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm compact-table align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Date</th>
                                    <th>Time Start</th>
                                    <th>Time End</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($overtimeRequests as $request)
                                    <tr>
                                        <td class="text-center">{{ $request->date }}</td>
                                        <td class="text-center">{{ $request->time_start }}</td>
                                        <td class="text-center">{{ $request->time_end }}</td>
                                        <td class="text-center">{{ $request->number_of_hours }}</td>
                                        <td class="text-center">{{ ucfirst($request->status) }}</td>
                                        <td>{{ $request->reason }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('overtime_requests.show', $request->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- LEAVE REQUESTS --}}
                @if ($leaveRequests->count())
                    <h6 class="mt-4">Leave Requests</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm compact-table align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leaveRequests as $request)
                                    <tr>
                                        <td class="text-center">{{ $request->start_date }}</td>
                                        <td class="text-center">{{ $request->end_date }}</td>
                                        <td class="text-center">{{ $request->number_of_days }}</td>
                                        <td class="text-center">{{ ucfirst($request->status) }}</td>
                                        <td>{{ $request->reason }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('leave_requests.show', $request->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- OUTBASE REQUESTS --}}
                @if ($outbaseRequests->count())
                    <h6 class="mt-4">Outbase Requests</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm compact-table align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Date</th>
                                    <th>Time Start</th>
                                    <th>Time End</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($outbaseRequests as $request)
                                    <tr>
                                        <td class="text-center">{{ $request->date }}</td>
                                        <td class="text-center">{{ $request->time_start }}</td>
                                        <td class="text-center">{{ $request->time_end }}</td>
                                        <td>{{ $request->location }}</td>
                                        <td class="text-center">{{ ucfirst($request->status) }}</td>
                                        <td>{{ $request->reason }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('outbase_requests.show', $request->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- OFFSET REQUESTS --}}
                @if ($offsetRequests->count())
                    <h6 class="mt-4">Offset Requests</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm compact-table align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Date</th>
                                    <th>Time Start</th>
                                    <th>Time End</th>
                                    <th>Hours</th>
                                    <th>Project/Event</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($offsetRequests as $request)
                                    <tr>
                                        <td class="text-center">{{ $request->date }}</td>
                                        <td class="text-center">{{ $request->time_start }}</td>
                                        <td class="text-center">{{ $request->time_end }}</td>
                                        <td class="text-center">{{ $request->number_of_hours }}</td>
                                        <td>{{ $request->project_or_event_description }}</td>
                                        <td class="text-center">{{ ucfirst($request->status) }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('offset_requests.show', $request->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
