<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Outbase Request Details') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="card shadow-sm">
                <div class="card-body">

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Employee -->
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control" disabled value="{{ $outbaseRequest->employee->first_name }} {{ $outbaseRequest->employee->last_name }}">
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" disabled value="{{ $outbaseRequest->date }}">
                    </div>

                    <!-- Time Start -->
                    <div class="mb-3">
                        <label class="form-label">Time Start</label>
                        <input type="text" class="form-control" disabled value="{{ $outbaseRequest->time_start }}">
                    </div>

                    <!-- Time End -->
                    <div class="mb-3">
                        <label class="form-label">Time End</label>
                        <input type="text" class="form-control" disabled value="{{ $outbaseRequest->time_end }}">
                    </div>

                    <!-- Location -->
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" disabled value="{{ $outbaseRequest->location }}">
                    </div>

                    <!-- Reason -->
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" rows="3" disabled>{{ $outbaseRequest->reason }}</textarea>
                    </div>

                    @php
                        $disabled = 'disabled class=form-control-plaintext';
                    @endphp

                    {{-- Created At --}}
                    <div class="mb-3">
                        <label class="form-label">Filed On</label>
                        <input type="text" class="form-control" disabled value="{{ $outbaseRequest->created_at->format('F d, Y h:i A') }}">
                    </div>

                    {{-- Updated At --}}
                    <div class="mb-3">
                        <label class="form-label">Last Updated</label>
                        <input type="text" class="form-control" disabled value="{{ $outbaseRequest->updated_at->format('F d, Y h:i A') }}">
                    </div>


                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <input type="text" class="form-control" disabled value="{{ ucfirst($outbaseRequest->status) }}">
                    </div>

                    <!-- Approval Metadata -->
                    @if ($outbaseRequest->status === 'approved' && $outbaseRequest->approver)
                        <div class="mb-3">
                            <label class="form-label">Approved By</label>
                            <input type="text" class="form-control" disabled value="{{ $outbaseRequest->approver->name }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Approval Date</label>
                            <input type="text" class="form-control" disabled value="{{ $outbaseRequest->approval_date ?? '' }}">
                        </div>
                    @elseif ($outbaseRequest->status === 'rejected')
                        <div class="mb-3">
                            <label class="form-label">Rejected By</label>
                            <input type="text" class="form-control" disabled value="{{ optional($outbaseRequest->approver)->name }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason</label>
                            <textarea class="form-control" rows="3" disabled>{{ $outbaseRequest->rejection_reason }}</textarea>
                        </div>
                    @endif

                    @if ($outbaseRequest->files->count())
                        <div class="mb-4">
                            <label class="form-label">Attached Files</label>
                            <ul class="list-unstyled">
                                @foreach ($outbaseRequest->files as $file)
                                    <li class="mb-2">
                                        <a href="{{ route('files.download', $file->id) }}" target="_blank" class="d-inline-flex align-items-center gap-2">
                                            @php $extension = pathinfo($file->file_name, PATHINFO_EXTENSION); @endphp

                                            @switch($extension)
                                                @case('pdf') <i class="bi bi-file-earmark-pdf text-danger"></i> @break
                                                @case('jpg') @case('jpeg') @case('png') <i class="bi bi-image text-primary"></i> @break
                                                @case('doc') @case('docx') <i class="bi bi-file-earmark-word text-primary"></i> @break
                                                @case('xlsx') <i class="bi bi-file-earmark-excel text-success"></i> @break
                                                @default <i class="bi bi-paperclip"></i>
                                            @endswitch

                                            {{ $file->file_name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="d-flex gap-2 mt-4">
                        <a href="{{ route('outbase_requests.edit', $outbaseRequest->id) }}" class="btn btn-primary">
                            Edit
                        </a>

                        <form method="POST" action="{{ route('outbase_requests.destroy', $outbaseRequest->id) }}" onsubmit="return confirm('Are you sure you want to delete this outbase request?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                Delete
                            </button>
                        </form>
                        <a href="javascript:history.back()" class="btn btn-secondary">
                            Back
                        </a>
                    </div>

                    @if ($outbaseRequest->status === 'pending' && auth()->id() === ($outbaseRequest->employee->approver_id ?? null))
                        <!-- Approve Button -->
                        <form method="POST" action="{{ route('outbase_requests.approve', $outbaseRequest->id) }}" class="mt-4">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">
                                Approve
                            </button>
                        </form>

                        <!-- Reject Form -->
                        <form method="POST" action="{{ route('outbase_requests.reject', $outbaseRequest->id) }}" class="mt-3">
                            @csrf
                            @method('PATCH')

                            <div class="mb-2">
                                <label for="reason" class="form-label text-danger">Reason for Rejection</label>
                                <input type="text" name="reason" id="reason" class="form-control is-invalid" required value="{{ old('reason') }}">
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
</x-app-layout>
