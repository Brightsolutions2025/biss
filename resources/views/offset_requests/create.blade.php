<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Add New Offset Request') }}
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

                    <form method="POST" action="{{ route('offset_requests.store') }}" onsubmit="prepareOvertimeData()" enctype="multipart/form-data">
                        @csrf

                        <!-- Employee -->
                        <div class="mb-3">
                            <label for="employee_name" class="form-label">Employee</label>
                            <input type="text" id="employee_name" class="form-control" value="{{ $employee->first_name }} {{ $employee->last_name }}" disabled>
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        </div>

                        <!-- Date -->
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" id="date" name="date" value="{{ old('date') }}" class="form-control" required>
                            @error('date') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <!-- Project Description -->
                        <div class="mb-3">
                            <label for="project_or_event_description" class="form-label">Project or Event Description</label>
                            <textarea id="project_or_event_description" name="project_or_event_description" rows="3" class="form-control" required>{{ old('project_or_event_description') }}</textarea>
                            @error('project_or_event_description') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <!-- Time Start -->
                        <div class="mb-3">
                            <label for="time_start" class="form-label">Time Start</label>
                            <input type="time" id="time_start" name="time_start" value="{{ old('time_start') }}" class="form-control" required>
                            @error('time_start') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <!-- Time End -->
                        <div class="mb-3">
                            <label for="time_end" class="form-label">Time End</label>
                            <input type="time" id="time_end" name="time_end" value="{{ old('time_end') }}" class="form-control" required>
                            @error('time_end') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <!-- Number of Hours -->
                        <div class="mb-3">
                            <label for="number_of_hours" class="form-label">Number of Hours</label>
                            <input type="number" step="0.01" id="number_of_hours" name="number_of_hours" value="{{ old('number_of_hours') }}" class="form-control" required>
                            @error('number_of_hours') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea id="reason" name="reason" rows="3" class="form-control">{{ old('reason') }}</textarea>
                            @error('reason') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <!-- Approved & Upcoming Overtime -->
                        <div class="mb-4">
                            <label class="form-label">Approved and Upcoming Overtime Requests</label>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>Total OT</th>
                                            <th>Remaining</th>
                                            <th>Hours to Offset</th>
                                        </tr>
                                    </thead>
                                    <tbody id="overtime-table-body">
                                        @foreach ($overtimeRequests as $ot)
                                            <tr>
                                                <td>{{ $ot->date }}</td>
                                                <td>{{ $ot->time_start }}</td>
                                                <td>{{ $ot->time_end }}</td>
                                                <td>{{ $ot->number_of_hours }}</td>
                                                <td id="remaining-{{ $ot->id }}">
                                                    {{ number_format($ot->number_of_hours - $ot->used_hours, 2) }}
                                                </td>
                                                <td>
                                                    <input type="number"
                                                        step="0.01"
                                                        min="0.5"
                                                        class="form-control form-control-sm hours-to-offset"
                                                        data-ot-id="{{ $ot->id }}"
                                                        data-original-hours="{{ $ot->number_of_hours - $ot->used_hours }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <input type="hidden" name="overtime_requests" id="overtime_requests">
                        </div>

                        <!-- Attachments -->
                        <div class="mb-4">
                            <label for="files" class="form-label">Supporting Documents (optional)</label>
                            <input
                                id="files"
                                name="files[]"
                                type="file"
                                class="form-control"
                                multiple
                            >
                            @error('files.*')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Submit') }}
                            </button>

                            <a href="javascript:history.back()" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputs = document.querySelectorAll('.hours-to-offset');

            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    const id = input.dataset.otId;
                    const max = parseFloat(input.dataset.originalHours);
                    const value = parseFloat(input.value) || 0;
                    const remaining = Math.max(0, max - value).toFixed(2);
                    const targetCell = document.getElementById(`remaining-${id}`);
                    if (targetCell) {
                        targetCell.textContent = remaining;
                    }
                });
            });
        });

        function prepareOvertimeData() {
            const inputs = document.querySelectorAll('.hours-to-offset');
            const selected = [];

            inputs.forEach(input => {
                const value = parseFloat(input.value);
                if (!isNaN(value) && value >= 0.5) {
                    selected.push({
                        id: parseInt(input.dataset.otId),
                        used_hours: value
                    });
                }
            });

            document.getElementById('overtime_requests').value = JSON.stringify(selected);
        }
    </script>
</x-app-layout>
