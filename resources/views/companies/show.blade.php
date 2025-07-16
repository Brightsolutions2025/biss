<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Company Details') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="card shadow-sm">
                    <div class="card-body">

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

                        <!-- Company Info -->
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" value="{{ $company->name }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Industry</label>
                            <input type="text" class="form-control" value="{{ $company->industry }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" value="{{ $company->address }}" disabled>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" value="{{ $company->phone }}" disabled>
                        </div>

                        <!-- Offset Validity Duration Info -->
                        <div class="mb-3">
                            <label class="form-label">Offset Valid After (days from OT)</label>
                            <input type="text" class="form-control" value="{{ $company->offset_valid_after_days }}" disabled>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Offset Filing Grace Period</label>
                            <input type="text" class="form-control" value="{{ $company->offset_valid_before_days }}" disabled>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-primary">
                                Edit
                            </a>

                            <form method="POST" action="{{ route('companies.destroy', $company->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this company?')">
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
