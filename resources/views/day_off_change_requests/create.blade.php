{{-- resources/views/day_off_change_requests/create.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Create Day Off Change Request') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">

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

                <div class="card shadow-sm border border-2">
                    <div class="card-body">
                        <form method="POST" action="{{ route('day_off_change_requests.store') }}">
                            @csrf

                            <!-- Reason Type -->
                            <div class="mb-3">
                                <label class="form-label">Reason Type</label>
                                <select name="reason_type" class="form-select" required>
                                    <option value="">-- Select Reason --</option>
                                    <option value="Offset due to Extension of Work"
                                        {{ old('reason_type') === 'Offset due to Extension of Work' ? 'selected' : '' }}>
                                        Offset due to Extension of Work
                                    </option>
                                    <option value="Schedule / Day Off Adjustment"
                                        {{ old('reason_type') === 'Schedule / Day Off Adjustment' ? 'selected' : '' }}>
                                        Schedule / Day Off Adjustment
                                    </option>
                                </select>
                            </div>

                            <!-- Extension Date -->
                            <div class="mb-3">
                                <label class="form-label">Extension Date</label>
                                <input
                                    type="date"
                                    name="extension_date"
                                    class="form-control"
                                    value="{{ old('extension_date') }}"
                                    required
                                >
                            </div>

                            <!-- Time Range -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Time Start</label>
                                    <input
                                        type="time"
                                        name="time_start"
                                        class="form-control"
                                        value="{{ old('time_start') }}"
                                        required
                                    >
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Time End</label>
                                    <input
                                        type="time"
                                        name="time_end"
                                        class="form-control"
                                        value="{{ old('time_end') }}"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Number of Hours -->
                            <div class="mb-3">
                                <label class="form-label">Number of Hours</label>
                                <input
                                    type="number"
                                    name="number_of_hours"
                                    step="0.01"
                                    class="form-control"
                                    value="{{ old('number_of_hours') }}"
                                    placeholder="e.g. 2.50"
                                    required
                                >
                            </div>

                            <!-- Day Off Dates -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Current Day Off</label>
                                    <input
                                        type="date"
                                        name="old_date"
                                        class="form-control"
                                        value="{{ old('old_date') }}"
                                        required
                                    >
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New / Offset Day Off</label>
                                    <input
                                        type="date"
                                        name="new_date"
                                        class="form-control"
                                        value="{{ old('new_date') }}"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Reason -->
                            <div class="mb-3">
                                <label class="form-label">Detailed Reason</label>
                                <textarea
                                    name="reason"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Explain the reason for the day off change"
                                    required
                                >{{ old('reason') }}</textarea>
                            </div>

                            <!-- Submit -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Submit Request') }}
                                </button>
                                <a href="{{ route('day_off_change_requests.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
