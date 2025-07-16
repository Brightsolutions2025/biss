<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">{{ __('Approved Leaves Timeline') }}</h2>
    </x-slot>

    <div class="container py-4">
        {{-- Filter Form --}}
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $startDate) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $endDate) }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        @if($leaveRequests->isEmpty())
            <div class="alert alert-info">You have no approved leave requests for the selected period.</div>
        @else
            {{-- Calendar Display --}}
            <div id="calendar"></div>
        @endif

        <br>

        {{-- Download Buttons --}}
        <div class="d-flex gap-2 mb-3">
            <a id="pdfLink"
                href="{{ route('reports.leave_timeline.pdf', request()->only('start_date', 'end_date')) }}"
                class="btn btn-outline-primary">
                Download PDF
            </a>

            <a id="excelLink"
                href="{{ route('reports.leave_timeline.excel', request()->only('start_date', 'end_date')) }}"
                class="btn btn-outline-success">
                Export Excel
            </a>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
        <style>
            html, body {
                height: 100%;
            }

            #calendar {
                flex-grow: 1;
                min-height: 600px;
                max-width: 100%;
                margin: 0 auto;
                padding: 10px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0,0,0,0.05);
            }

            .fc {
                font-size: 0.9rem;
            }
        </style>
    @endpush


    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var calendarEl = document.getElementById('calendar');

                if (calendarEl) {
                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        initialDate: '{{ $startDate }}', // 👈 shows the month of the selected start date
                        height: 'auto',
                        aspectRatio: 1.35,
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,listMonth'
                        },
                        events: {!! $calendarEvents->toJson() !!}
                    });

                    calendar.render();
                }
            });
        </script>
    @endpush
</x-app-layout>
