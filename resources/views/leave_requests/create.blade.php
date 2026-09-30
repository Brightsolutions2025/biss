<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Add a New Leave Request') }}
        </h2>
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
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('leave_requests.store') }}" enctype="multipart/form-data"
                    onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerText='Submitting...';">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control bg-light"
                            value="{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_number }})" disabled>
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                    </div>

                    @if ($employee->isRegular())
                        <div class="alert alert-light border mb-4">
                            <div class="fw-semibold mb-2">{{ now()->year }} Paid Leave Credits</div>
                            <div class="row g-2">
                                @foreach ($creditSummary as $summary)
                                    <div class="col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <div class="small text-muted">{{ $summary['label'] }}</div>
                                            <div class="fw-bold">
                                                {{ number_format($summary['remaining'], 2) }} remaining
                                                <span class="text-muted fw-normal">/ {{ number_format($summary['beginning'], 2) }}</span>
                                            </div>
                                            @if (!$summary['configured'])
                                                <div class="small text-danger">No balance configured for this year.</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="small text-muted mt-2">Policy: VL = 12 days/year, EL = 3 days/year for regular employees.</div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-4">
                            Paid VL/EL credits apply only to regular employees. You may still file an unpaid leave request.
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="leave_type" class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select id="leave_type" name="leave_type" class="form-select" required>
                            <option value="">-- Select Leave Type --</option>
                            @foreach ($leaveTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('leave_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('leave_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info mb-4">
                        <strong>Note:</strong> If you're planning to take leave for more than one week,
                        submit a separate request for each week.
                    </div>

                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input id="start_date" name="start_date" type="date" class="form-control" value="{{ old('start_date') }}" required>
                        @error('start_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input id="end_date" name="end_date" type="date" class="form-control" value="{{ old('end_date') }}" required>
                        @error('end_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="number_of_days" class="form-label">Number of Days</label>
                        <input id="number_of_days" name="number_of_days" type="number" step="0.5" min="0" class="form-control"
                            value="{{ old('number_of_days') }}" required>
                        @error('number_of_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea id="reason" name="reason" rows="4" class="form-control" required>{{ old('reason') }}</textarea>
                        @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4 form-check">
                        <input type="hidden" name="leave_with_pay" value="0">
                        <input type="checkbox" id="leave_with_pay" name="leave_with_pay" value="1" class="form-check-input"
                            {{ old('leave_with_pay') ? 'checked' : '' }} {{ !$employee->isRegular() ? 'disabled' : '' }}>
                        <label for="leave_with_pay" class="form-check-label">Leave with Pay</label>
                        @if (!$employee->isRegular())
                            <div class="form-text">Paid VL/EL credits are unavailable because your employment type is not Regular.</div>
                        @endif
                        @error('leave_with_pay')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="files" class="form-label" id="supporting_documents_label">Supporting Documents (optional)</label>
                        <input id="files" name="files[]" type="file" class="form-control" multiple
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx">
                        <div id="supporting_documents_help" class="form-text">
                            Up to 5 files, maximum 5 MB each. PDF, JPG, PNG, DOC/DOCX, and XLSX are accepted.
                        </div>
                        @error('files')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('files.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                        <a href="javascript:history.back()" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
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
                const diffTime = endDate - startDate;
                daysInput.value = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;
            } else {
                daysInput.value = '';
            }
        }

        function updateAttachmentRequirement() {
            const emergency = leaveTypeInput.value === 'emergency';
            filesInput.required = emergency;
            filesLabel.innerHTML = emergency
                ? 'Supporting Documents <span class="text-danger">*</span>'
                : 'Supporting Documents (optional)';
            filesHelp.textContent = emergency
                ? 'Required for Emergency Leave (EL). Upload at least one file. Up to 5 files, maximum 5 MB each.'
                : 'Up to 5 files, maximum 5 MB each. PDF, JPG, PNG, DOC/DOCX, and XLSX are accepted.';
        }

        startDateInput.addEventListener('change', calculateDays);
        endDateInput.addEventListener('change', calculateDays);
        leaveTypeInput.addEventListener('change', updateAttachmentRequirement);
        updateAttachmentRequirement();
    });
    </script>
</x-app-layout>
