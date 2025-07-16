<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark">
            {{ __('User Preferences') }}
        </h2>
    </x-slot>

    <div class="py-5">
        <div class="container">
            <div class="card shadow-sm">
                <div class="card-body">

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <h3 class="h5 fw-semibold mb-4">Current Preferences</h3>

                    @if ($preference)
                        <div class="mb-4">
                            <p><strong>User:</strong> {{ auth()->user()?->name ?? '—' }}</p>

                            <p><strong>Selected Company:</strong> 
                                @if ($preference->company)
                                    {{ $preference->company->name }}
                                @else
                                    <em>null</em>
                                @endif
                            </p>

                            <p><strong>Other Preferences (JSON):</strong></p>
                            <pre class="bg-light p-3 rounded small text-dark overflow-auto">
{{ $preference->preferences ? json_encode($preference->preferences, JSON_PRETTY_PRINT) : 'null' }}
                            </pre>
                        </div>

                        <form method="GET" action="{{ route('preferences.edit', $preference->id) }}">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Edit Preferences') }}
                            </button>
                        </form>
                    @else
                        <p class="text-muted">No preferences found for this user.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
