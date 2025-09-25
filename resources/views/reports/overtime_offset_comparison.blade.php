<x-app-layout>
    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            display: flex;
            align-items: center;
        }

        .select2-container--bootstrap-5 .select2-selection__rendered {
            line-height: 1.5;
            padding-left: 0;
        }
    </style>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">Overtime vs Offset Report</h2>
    </x-slot>

    <div class="container py-4">
        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <form method="GET" class="row g-2 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">-- All Departments --</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select">
                            <option value="">-- All Employees --</option>
                            @foreach ($employeeOptions as $emp)
                                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->user->name ?? 'Unnamed' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">As of Date</label>
                        <input type="date" name="as_of" class="form-control" value="{{ request('as_of', now()->toDateString()) }}">
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>

                <p class="fw-semibold mb-3">
                    Report as of: <span class="text-primary">{{ \Carbon\Carbon::parse(request('as_of', now()))->format('F j, Y') }}</span>
                </p>

                <table class="table table-bordered table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th class="text-end">Total OT</th>
                            <th class="text-end text-danger">Expired OT</th>
                            <th class="text-end text-primary">Valid OT</th>
                            <th class="text-end text-warning">Offset Used</th>
                            <th class="text-end text-success">Remaining Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $row)
                            <tr>
                                <td>{{ $row['employee_name'] }}</td>
                                <td>{{ $row['department'] }}</td>
                                <td class="text-end">{{ number_format($row['overtime_hours'], 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($row['expired_hours'], 2) }}</td>
                                <td class="text-end text-primary">{{ number_format($row['valid_overtime_hours'], 2) }}</td>
                                <td class="text-end text-warning">{{ number_format($row['offset_hours'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <p class="small text-muted mt-2">
                    <i>Note: Remaining Balance = Valid Overtime – Offset Used. Expired OT is not usable.</i>
                </p>
                <!-- Download Buttons -->
                <div class="d-flex gap-2 mb-3">
                    <a id="pdfLink"
                        href="{{ route('reports.overtime_offset_comparison.pdf', request()->only('department_id', 'employee_id', 'as_of')) }}"
                        class="btn btn-outline-primary">
                        Download PDF
                    </a>

                    <a id="excelLink"
                        href="{{ route('reports.overtime_offset_comparison.excel', request()->only('department_id', 'employee_id', 'as_of')) }}"
                        class="btn btn-outline-success">
                        Export Excel
                    </a>
                </div>
                {{-- Detail Table (Only if single employee is selected) --}}
                @php
                    $selectedId = request('employee_id');
                    $employeeModel = $selectedId ? \App\Models\Employee::with(['user', 'department', 'overtimeRequests.offsetRequests'])->find($selectedId) : null;
                @endphp

                @if ($employeeModel)
                    <h5 class="fw-bold mt-4">Detailed Overtime & Offset Requests: {{ $employeeModel->user->name }}</h5>

                    <table class="table table-bordered table-sm table-hover align-middle mt-2">
                        <thead class="table-light">
                            <tr>
                                <th>OT Date</th>
                                <th class="text-end">OT Hours</th>
                                <th class="text-end text-danger">Expired?</th>
                                <th>Offset Date</th>
                                <th class="text-end">Used Hours</th>
                                <th class="text-end text-success">Remaining Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($overtimeRequests as $overtime)
                                @php
                                    // ✅ FIX: use pivot->used_hours instead of offset->number_of_hours
                                    $offsets = $overtime->offsetRequests;
                                    $offsetTotal = $offsets->sum(fn($o) => $o->pivot->used_hours);

                                    // ✅ FIX: balance is total OT hours minus actual used hours (not requested offset hours)
                                    $isExpired = $overtime->offset_expiration_date < now();
                                    $balance = $isExpired ? 0 : ($overtime->number_of_hours - $offsetTotal);
                                @endphp

                                @if ($offsets->isEmpty())
                                    <tr>
                                        <td>{{ $overtime->date->format('Y-m-d') }}</td>
                                        <td class="text-end">{{ number_format($overtime->number_of_hours, 2) }}</td>
                                        <td>{{ ucfirst($overtime->status) }}</td>
                                        <td>-</td>
                                        <td class="text-end">-</td>
                                        <td class="text-end">{{ number_format($balance, 2) }}</td>
                                    </tr>
                                @else
                                    @php $firstOffset = $offsets->first(); @endphp
                                    <tr>
                                        <td>{{ $overtime->date->format('Y-m-d') }}</td>
                                        <td class="text-end">{{ number_format($overtime->number_of_hours, 2) }}</td>
                                        <td>{{ ucfirst($overtime->status) }}</td>
                                        <td>{{ $firstOffset->date->format('Y-m-d') }}</td>
                                        
                                        {{-- ✅ FIX: show actual used hours from pivot instead of offset->number_of_hours --}}
                                        <td class="text-end">{{ number_format($firstOffset->pivot->used_hours, 2) }}</td>
                                        
                                        <td class="text-end">{{ number_format($balance, 2) }}</td>
                                    </tr>
                                    @foreach ($offsets->skip(1) as $offset)
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td>{{ $offset->date->format('Y-m-d') }}</td>
                                            
                                            {{-- ✅ FIX: show pivot->used_hours for remaining offsets --}}
                                            <td class="text-end">{{ number_format($offset->pivot->used_hours, 2) }}</td>
                                            
                                            <td></td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @endif

            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('select[name="department_id"]').select2({
                theme: 'bootstrap-5',
                placeholder: '-- All Departments --',
                allowClear: true,
                width: 'resolve'
            });
            $('select[name="employee_id"]').select2({
                theme: 'bootstrap-5',
                placeholder: '-- All Employees --',
                allowClear: true,
                width: 'resolve'
            });
        });
    </script>
</x-app-layout>
