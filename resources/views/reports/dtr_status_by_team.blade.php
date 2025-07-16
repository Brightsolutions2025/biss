<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Employee DTR Status by Department & Team') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12">

                <div class="card shadow-sm mb-4">
                    <div class="card-body">

                        <!-- Payroll Period Filter -->
                        <form method="GET" class="mb-4" id="payrollPeriodForm">
                            <label for="payroll_period_id" class="form-label fw-semibold">Select Payroll Period</label>
                            <select name="payroll_period_id" id="payroll_period_id" class="form-select">
                                @forelse($payrollPeriods as $period)
                                    <option value="{{ $period->id }}" {{ $period->id == $payrollPeriodId ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::parse($period->start_date)->format('M d, Y') }}
                                        -
                                        {{ \Carbon\Carbon::parse($period->end_date)->format('M d, Y') }}
                                    </option>
                                @empty
                                    <option disabled>No payroll periods available</option>
                                @endforelse
                            </select>
                        </form>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Team</th>
                                        <th>DTR Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData as $row)
                                        @php
                                            $timeRecord = \App\Models\TimeRecord::where('employee_id', $row['employee']->id)
                                                ->where('payroll_period_id', $payrollPeriodId)
                                                ->first();
                                        @endphp
                                        <tr>
                                            <td>{{ $row['employee']->first_name }} {{ $row['employee']->last_name }}</td>
                                            <td>{{ $row['department'] }}</td>
                                            <td>{{ $row['team'] }}</td>
                                            <td>
                                                @if($row['status'] === 'Approved')
                                                    <span class="text-success fw-semibold">{{ $row['status'] }}</span>
                                                @elseif($row['status'] === 'Submitted')
                                                    <span class="text-warning fw-semibold">{{ $row['status'] }}</span>
                                                @elseif($row['status'] === 'Rejected')
                                                    <span class="text-danger fw-semibold">{{ $row['status'] }}</span>
                                                @else
                                                    <span class="text-muted fw-semibold">{{ $row['status'] ?? 'Not Submitted' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($timeRecord)
                                                    <a href="{{ route('time_records.show', $timeRecord->id) }}"
                                                       class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                @else
                                                    <span class="text-muted">No Record</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Download Buttons -->
                        <div class="d-flex gap-2 mt-4">
                            <a id="pdfLink"
                               href="{{ route('reports.dtr-status-by-team.pdf', ['payroll_period_id' => $payrollPeriodId]) }}"
                               class="btn btn-outline-primary">
                                Download PDF
                            </a>

                            <a id="excelLink"
                               href="{{ route('reports.dtr-status-by-team.excel', ['payroll_period_id' => $payrollPeriodId]) }}"
                               class="btn btn-outline-success">
                                Export Excel
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- JavaScript: Link Updating -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const select = document.getElementById('payroll_period_id');
            const pdfLink = document.getElementById('pdfLink');
            const excelLink = document.getElementById('excelLink');
            const form = document.getElementById('payrollPeriodForm');

            select.addEventListener('change', function () {
                const periodId = this.value;

                const basePdfUrl = "{{ route('reports.dtr-status-by-team.pdf') }}";
                const baseExcelUrl = "{{ route('reports.dtr-status-by-team.excel') }}";

                pdfLink.href = basePdfUrl + '?payroll_period_id=' + encodeURIComponent(periodId);
                excelLink.href = baseExcelUrl + '?payroll_period_id=' + encodeURIComponent(periodId);

                form.submit();
            });
        });
    </script>
</x-app-layout>
