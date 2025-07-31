{{-- resources/views/projects/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Add a New Project') }}
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

                <div class="card shadow-sm">
                    <div class="card-body">

                        <form method="POST" action="{{ route('projects.store') }}">
                            @csrf

                            <!-- Client -->
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client</label>
                                <select id="client_id" name="client_id" class="form-control" required>
                                    <option value="">-- Select Client --</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Project Name</label>
                                <input id="name" name="name" type="text" class="form-control" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Project Type -->
                            <div class="mb-3">
                                <label for="project_type" class="form-label">Project Type</label>
                                <select id="project_type" name="project_type" class="form-control" required>
                                    <option value="external" {{ old('project_type') == 'external' ? 'selected' : '' }}>External</option>
                                    <option value="internal" {{ old('project_type') == 'internal' ? 'selected' : '' }}>Internal</option>
                                    <option value="client-based" {{ old('project_type') == 'client-based' ? 'selected' : '' }}>Client-Based</option>
                                </select>
                                @error('project_type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Description (optional)</label>
                                <textarea id="description" name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Location -->
                            <div class="mb-3">
                                <label for="location" class="form-label">Location (optional)</label>
                                <input id="location" name="location" type="text" class="form-control" value="{{ old('location') }}">
                                @error('location')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Dates -->
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input id="start_date" name="start_date" type="date" class="form-control" value="{{ old('start_date') }}">
                                    @error('start_date')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input id="end_date" name="end_date" type="date" class="form-control" value="{{ old('end_date') }}">
                                    @error('end_date')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="">-- Select Status --</option>
                                    <option value="planned" {{ old('status') == 'planned' ? 'selected' : '' }}>Planned</option>
                                    <option value="in-progress" {{ old('status') == 'in-progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="on-hold" {{ old('status') == 'on-hold' ? 'selected' : '' }}>On Hold</option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Budget -->
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="budget" class="form-label">Budget (optional)</label>
                                    <input id="budget" name="budget" type="number" step="0.01" class="form-control" value="{{ old('budget') }}">
                                    @error('budget')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col">
                                    <label for="budget_buffer" class="form-label">Budget Buffer (optional)</label>
                                    <input id="budget_buffer" name="budget_buffer" type="number" step="0.01" class="form-control" value="{{ old('budget_buffer') }}">
                                    @error('budget_buffer')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Locked Budget -->
                            <div class="mb-3 form-check">
                                <input id="locked_budget" name="locked_budget" type="checkbox" class="form-check-input" value="1" {{ old('locked_budget') ? 'checked' : '' }}>
                                <label for="locked_budget" class="form-check-label">Lock Budget</label>
                                @error('locked_budget')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Priority -->
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority (optional)</label>
                                <input id="priority" name="priority" type="text" class="form-control" value="{{ old('priority') }}">
                                @error('priority')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Risk Level -->
                            <div class="mb-3">
                                <label for="risk_level" class="form-label">Risk Level (optional)</label>
                                <select id="risk_level" name="risk_level" class="form-control">
                                    <option value="">-- Select Risk Level --</option>
                                    <option value="low" {{ old('risk_level') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('risk_level') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('risk_level') == 'high' ? 'selected' : '' }}>High</option>
                                </select>
                                @error('risk_level')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Completion Date -->
                            <div class="mb-3">
                                <label for="completion_date_actual" class="form-label">Actual Completion Date (optional)</label>
                                <input id="completion_date_actual" name="completion_date_actual" type="date" class="form-control" value="{{ old('completion_date_actual') }}">
                                @error('completion_date_actual')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tags -->
                            <div class="mb-4">
                                <label for="tags" class="form-label">Tags (optional, comma-separated)</label>
                                <input id="tags" name="tags" type="text" class="form-control" value="{{ old('tags') }}">
                                @error('tags')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Create Project') }}
                                </button>

                                <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
