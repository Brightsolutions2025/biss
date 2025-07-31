{{-- resources/views/projects/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 text-dark">
            {{ __('Project Details') }}
        </h2>
    </x-slot>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

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

                <div class="mb-4">
                    <label class="form-label fw-semibold">Project Name</label>
                    <input type="text" class="form-control bg-light" value="{{ $project->name }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Client</label>
                    <input type="text" class="form-control bg-light" value="{{ $project->client->name ?? 'N/A' }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Company</label>
                    <input type="text" class="form-control bg-light" value="{{ $project->company->name ?? 'N/A' }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Project Type</label>
                    <input type="text" class="form-control bg-light" value="{{ ucfirst($project->project_type) }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea class="form-control bg-light" rows="3" disabled>{{ $project->description }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Location</label>
                    <input type="text" class="form-control bg-light" value="{{ $project->location }}" disabled>
                </div>

                <div class="mb-4 row">
                    <div class="col">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="text" class="form-control bg-light" value="{{ $project->start_date }}" disabled>
                    </div>
                    <div class="col">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="text" class="form-control bg-light" value="{{ $project->end_date }}" disabled>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Status</label>
                    <input type="text" class="form-control bg-light" value="{{ ucfirst($project->status) }}" disabled>
                </div>

                <div class="mb-4 row">
                    <div class="col">
                        <label class="form-label fw-semibold">Budget</label>
                        <input type="text" class="form-control bg-light" value="{{ number_format($project->budget, 2) }}" disabled>
                    </div>
                    <div class="col">
                        <label class="form-label fw-semibold">Buffer</label>
                        <input type="text" class="form-control bg-light" value="{{ number_format($project->budget_buffer, 2) }}" disabled>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Locked Budget</label>
                    <input type="text" class="form-control bg-light" value="{{ $project->locked_budget ? 'Yes' : 'No' }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Priority</label>
                    <input type="text" class="form-control bg-light" value="{{ ucfirst($project->priority) }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Project Manager</label>
                    <input type="text" class="form-control bg-light" value="{{ $project->projectManager?->name ?? 'None' }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Actual Completion Date</label>
                    <input type="text" class="form-control bg-light" value="{{ $project->completion_date_actual }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Risk Level</label>
                    <input type="text" class="form-control bg-light" value="{{ ucfirst($project->risk_level) }}" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Tags</label>
                    <input type="text" class="form-control bg-light" value="{{ is_array($project->tags) ? implode(', ', $project->tags) : $project->tags }}" disabled>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-primary">
                        Edit
                    </a>

                    <form method="POST" action="{{ route('projects.destroy', $project->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this project?')">
                            Delete
                        </button>
                    </form>

                    <a href="{{ route('projects.index') }}" class="btn btn-secondary ms-auto">
                        Back to List
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
