<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Edit Outbase Request') }}
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

                    <form method="POST" action="{{ route('outbase_requests.update', $outbaseRequest) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <!-- Employee Name (readonly) -->
                        <div class="mb-3">
                            <label for="employee_name" class="form-label">Employee</label>
                            <input
                                type="text"
                                class="form-control"
                                id="employee_name"
                                value="{{ $outbaseRequest->employee->first_name . ' ' . $outbaseRequest->employee->last_name }}"
                                disabled
                            >
                            <input type="hidden" name="employee_id" value="{{ $outbaseRequest->employee_id }}">
                        </div>

                        <!-- Date -->
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input
                                type="date"
                                name="date"
                                id="date"
                                class="form-control"
                                value="{{ old('date', $outbaseRequest->date) }}"
                                required
                            >
                            @error('date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Time Start -->
                        <div class="mb-3">
                            <label for="time_start" class="form-label">Time Start</label>
                            <input
                                type="time"
                                name="time_start"
                                id="time_start"
                                class="form-control"
                                value="{{ old('time_start', $outbaseRequest->time_start) }}"
                                required
                            >
                            @error('time_start')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Time End -->
                        <div class="mb-3">
                            <label for="time_end" class="form-label">Time End</label>
                            <input
                                type="time"
                                name="time_end"
                                id="time_end"
                                class="form-control"
                                value="{{ old('time_end', $outbaseRequest->time_end) }}"
                                required
                            >
                            @error('time_end')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input
                                type="text"
                                name="location"
                                id="location"
                                class="form-control"
                                value="{{ old('location', $outbaseRequest->location) }}"
                                required
                            >
                            @error('location')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea
                                name="reason"
                                id="reason"
                                rows="3"
                                class="form-control"
                                required
                            >{{ old('reason', $outbaseRequest->reason) }}</textarea>
                            @error('reason')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($outbaseRequest->files->count())
                            <div class="mb-3">
                                <label class="form-label">Attached Files</label>
                                <ul class="list-unstyled">
                                    @foreach ($outbaseRequest->files as $file)
                                        <li class="mb-2">
                                            <a href="{{ route('files.download', $file->id) }}" target="_blank">
                                                {{ $file->file_name }}
                                            </a>
                                            <a href="#"
                                            class="btn btn-sm btn-outline-danger ms-2"
                                            onclick="event.preventDefault(); deleteFile({{ $file->id }})">
                                                Delete
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Add More Files (Max: 5)</label>
                            <input type="file" name="files[]" multiple class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx">
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary">
                                Update Request
                            </button>
                            <a href="{{ route('outbase_requests.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>

                    </form>
                </div>
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
            })
            .then(res => {
                if (res.ok) {
                    location.reload();
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
