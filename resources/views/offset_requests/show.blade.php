<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Offset Request Details') }}
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

                    {{-- Employee --}}
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" disabled class="form-control bg-light" 
                            value="{{ $offsetRequest->employee->first_name }} {{ $offsetRequest->employee->last_name }}">
                    </div>

                    {{-- Date --}}
                    <div class="mb-3">
                        <label class="form-label">Date to Offset</label>
                        <input type="text" disabled class="form-control bg-light"
                            value="{{ $offsetRequest->date }}">
                    </div>

                    {{-- Project or Event --}}
                    <div class="mb-3">
                        <label class="form-label">Project or Event</label>
                        <textarea disabled class="form-control bg-light" rows="3">{{ $offsetRequest->project_or_event_description }}</textarea>
                    </div>

                    {{-- Time Range --}}
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Time Start</label>
                            <input type="time" disabled class="form-control bg-light"
                                value="{{ $offsetRequest->time_start }}">
                        </div>
                        <div class="col">
                            <label class="form-label">Time End</label>
                            <input type="time" disabled class="form-control bg-light"
                                value="{{ $offsetRequest->time_end }}">
                        </div>
                    </div>

                    {{-- Number of Hours --}}
                    <div class="mb-3">
                        <label class="form-label">Number of Hours</label>
                        <input type="text" disabled class="form-control bg-light"
                            value="{{ $offsetRequest->number_of_hours }}">
                    </div>

                    {{-- Reason --}}
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea disabled class="form-control bg-light" rows="3">{{ $offsetRequest->reason }}</textarea>
                    </div>

                    @php $disabled = 'disabled class=form-control-plaintext'; @endphp

                    {{-- Created At --}}
                    <div class="mb-3">
                        <label class="form-label">Filed On</label>
                        <input type="text" class="form-control bg-light" disabled value="{{ $offsetRequest->created_at->format('F d, Y h:i A') }}">
                    </div>

                    {{-- Updated At --}}
                    <div class="mb-3">
                        <label class="form-label">Last Updated</label>
                        <input type="text" class="form-control bg-light" disabled value="{{ $offsetRequest->updated_at->format('F d, Y h:i A') }}">
                    </div>


                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <input type="text" disabled class="form-control bg-light"
                            value="{{ ucfirst($offsetRequest->status) }}">
                    </div>

                    {{-- Approval Metadata --}}
                    @if ($offsetRequest->status === 'approved' && $offsetRequest->approver)
                        <div class="mb-3">
                            <label class="form-label">Approved By</label>
                            <input type="text" disabled class="form-control bg-light"
                                value="{{ $offsetRequest->approver->name }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Approval Date</label>
                            <input type="text" disabled class="form-control bg-light"
                                value="{{ $offsetRequest->approval_date ?? '' }}">
                        </div>
                    @elseif ($offsetRequest->status === 'rejected')
                        <div class="mb-3">
                            <label class="form-label">Rejected By</label>
                            <input type="text" disabled class="form-control bg-light"
                                value="{{ optional($offsetRequest->approver)->name }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason</label>
                            <textarea disabled class="form-control bg-light" rows="3">{{ $offsetRequest->rejection_reason }}</textarea>
                        </div>
                    @endif

                    {{-- Linked Overtime --}}
                    <div class="mb-4">
                        <label class="form-label mb-2">Used Overtime</label>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Total OT</th>
                                        <th>Used for Offset</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($offsetRequest->overtimeRequests as $ot)
                                        <tr>
                                            <td>{{ $ot->date }}</td>
                                            <td>{{ $ot->time_start }}</td>
                                            <td>{{ $ot->time_end }}</td>
                                            <td>{{ $ot->number_of_hours }}</td>
                                            <td>{{ $ot->pivot->used_hours }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($offsetRequest->files->count())
                        <div class="mb-4">
                            <label class="form-label">Attached Files</label>
                            <ul class="list-unstyled">
                                @foreach ($offsetRequest->files as $file)
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

                    {{-- Actions --}}
                    <div class="d-flex gap-2 mb-4">
                        <a href="{{ route('offset_requests.edit', $offsetRequest->id) }}"
                            class="btn btn-outline-primary">Edit</a>

                        <form method="POST" action="{{ route('offset_requests.destroy', $offsetRequest->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this offset request?')"
                                class="btn btn-outline-danger">Delete</button>
                        </form>
                        <a href="javascript:history.back()" class="btn btn-secondary">
                            Back
                        </a>
                    </div>

                    {{-- Approver Actions --}}
                    @if ($offsetRequest->status === 'pending' && auth()->id() === ($offsetRequest->employee->approver_id ?? null))
                        <form method="POST" action="{{ route('offset_requests.approve', $offsetRequest->id) }}" class="mb-3">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">Approve</button>
                        </form>

                        <form method="POST" action="{{ route('offset_requests.reject', $offsetRequest->id) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-2">
                                <label for="reason" class="form-label text-danger">Reason for Rejection</label>
                                <input type="text" name="reason" id="reason" required class="form-control is-invalid" value="{{ old('reason') }}">
                                @error('reason')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-warning">Reject</button>
                        </form>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
