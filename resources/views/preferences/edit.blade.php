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
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('preferences.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">
                                Active Company
                            </label>
                            <select name="company_id" class="form-select">
                                <option value="">None</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" 
                                        {{ old('company_id', optional($preference)->company_id) == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="preferences[theme]" class="form-label">
                                Theme (optional example)
                            </label>
                            <input type="text" name="preferences[theme]" id="preferences[theme]" class="form-control"
                                value="{{ old('preferences.theme', $preference->preferences['theme'] ?? '') }}">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Update') }}
                            </button>

                            <a href="javascript:history.back()" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
