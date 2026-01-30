{{-- resources/views/day_off_change_requests/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Day Off Change Request Details') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                {{-- Success Message --}}
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                {{-- Validation Errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Day Off Change Request Info --}}
                <div class="card shadow-sm border border-2">
                    <div class="card-body">

                        <!-- Company -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Company</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $dayOffChangeRequest->company->name ?? 'N/A' }}" disabled>
                        </div>

                        <!-- Employee -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Employee</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $dayOffChangeRequest->employee->user->name ?? 'N/A' }}" disabled>
                        </div>

                        <!-- Reason Type -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason Type</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $dayOffChangeRequest->reason_type }}" disabled>
                        </div>

                        <!-- Extension Date -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Extension Date</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $dayOffChangeRequest->extension_date->format('Y-m-d') }}" disabled>
                        </div>

                        <!-- Time Range -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Time Start</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $dayOffChangeRequest->time_start }}" disabled>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Time End</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $dayOffChangeRequest->time_end }}" disabled>
                            </div>
                        </div>

                        <!-- Number of Hours -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Number of Hours</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $dayOffChangeRequest->number_of_hours }}" disabled>
                        </div>

                        <!-- Day Off Dates -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Current Day Off</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $dayOffChangeRequest->old_date->format('Y-m-d') }}" disabled>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">New / Offset Day Off</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $dayOffChangeRequest->new_date->format('Y-m-d') }}" disabled>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Detailed Reason</label>
                            <textarea class="form-control bg-light" rows="3" disabled>{{ $dayOffChangeRequest->reason }}</textarea>
                        </div>

                        <!-- Status & Approver -->
                        <hr>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ ucfirst($dayOffChangeRequest->status) }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Approver</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $dayOffChangeRequest->approver?->name ?? 'N/A' }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Approval Date</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ optional($dayOffChangeRequest->approval_date)->format('Y-m-d') ?? 'N/A' }}" disabled>
                        </div>

                        @if($dayOffChangeRequest->status === 'rejected')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rejection Reason</label>
                            <textarea class="form-control bg-light" rows="2" disabled>{{ $dayOffChangeRequest->rejection_reason }}</textarea>
                        </div>
                        @endif

                        <!-- Audit Info -->
                        <hr>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Created At</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $dayOffChangeRequest->created_at->format('Y-m-d H:i') }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Last Updated</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $dayOffChangeRequest->updated_at->format('Y-m-d H:i') }}" disabled>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex gap-2 mt-4">
                            <a href="{{ route('day_off_change_requests.edit', $dayOffChangeRequest->id) }}" class="btn btn-primary">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('day_off_change_requests.destroy', $dayOffChangeRequest->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this request?')">
                                    Delete
                                </button>
                            </form>

                            <a href="{{ route('day_off_change_requests.index') }}" class="btn btn-secondary ms-auto">
                                Back to List
                            </a>
                        </div>

                        {{-- Approve / Reject --}}
                        @if (
                            $dayOffChangeRequest->status === 'pending' &&
                            auth()->id() === ($dayOffChangeRequest->employee->approver_id ?? null)
                        )
                            <hr class="my-4">

                            {{-- Approve --}}
                            <form method="POST"
                                action="{{ route('day_off_change_requests.approve', $dayOffChangeRequest->id) }}"
                                onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Approving...';">
                                @csrf
                                @method('PATCH')

                                <button type="submit" class="btn btn-success">
                                    Approve
                                </button>
                            </form>

                            {{-- Reject --}}
                            <form method="POST"
                                action="{{ route('day_off_change_requests.reject', $dayOffChangeRequest->id) }}"
                                class="mt-3"
                                onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerText='Rejecting...';">
                                @csrf
                                @method('PATCH')

                                <div class="mb-2">
                                    <label for="rejection_reason" class="form-label text-danger fw-semibold">
                                        Reason for Rejection
                                    </label>
                                    <input
                                        type="text"
                                        name="rejection_reason"
                                        id="rejection_reason"
                                        class="form-control @error('rejection_reason') is-invalid @enderror"
                                        value="{{ old('rejection_reason') }}"
                                        required
                                    >
                                    @error('rejection_reason')
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
