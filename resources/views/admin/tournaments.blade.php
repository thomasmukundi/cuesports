@extends('admin.layout')

@section('title', 'Tournaments - CueSports Admin')
@section('page-title', 'Tournaments Management')

@section('content')
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Tournaments</h3>
        <a href="{{ route('admin.tournaments.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Tournament
        </a>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.tournaments') }}" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search tournaments..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.tournaments') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Players</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tournaments as $tournament)
                    <tr>
                        <td><strong>{{ $tournament->name }}</strong></td>
                        <td>{{ $tournament->start_date ? $tournament->start_date->format('M d, Y') : 'N/A' }}</td>
                        <td>{{ $tournament->registrations_count ?? 0 }}</td>
                        <td>
                            <span class="badge bg-{{ $tournament->status === 'active' ? 'success' : ($tournament->status === 'completed' ? 'secondary' : ($tournament->status === 'upcoming' ? 'info' : 'warning')) }}">
                                {{ ucfirst($tournament->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('{{ $tournament->name }}', '{{ route('admin.tournaments.destroy', $tournament->id) }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('admin.tournaments.view', $tournament) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No tournaments found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($tournaments->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $tournaments->links('pagination::bootstrap-4') }}
        </div>
        @endif
    </div>
</div>
@endsection
