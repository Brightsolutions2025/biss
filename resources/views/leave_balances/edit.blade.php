<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Edit Leave Balance') }}
        </h2>
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

                <form method="POST" action="{{ route('leave_balances.update', $leaveBalance->id) }}">
                    @csrf
                    @method('PATCH')

                    {{-- Employee --}}
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Employee</label>
                        <select id="employee_id" name="employee_id" class="form-select" disabled>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id', $leaveBalance->employee_id) == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->last_name }}, {{ $employee->first_name }} ({{ $employee->employee_number }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="employee_id" value="{{ $leaveBalance->employee_id }}">
                        @error('employee_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Year --}}
                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <input id="year" name="year" type="number" class="form-control" value="{{ old('year', $leaveBalance->year) }}" disabled>
                        <input type="hidden" name="year" value="{{ $leaveBalance->year }}">
                        @error('year')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Beginning Balance --}}
                    <div class="mb-3">
                        <label for="beginning_balance" class="form-label">Beginning Balance</label>
                        <input id="beginning_balance" name="beginning_balance" type="number" class="form-control" value="{{ old('beginning_balance', $leaveBalance->beginning_balance) }}">
                        @error('beginning_balance')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit & Cancel --}}
                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary">
                            Update
                        </button>
                        <a href="{{ route('leave_balances.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
