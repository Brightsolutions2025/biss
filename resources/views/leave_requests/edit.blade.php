<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Edit Leave Request') }}
        </h2>
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

                <form method="POST" action="{{ route('leave_requests.update', $leaveRequest->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Employee -->
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input
                            type="text"
                            class="form-control bg-light"
                            value="{{ $leaveRequest->employee->first_name }} {{ $leaveRequest->employee->last_name }} ({{ $leaveRequest->employee->employee_number }})"
                            disabled
                        >
                        <input type="hidden" name="employee_id" value="{{ $leaveRequest->employee_id }}">
                    </div>

                    <!-- Start Date -->
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input
                            id="start_date"
                            name="start_date"
                            type="date"
                            class="form-control"
                            value="{{ old('start_date', $leaveRequest->start_date) }}"
                            required
                        >
                        @error('start_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- End Date -->
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input
                            id="end_date"
                            name="end_date"
                            type="date"
                            class="form-control"
                            value="{{ old('end_date', $leaveRequest->end_date) }}"
                            required
                        >
                        @error('end_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Number of Days -->
                    <div class="mb-3">
                        <label for="number_of_days" class="form-label">Number of Days</label>
                        <input
                            id="number_of_days"
                            name="number_of_days"
                            type="number"
                            step="0.5"
                            min="0"
                            class="form-control"
                            value="{{ old('number_of_days', $leaveRequest->number_of_days) }}"
                            required
                        >
                        @error('number_of_days')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Reason -->
                    <div class="mb-4">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea
                            id="reason"
                            name="reason"
                            rows="4"
                            class="form-control"
                            required
                        >{{ old('reason', $leaveRequest->reason) }}</textarea>
                        @error('reason')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    @if ($leaveRequest->files->count())
                        <div class="mb-3">
                            <label class="form-label">Attached Files</label>
                            <ul class="list-unstyled">
                                @foreach ($leaveRequest->files as $file)
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

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            Update Leave Request
                        </button>

                        <a href="{{ route('leave_requests.index') }}" class="btn btn-secondary">
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
            })
            .then(res => {
                if (res.ok) {
                    location.reload(); // refresh the page to reflect deletion
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
