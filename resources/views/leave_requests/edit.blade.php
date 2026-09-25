<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Leave Request') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('leave_requests.update', $leaveRequest->id) }}" enctype="multipart/form-data"
                    onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerText='Submitting...';">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control bg-light"
                            value="{{ $leaveRequest->employee->first_name }} {{ $leaveRequest->employee->last_name }} ({{ $leaveRequest->employee->employee_number }})" disabled>
                        <input type="hidden" name="employee_id" value="{{ $leaveRequest->employee_id }}">
                    </div>

                    <div class="mb-3">
                        <label for="leave_type" class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select id="leave_type" name="leave_type" class="form-select" required>
                            @foreach ($leaveTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('leave_type', $leaveRequest->leave_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('leave_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    @if ($leaveRequest->employee->isRegular())
                        <div class="alert alert-light border mb-4">
                            @foreach ($creditSummary as $summary)
                                <div class="d-flex justify-content-between gap-3">
                                    <span>{{ $summary['label'] }}</span>
                                    <strong>{{ number_format($summary['remaining'], 2) }} / {{ number_format($summary['beginning'], 2) }} remaining</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="alert alert-info mb-4">
                        <strong>Note:</strong> If you're planning to take leave for more than one week, submit a separate request for each week.
                    </div>

                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input id="start_date" name="start_date" type="date" class="form-control"
                            value="{{ old('start_date', $leaveRequest->start_date) }}" required>
                        @error('start_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input id="end_date" name="end_date" type="date" class="form-control"
                            value="{{ old('end_date', $leaveRequest->end_date) }}" required>
                        @error('end_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="number_of_days" class="form-label">Number of Days</label>
                        <input id="number_of_days" name="number_of_days" type="number" step="0.5" min="0" class="form-control"
                            value="{{ old('number_of_days', $leaveRequest->number_of_days) }}" required>
                        @error('number_of_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea id="reason" name="reason" rows="4" class="form-control" required>{{ old('reason', $leaveRequest->reason) }}</textarea>
                        @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4 form-check">
                        <input type="hidden" name="leave_with_pay" value="0">
                        <input type="checkbox" id="leave_with_pay" name="leave_with_pay" value="1" class="form-check-input"
                            {{ old('leave_with_pay', $leaveRequest->leave_with_pay) ? 'checked' : '' }}
                            {{ !$leaveRequest->employee->isRegular() ? 'disabled' : '' }}>
                        <label for="leave_with_pay" class="form-check-label">Leave with Pay</label>
                        @error('leave_with_pay')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    @if ($leaveRequest->files->count())
                        <div class="mb-3">
                            <label class="form-label">Attached Supporting Documents</label>
                            <ul class="list-unstyled">
                                @foreach ($leaveRequest->files as $file)
                                    <li class="mb-2" id="file-row-{{ $file->id }}">
                                        <a href="{{ route('files.download', $file->id) }}" target="_blank">{{ $file->file_name }}</a>
                                        <a href="#" class="btn btn-sm btn-outline-danger ms-2"
                                            onclick="event.preventDefault(); deleteFile({{ $file->id }})">Delete</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label" id="supporting_documents_label">Add Supporting Documents (Max: 5)</label>
                        <input id="files" type="file" name="files[]" multiple class="form-control"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx">
                        <div id="supporting_documents_help" class="form-text"></div>
                        @error('files')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('files.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Leave Request</button>
                        <a href="{{ route('leave_requests.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const existingAttachmentCount = {{ $leaveRequest->files->count() }};

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
            .then(async res => {
                const body = await res.json().catch(() => ({}));
                if (res.ok) {
                    location.reload();
                    return;
                }
                alert(body.error || 'Could not delete this supporting document.');
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const daysInput = document.getElementById('number_of_days');
            const leaveTypeInput = document.getElementById('leave_type');
            const filesInput = document.getElementById('files');
            const filesLabel = document.getElementById('supporting_documents_label');
            const filesHelp = document.getElementById('supporting_documents_help');

            function calculateDays() {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                if (!isNaN(startDate) && !isNaN(endDate) && endDate >= startDate) {
                    daysInput.value = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
                } else {
                    daysInput.value = '';
                }
            }

            function updateAttachmentRequirement() {
                const emergency = leaveTypeInput.value === 'emergency';
                const needsNewAttachment = emergency && existingAttachmentCount < 1;
                filesInput.required = needsNewAttachment;
                filesLabel.innerHTML = emergency
                    ? 'Supporting Documents <span class="text-danger">*</span>'
                    : 'Add Supporting Documents (optional, Max: 5)';
                filesHelp.textContent = emergency
                    ? (existingAttachmentCount > 0
                        ? 'Emergency Leave requires at least one supporting document. An existing attachment already satisfies this requirement.'
                        : 'Required for Emergency Leave. Upload at least one supporting document before updating.')
                    : 'Supporting documents are optional for Vacation Leave.';
            }

            startDateInput.addEventListener('change', calculateDays);
            endDateInput.addEventListener('change', calculateDays);
            leaveTypeInput.addEventListener('change', updateAttachmentRequirement);
            updateAttachmentRequirement();
        });
    </script>
    @endpush
</x-app-layout>
