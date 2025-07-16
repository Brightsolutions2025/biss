<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">{{ __('Shift Details') }}</h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

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

                <div class="card">
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" value="{{ $shift->company->name }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Shift Name</label>
                            <input type="text" class="form-control" value="{{ $shift->name }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Time In</label>
                            <input type="time" class="form-control" value="{{ \Carbon\Carbon::parse($shift->time_in)->format('H:i') }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Time Out</label>
                            <input type="time" class="form-control" value="{{ \Carbon\Carbon::parse($shift->time_out)->format('H:i') }}" disabled>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Is Night Shift</label>
                            <input type="text" class="form-control" value="{{ $shift->is_night_shift ? 'Yes' : 'No' }}" disabled>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('shifts.edit', $shift->id) }}" class="btn btn-primary">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('shifts.destroy', $shift->id) }}" onsubmit="return confirm('Are you sure you want to delete this shift?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
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
        </div>
    </div>
</x-app-layout>
