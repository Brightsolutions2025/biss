<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Add a New Leave Balance') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <div class="alert alert-info">
                    VL/EL paid credits apply only to regular employees. Standard annual credits are 12 days for VL and 3 days for EL.
                </div>

                <form method="POST" action="{{ route('leave_balances.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Regular Employee</label>
                        <select name="employee_id" id="employee_id" class="form-select" required>
                            <option value="">-- Select Employee --</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" {{ (int) old('employee_id') === $employee->id ? 'selected' : '' }}>
                                    {{ $employee->last_name }}, {{ $employee->first_name }} ({{ $employee->employee_number }})
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="leave_type" class="form-label">Leave Type</label>
                        <select name="leave_type" id="leave_type" class="form-select" required>
                            <option value="">-- Select Leave Type --</option>
                            @foreach ($leaveTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('leave_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('leave_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <input id="year" name="year" type="number" min="2000" max="2100" class="form-control"
                            value="{{ old('year', date('Y')) }}" required>
                        @error('year')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="beginning_balance" class="form-label">Beginning Balance</label>
                        <input id="beginning_balance" name="beginning_balance" type="number" min="0" step="0.5" class="form-control"
                            value="{{ old('beginning_balance') }}" required>
                        <div class="form-text">Defaults to 12 for VL and 3 for EL. HR may adjust the beginning balance if company policy requires a correction.</div>
                        @error('beginning_balance')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Add') }}</button>
                        <a href="javascript:history.back()" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const leaveType = document.getElementById('leave_type');
            const balance = document.getElementById('beginning_balance');
            const defaults = @json($defaultCredits);
            const hadOldBalance = @json(old('beginning_balance') !== null);

            function applyDefault() {
                if (!hadOldBalance && leaveType.value && defaults[leaveType.value] !== undefined) {
                    balance.value = defaults[leaveType.value];
                }
            }

            leaveType.addEventListener('change', function () {
                if (defaults[this.value] !== undefined) {
                    balance.value = defaults[this.value];
                }
            });
            applyDefault();
        });
    </script>
</x-app-layout>
