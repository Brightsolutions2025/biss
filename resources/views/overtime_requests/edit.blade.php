<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Overtime Request') }}</h2>
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

                <form method="POST" action="{{ route('overtime_requests.update', $overtimeRequest) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <!-- Employee (Disabled) -->
                    <div class="mb-3">
                        <label for="employee_display" class="form-label">Employee</label>
                        <select id="employee_display" class="form-select bg-light" disabled>
                            <option>
                                {{ $overtimeRequest->employee->last_name }}, {{ $overtimeRequest->employee->first_name }} ({{ $overtimeRequest->employee->employee_number }})
                            </option>
                        </select>
                        <input type="hidden" name="employee_id" value="{{ $overtimeRequest->employee_id }}">
                    </div>

                    <!-- Overtime Date -->
                    <div class="mb-3">
                        <label for="date" class="form-label">Overtime Date</label>
                        <input type="date" name="date" id="date" class="form-control"
                               value="{{ old('date', $overtimeRequest->date) }}" required>
                        @error('date')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Time Start -->
                    <div class="mb-3">
                        <label for="time_start" class="form-label">Time Start</label>
                        <input type="time" name="time_start" id="time_start" class="form-control"
                               value="{{ old('time_start', $overtimeRequest->time_start) }}" required>
                        @error('time_start')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Time End -->
                    <div class="mb-3">
                        <label for="time_end" class="form-label">Time End</label>
                        <input type="time" name="time_end" id="time_end" class="form-control"
                               value="{{ old('time_end', $overtimeRequest->time_end) }}" required>
                        @error('time_end')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Number of Hours -->
                    <div class="mb-3">
                        <label for="number_of_hours" class="form-label">Number of Hours</label>
                        <input type="number" name="number_of_hours" id="number_of_hours"
                               step="0.01" min="0" class="form-control"
                               value="{{ old('number_of_hours', $overtimeRequest->number_of_hours) }}" required>
                        @error('number_of_hours')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Reason -->
                    <div class="mb-4">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea name="reason" id="reason" rows="3" class="form-control" required>{{ old('reason', $overtimeRequest->reason) }}</textarea>
                        @error('reason')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Show Existing Files -->
                    @if ($overtimeRequest->files->count())
                        <div class="mb-3">
                            <label class="form-label">Attached Files</label>
                            <ul class="list-unstyled">
                                @foreach ($overtimeRequest->files as $file)
                                    <li class="mb-2">
                                        <a href="{{ route('files.download', $file->id) }}" target="_blank">{{ $file->file_name }}</a>
                                        <a href="#" class="btn btn-sm btn-outline-danger ms-2"
                                        onclick="event.preventDefault(); deleteFile({{ $file->id }})">
                                            Delete
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Add More Files -->
                    <div class="mb-3">
                        <label class="form-label">Add More Files (Max: 5)</label>
                        <input type="file" name="files[]" multiple class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx">
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            Update Request
                        </button>

                        <a href="{{ route('overtime_requests.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        function deleteFile(fileId) {
            if (!confirm('Delete this file?')) return;

            fetch(`/files/${fileId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }).then(res => {
                if (res.ok) {
                    location.reload();
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
