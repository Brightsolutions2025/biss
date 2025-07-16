<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Overtime Request Details') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

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

                {{-- Employee --}}
                <div class="mb-3">
                    <label class="form-label">Employee</label>
                    <input type="text" class="form-control" disabled
                        value="{{ $overtimeRequest->employee->last_name }}, {{ $overtimeRequest->employee->first_name }} ({{ $overtimeRequest->employee->employee_number }})">
                </div>

                {{-- Department & Team --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" disabled
                            value="{{ optional($overtimeRequest->employee->department)->name ?? 'N/A' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Team</label>
                        <input type="text" class="form-control" disabled
                            value="{{ optional($overtimeRequest->employee->team)->name ?? 'N/A' }}">
                    </div>
                </div>

                {{-- Date --}}
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" disabled value="{{ $overtimeRequest->date }}">
                </div>

                {{-- Time Start & End --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Time Start</label>
                        <input type="time" class="form-control" disabled value="{{ $overtimeRequest->time_start }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Time End</label>
                        <input type="time" class="form-control" disabled value="{{ $overtimeRequest->time_end }}">
                    </div>
                </div>

                {{-- Number of Hours --}}
                <div class="mb-3">
                    <label class="form-label">Number of Hours</label>
                    <input type="text" class="form-control" disabled value="{{ $overtimeRequest->number_of_hours }}">
                </div>

                {{-- Reason --}}
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea class="form-control" rows="3" disabled>{{ $overtimeRequest->reason }}</textarea>
                </div>

                @php $disabled = 'disabled class=form-control-plaintext'; @endphp

                {{-- Created At --}}
                <div class="mb-3">
                    <label class="form-label">Filed On</label>
                    <input type="text" class="form-control" disabled value="{{ $overtimeRequest->created_at->format('F d, Y h:i A') }}">
                </div>

                {{-- Updated At --}}
                <div class="mb-3">
                    <label class="form-label">Last Updated</label>
                    <input type="text" class="form-control" disabled value="{{ $overtimeRequest->updated_at->format('F d, Y h:i A') }}">
                </div>

                {{-- Status --}}
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <input type="text" class="form-control" disabled value="{{ ucfirst($overtimeRequest->status) }}">
                </div>

                {{-- Approver --}}
                @if ($overtimeRequest->approver)
                    <div class="mb-3">
                        <label class="form-label">Approved By</label>
                        <input type="text" class="form-control" disabled value="{{ $overtimeRequest->approver->name }}">
                    </div>
                @endif

                {{-- Approval Date --}}
                @if ($overtimeRequest->approval_date)
                    <div class="mb-3">
                        <label class="form-label">Approval Date</label>
                        <input type="date" class="form-control" disabled value="{{ $overtimeRequest->approval_date }}">
                    </div>
                @endif

                {{-- Rejection Reason --}}
                @if ($overtimeRequest->status === 'rejected' && $overtimeRequest->rejection_reason)
                    <div class="mb-3">
                        <label class="form-label text-danger">Rejection Reason</label>
                        <textarea class="form-control text-danger bg-light" rows="3" disabled>{{ $overtimeRequest->rejection_reason }}</textarea>
                    </div>
                @endif

                {{-- Expires At --}}
                <div class="mb-3">
                    <label class="form-label">Expires At</label>
                    <input type="text" class="form-control" disabled
                        value="{{ $overtimeRequest->expires_at ?? 'N/A' }}">
                </div>

                @if ($overtimeRequest->files->count())
                    <div class="mb-4">
                        <label class="form-label">Attached Files</label>
                        <ul class="list-unstyled">
                            @foreach ($overtimeRequest->files as $file)
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

                {{-- Actions --}}
                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('overtime_requests.edit', $overtimeRequest->id) }}" class="btn btn-primary">Edit</a>

                    <form method="POST" action="{{ route('overtime_requests.destroy', $overtimeRequest->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to delete this overtime request?')">
                            Delete
                        </button>
                    </form>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        Back
                    </a>
                </div>

                {{-- Approve/Reject --}}
                @if ($overtimeRequest->status === 'pending' && auth()->id() === ($overtimeRequest->employee->approver_id ?? null))
                    <form method="POST" action="{{ route('overtime_requests.approve', $overtimeRequest->id) }}" class="mt-4">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success">Approve</button>
                    </form>

                    <form method="POST" action="{{ route('overtime_requests.reject', $overtimeRequest->id) }}" class="mt-3">
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
                        <button type="submit" class="btn btn-warning">Reject</button>
                    </form>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
