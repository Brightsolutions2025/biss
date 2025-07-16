<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Employee Details') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">

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

                <!-- Identification -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">Identification</h5>
                    <dl class="row">
                        <dt class="col-sm-3">User</dt>
                        <dd class="col-sm-9">{{ $employee->user->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Employee Number</dt>
                        <dd class="col-sm-9">{{ $employee->employee_number }}</dd>

                        <dt class="col-sm-3">Approver</dt>
                        <dd class="col-sm-9">{{ $employee->approver?->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Flexible Time</dt>
                        <dd class="col-sm-9">{{ $employee->flexible_time ? 'Yes' : 'No' }}</dd>

                        <dt class="col-sm-3">OT Not Convertible to Offset</dt>
                        <dd class="col-sm-9">{{ $employee->ot_not_convertible_to_offset ? 'Yes' : 'No' }}</dd>
                    </dl>
                </div>

                <!-- Personal Information -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">Personal Information</h5>
                    <dl class="row">
                        <dt class="col-sm-3">Full Name</dt>
                        <dd class="col-sm-9">
                            {{ $employee->first_name }} 
                            {{ $employee->middle_name ? $employee->middle_name . ' ' : '' }}{{ $employee->last_name }}
                        </dd>

                        <dt class="col-sm-3">Birth Date</dt>
                        <dd class="col-sm-9">{{ $employee->birth_date }}</dd>

                        <dt class="col-sm-3">Gender</dt>
                        <dd class="col-sm-9">{{ $employee->gender }}</dd>

                        <dt class="col-sm-3">Civil Status</dt>
                        <dd class="col-sm-9">{{ $employee->civil_status }}</dd>

                        <dt class="col-sm-3">Nationality</dt>
                        <dd class="col-sm-9">{{ $employee->nationality }}</dd>
                    </dl>
                </div>

                <!-- Employment Details -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">Employment Details</h5>
                    <dl class="row">
                        <dt class="col-sm-3">Position</dt>
                        <dd class="col-sm-9">{{ $employee->position }}</dd>

                        <dt class="col-sm-3">Department</dt>
                        <dd class="col-sm-9">{{ $employee->department?->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Team</dt>
                        <dd class="col-sm-9">{{ $employee->team?->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Employment Type</dt>
                        <dd class="col-sm-9">{{ $employee->employment_type }}</dd>

                        <dt class="col-sm-3">Hire Date</dt>
                        <dd class="col-sm-9">{{ $employee->hire_date }}</dd>

                        <dt class="col-sm-3">Termination Date</dt>
                        <dd class="col-sm-9">{{ $employee->termination_date }}</dd>

                        <dt class="col-sm-3">Basic Salary</dt>
                        <dd class="col-sm-9">₱{{ number_format($employee->basic_salary, 2) }}</dd>
                    </dl>
                </div>

                <!-- Government IDs -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">Government IDs</h5>
                    <dl class="row">
                        <dt class="col-sm-3">SSS Number</dt>
                        <dd class="col-sm-9">{{ $employee->sss_number }}</dd>

                        <dt class="col-sm-3">PhilHealth Number</dt>
                        <dd class="col-sm-9">{{ $employee->philhealth_number }}</dd>

                        <dt class="col-sm-3">Pag-IBIG Number</dt>
                        <dd class="col-sm-9">{{ $employee->pagibig_number }}</dd>

                        <dt class="col-sm-3">TIN Number</dt>
                        <dd class="col-sm-9">{{ $employee->tin_number }}</dd>
                    </dl>
                </div>

                <!-- Contact Information -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">Contact Information</h5>
                    <dl class="row">
                        <dt class="col-sm-3">Contact Number</dt>
                        <dd class="col-sm-9">{{ $employee->contact_number }}</dd>

                        <dt class="col-sm-3">Emergency Contact</dt>
                        <dd class="col-sm-9">{{ $employee->emergency_contact }}</dd>

                        <dt class="col-sm-3">Address</dt>
                        <dd class="col-sm-9">{{ $employee->address }}</dd>
                    </dl>
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">Notes</h5>
                    <p class="mb-0">{{ $employee->notes }}</p>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-primary">Edit</a>

                    <form method="POST" action="{{ route('employees.destroy', $employee->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to delete this employee?')">
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
