<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Add a New Leave Balance') }}
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

                <form method="POST" action="{{ route('leave_balances.store') }}">
                    @csrf

                    {{-- Employee Selection --}}
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Employee</label>
                        <select name="employee_id" id="employee_id" class="form-select" required>
                            <option value="">-- Select Employee --</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" {{ (int) old('employee_id') === $employee->id ? 'selected' : '' }}>
                                    {{ $employee->last_name }}, {{ $employee->first_name }} ({{ $employee->employee_number }})
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Year --}}
                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <input
                            id="year"
                            name="year"
                            type="number"
                            min="2000"
                            max="2100"
                            class="form-control"
                            value="{{ old('year', date('Y')) }}"
                            required
                        >
                        @error('year')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Beginning Balance --}}
                    <div class="mb-3">
                        <label for="beginning_balance" class="form-label">Beginning Balance</label>
                        <input
                            id="beginning_balance"
                            name="beginning_balance"
                            type="number"
                            min="0"
                            class="form-control"
                            value="{{ old('beginning_balance', 0) }}"
                            required
                        >
                        @error('beginning_balance')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Add') }}
                        </button>

                        <a href="javascript:history.back()" class="btn btn-secondary">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
