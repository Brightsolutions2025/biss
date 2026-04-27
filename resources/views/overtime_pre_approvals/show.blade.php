{{-- resources/views/overtime_pre_approvals/show.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Overtime Pre-Approval Details') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

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

                <div class="card shadow-sm border border-2">
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Company</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $overtimePreApproval->company->name ?? 'N/A' }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Employee</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $overtimePreApproval->employee->last_name ?? 'N/A' }}, {{ $overtimePreApproval->employee->first_name ?? '' }} {{ $overtimePreApproval->employee?->employee_number ? '(' . $overtimePreApproval->employee->employee_number . ')' : '' }}" disabled>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Department</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ optional($overtimePreApproval->employee->department)->name ?? 'N/A' }}" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Team</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ optional($overtimePreApproval->employee->team)->name ?? 'N/A' }}" disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Overtime Date</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ optional($overtimePreApproval->date)->format('Y-m-d') ?? 'N/A' }}" disabled>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Planned Time Start</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $overtimePreApproval->planned_time_start ? \Illuminate\Support\Carbon::parse($overtimePreApproval->planned_time_start)->format('h:i A') : 'N/A' }}" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Planned Time End</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $overtimePreApproval->planned_time_end ? \Illuminate\Support\Carbon::parse($overtimePreApproval->planned_time_end)->format('h:i A') : 'N/A' }}" disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estimated Number of Hours</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ number_format($overtimePreApproval->estimated_number_of_hours ?? 0, 2) }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason for Overtime</label>
                            <textarea class="form-control bg-light" rows="3" disabled>{{ $overtimePreApproval->reason ?? 'N/A' }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Planned Tasks / Deliverables</label>
                            <textarea class="form-control bg-light" rows="3" disabled>{{ $overtimePreApproval->planned_tasks ?? 'N/A' }}</textarea>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ ucfirst($overtimePreApproval->status ?? 'pending') }}" disabled>
                        </div>

                        @if ($overtimePreApproval->approver)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Approved / Rejected By</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $overtimePreApproval->approver->name }}" disabled>
                            </div>
                        @endif

                        @if ($overtimePreApproval->approval_date)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Approval Date</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ optional($overtimePreApproval->approval_date)->format('Y-m-d') ?? 'N/A' }}" disabled>
                            </div>
                        @endif

                        @if ($overtimePreApproval->status === 'rejected' && $overtimePreApproval->rejection_reason)
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-danger">Rejection Reason</label>
                                <textarea class="form-control text-danger bg-light" rows="3" disabled>{{ $overtimePreApproval->rejection_reason }}</textarea>
                            </div>
                        @endif

                        <hr>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Filed On</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ optional($overtimePreApproval->created_at)->format('F d, Y h:i A') ?? 'N/A' }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Last Updated</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ optional($overtimePreApproval->updated_at)->format('F d, Y h:i A') ?? 'N/A' }}" disabled>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <a href="{{ route('overtime_pre_approvals.edit', $overtimePreApproval->id) }}" class="btn btn-primary">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('overtime_pre_approvals.destroy', $overtimePreApproval->id) }}">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this overtime pre-approval?')">
                                    Delete
                                </button>
                            </form>

                            <a href="javascript:history.back()" class="btn btn-secondary ms-auto">
                                Back
                            </a>
                        </div>

                        @if ($overtimePreApproval->status === 'pending' && auth()->id() === ($overtimePreApproval->employee->approver_id ?? null))
                            <hr>

                            <form method="POST" action="{{ route('overtime_pre_approvals.approve', $overtimePreApproval->id) }}" class="mt-4"
                                onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerText='Approving...';">
                                @csrf
                                @method('PATCH')

                                <button type="submit" class="btn btn-success">
                                    Approve
                                </button>
                            </form>

                            <form method="POST" action="{{ route('overtime_pre_approvals.reject', $overtimePreApproval->id) }}" class="mt-3"
                                onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerText='Rejecting...';">
                                @csrf
                                @method('PATCH')

                                <div class="mb-2">
                                    <label for="reason" class="form-label text-danger">Reason for Rejection</label>
                                    <input type="text" name="reason" id="reason" class="form-control is-invalid"
                                        value="{{ old('reason') }}" required>

                                    @error('reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-warning">
                                    Reject
                                </button>
                            </form>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>