<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-semibold text-dark">
            {{ __('Available Reports') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                @php
                    $roleGroups = [
                        'employee' => 'Employee Reports',
                        'department head' => 'Department Head Reports',
                        'hr supervisor' => 'HR Reports',
                        'admin' => 'Admin Reports',
                    ];
                    $shownRoutes = [];
                @endphp

                @foreach ($roleGroups as $role => $groupTitle)
                    @php
                        $groupReports = collect($reports)->filter(function ($r) use ($role, $shownRoutes) {
                            return in_array($role, $r['roles']) && !in_array($r['route'], $shownRoutes);
                        });
                        $hasVisible = $groupReports->filter(fn($r) => auth()->user()->hasAnyRole($r['roles']))->isNotEmpty();
                    @endphp

                    @if ($hasVisible)
                        <div class="card shadow-sm mb-5">
                            <div class="card-header bg-light fw-bold text-uppercase small text-muted">
                                {{ $groupTitle }}
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    @foreach ($groupReports as $report)
                                        @if (auth()->user()->hasAnyRole($report['roles']) && !in_array($report['route'], $shownRoutes))
                                            @php $shownRoutes[] = $report['route']; @endphp
                                            <div class="col-md-6 col-lg-4">
                                                <div class="card h-100 border-0 shadow-sm hover-shadow"
                                                     style="cursor: pointer;"
                                                     onclick="location.href='{{ route($report['route']) }}'">
                                                    <div class="card-body">
                                                        <h5 class="card-title fw-bold">{{ $report['title'] }}</h5>
                                                        <p class="card-text text-muted">{{ $report['description'] }}</p>
                                                        <a href="{{ route($report['route']) }}" class="text-primary text-decoration-underline">
                                                            View Report &rarr;
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

                @if (empty($shownRoutes))
                    <div class="alert alert-secondary text-center mt-5">
                        No reports available for your role.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
