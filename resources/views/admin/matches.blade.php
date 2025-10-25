@extends('admin.layout')

@section('title', 'Matches - CueSports Admin')
@section('page-title', 'Matches Management')

@section('content')
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Matches</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.matches') }}" class="mb-3">
            <div class="row mb-2">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search matches..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="pending_confirmation" {{ request('status') == 'pending_confirmation' ? 'selected' : '' }}>Pending Confirmation</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="forfeit" {{ request('status') == 'forfeit' ? 'selected' : '' }}>Forfeit</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="winnersOnly" name="winners_only" value="1" {{ request('winners_only') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="winnersOnly">
                            Show only matches with winners
                        </label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.matches') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Match</th>
                        <th>Tournament</th>
                        <th>Players</th>
                        <th>Round</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matches as $match)
                    <tr>
                        <td><strong>{{ $match->match_name }}</strong></td>
                        <td>{{ $match->tournament->name ?? 'N/A' }}</td>
                        <td>
                            {{ $match->player1->name ?? 'TBD' }} vs {{ $match->player2->name ?? 'TBD' }}
                        </td>
                        <td>{{ $match->round_name }}</td>
                        <td>
                            @php
                                $statusColors = [
                                    'pending' => 'secondary',
                                    'scheduled' => 'info',
                                    'in_progress' => 'primary',
                                    'pending_confirmation' => 'warning',
                                    'completed' => 'success',
                                    'forfeit' => 'danger'
                                ];
                                $color = $statusColors[$match->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}">
                                {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="showMatchDetails('{{ $match->id }}', '{{ $match->match_name }}', '{{ $match->tournament->name ?? 'N/A' }}', '{{ $match->player1->name ?? 'N/A' }}', '{{ $match->player2->name ?? 'N/A' }}', '{{ $match->status }}', '{{ $match->winner_id ? ($match->winner_id == $match->player_1_id ? ($match->player1->name ?? 'N/A') : ($match->player2->name ?? 'N/A')) : 'No winner' }}')">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-gamepad fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No matches found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($matches->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $matches->links('pagination::bootstrap-4') }}
        </div>
        @endif
    </div>
</div>
@endsection
