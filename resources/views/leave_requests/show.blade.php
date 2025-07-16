<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Leave Request Details') }}
        </h2>
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
                    $disabled = 'disabled class=form-control-plaintext';
                @endphp

                {{-- Employee --}}
                <div class="mb-3">
                    <label class="form-label">Employee</label>
                    <input type="text" class="form-control bg-light" disabled
                        value="{{ $leaveRequest->employee->first_name }} {{ $leaveRequest->employee->last_name }} ({{ $leaveRequest->employee->employee_number }})">
                </div>

                {{-- Company --}}
                <div class="mb-3">
                    <label class="form-label">Company</label>
                    <input type="text" class="form-control bg-light" disabled value="{{ $leaveRequest->employee->company->name }}">
                </div>

                {{-- Start Date --}}
                <div class="mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="text" class="form-control bg-light" disabled value="{{ $leaveRequest->start_date }}">
                </div>

                {{-- End Date --}}
                <div class="mb-3">
                    <label class="form-label">End Date</label>
                    <input type="text" class="form-control bg-light" disabled value="{{ $leaveRequest->end_date }}">
                </div>

                {{-- Number of Days --}}
                <div class="mb-3">
                    <label class="form-label">Number of Days</label>
                    <input type="text" class="form-control bg-light" disabled value="{{ $leaveRequest->number_of_days }}">
                </div>

                {{-- Reason --}}
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea class="form-control bg-light" disabled rows="3">{{ $leaveRequest->reason }}</textarea>
                </div>

                {{-- Created At --}}
                <div class="mb-3">
                    <label class="form-label">Filed On</label>
                    <input type="text" class="form-control bg-light" disabled value="{{ $leaveRequest->created_at->format('F d, Y h:i A') }}">
                </div>

                {{-- Updated At --}}
                <div class="mb-3">
                    <label class="form-label">Last Updated</label>
                    <input type="text" class="form-control bg-light" disabled value="{{ $leaveRequest->updated_at->format('F d, Y h:i A') }}">
                </div>

                {{-- Status --}}
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <input type="text" class="form-control bg-light" disabled value="{{ ucfirst($leaveRequest->status) }}">
                </div>

                {{-- Approver --}}
                <div class="mb-3">
                    <label class="form-label">Approver</label>
                    <input type="text" class="form-control bg-light" disabled value="{{ optional($leaveRequest->approver)->name ?? '—' }}">
                </div>

                {{-- Approval Date --}}
                <div class="mb-3">
                    <label class="form-label">Approval Date</label>
                    <input type="text" class="form-control bg-light" disabled value="{{ $leaveRequest->approval_date ?? '—' }}">
                </div>

                {{-- Rejection Reason --}}
                @if ($leaveRequest->status === 'rejected')
                    <div class="mb-3">
                        <label class="form-label text-danger">Rejection Reason</label>
                        <textarea class="form-control text-danger bg-light" readonly rows="2">{{ $leaveRequest->rejection_reason }}</textarea>
                    </div>
                @endif

                @if ($leaveRequest->files->count())
                    <div class="mb-4">
                        <label class="form-label">Attached Files</label>
                        <ul class="list-unstyled">
                            @foreach ($leaveRequest->files as $file)
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

                {{-- Edit & Delete --}}
                <div class="d-flex gap-2 mb-4">
                    <a href="{{ route('leave_requests.edit', $leaveRequest->id) }}" class="btn btn-outline-primary">Edit</a>

                    <form method="POST" action="{{ route('leave_requests.destroy', $leaveRequest->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"
                            onclick="return confirm('Are you sure you want to delete this leave request?')">
                            Delete
                        </button>
                    </form>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        Back
                    </a>
                </div>

                {{-- Approve and Reject for Approver --}}
                @if (
                    $leaveRequest->status === 'pending' &&
                    auth()->id() === ($leaveRequest->employee->approver_id ?? null)
                )
                    <form method="POST" action="{{ route('leave_requests.approve', $leaveRequest->id) }}" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success">Approve</button>
                    </form>

                    <form method="POST" action="{{ route('leave_requests.reject', $leaveRequest->id) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-2">
                            <label for="reason" class="form-label text-danger">Reason for Rejection</label>
                            <input type="text" name="reason" id="reason" class="form-control is-invalid" required
                                value="{{ old('reason') }}">
                            @error('reason')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-warning">Reject</button>
                    </form>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
