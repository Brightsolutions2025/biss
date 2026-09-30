<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Edit Leave Balance') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
                @if ($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <form method="POST" action="{{ route('leave_balances.update', $leaveBalance->id) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control" disabled
                            value="{{ $leaveBalance->employee->last_name }}, {{ $leaveBalance->employee->first_name }} ({{ $leaveBalance->employee->employee_number }})">
                        <input type="hidden" name="employee_id" value="{{ $leaveBalance->employee_id }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Leave Type</label>
                        <input type="text" class="form-control" disabled value="{{ $leaveBalance->leave_type_label }}">
                        <input type="hidden" name="leave_type" value="{{ $leaveBalance->leave_type }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" value="{{ $leaveBalance->year }}" disabled>
                        <input type="hidden" name="year" value="{{ $leaveBalance->year }}">
                    </div>

                    <div class="mb-3">
                        <label for="beginning_balance" class="form-label">Beginning Balance</label>
                        <input id="beginning_balance" name="beginning_balance" type="number" min="0" step="0.5" class="form-control"
                            value="{{ old('beginning_balance', $leaveBalance->beginning_balance) }}" required>
                        @error('beginning_balance')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="{{ route('leave_balances.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
