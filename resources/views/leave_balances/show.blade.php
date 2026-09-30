<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Leave Balance Details') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

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

                {{-- Company Details --}}
                <h5 class="mb-3">Company</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" disabled value="{{ $leaveBalance->company->name }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Industry</label>
                        <input type="text" class="form-control" disabled value="{{ $leaveBalance->company->industry }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" disabled value="{{ $leaveBalance->company->address }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" disabled value="{{ $leaveBalance->company->phone }}">
                    </div>
                </div>

                {{-- Employee Details --}}
                <h5 class="mb-3">Employee</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" disabled value="{{ $leaveBalance->employee->last_name }}, {{ $leaveBalance->employee->first_name }} {{ $leaveBalance->employee->middle_name }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Employee Number</label>
                        <input type="text" class="form-control" disabled value="{{ $leaveBalance->employee->employee_number }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Position</label>
                        <input type="text" class="form-control" disabled value="{{ $leaveBalance->employee->position }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" disabled value="{{ optional($leaveBalance->employee->department)->name }}">
                    </div>
                </div>

                {{-- Leave Balance Details --}}
                <h5 class="mb-3">Leave Balance</h5>
                <div class="row g-3 mb-5">
                    <div class="col-md-6">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" disabled value="{{ $leaveBalance->year }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Beginning Balance</label>
                        <input type="number" class="form-control" disabled value="{{ $leaveBalance->beginning_balance }}">
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-3">
                    <a href="{{ route('leave_balances.edit', $leaveBalance->id) }}" class="btn btn-primary">
                        Edit
                    </a>

                    <form method="POST" action="{{ route('leave_balances.destroy', $leaveBalance->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Are you sure you want to delete this leave balance?')" class="btn btn-danger">
                            Delete
                        </button>
                    </form>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        Back
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
