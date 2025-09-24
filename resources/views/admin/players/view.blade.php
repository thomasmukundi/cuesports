@extends('admin.layout')

@section('title', 'Player Details - CueSports Admin')
@section('page-title', 'Player: ' . $player->name)

@section('content')
<!-- Player Information Section -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Player Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> {{ $player->name }}</p>
                        <p><strong>Email:</strong> {{ $player->email }}</p>
                        <p><strong>Phone:</strong> {{ $player->phone ?? 'N/A' }}</p>
                        <p><strong>Username:</strong> {{ $player->username ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-{{ $player->email_verified_at ? 'success' : 'warning' }}">
                                {{ $player->email_verified_at ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                        <p><strong>Joined:</strong> {{ $player->created_at->format('M d, Y') }}</p>
                        <p><strong>Last Updated:</strong> {{ $player->updated_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Player Statistics -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Player Statistics</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Total Matches:</span>
                    <span class="badge bg-primary">{{ $stats['total_matches'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Matches Won:</span>
                    <span class="badge bg-success">{{ $stats['matches_won'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Win Rate:</span>
                    <span class="badge bg-info">{{ $stats['win_rate'] }}%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Tournaments:</span>
                    <span class="badge bg-warning">{{ $stats['tournaments_registered'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Total Points:</span>
                    <span class="badge bg-secondary">{{ $stats['total_points'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Location Information -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Location Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Region:</strong> {{ $player->community->region->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>County:</strong> {{ $player->community->county->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Community:</strong> {{ $player->community->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Awards Section -->
@if($awards->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Awards & Achievements</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($awards as $award)
                    <div class="col-md-3 mb-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-trophy fa-2x text-warning mb-2"></i>
                                <h6 class="card-title">
                                    @if($award->position == 1)
                                        🥇 1st Place
                                    @elseif($award->position == 2)
                                        🥈 2nd Place
                                    @elseif($award->position == 3)
                                        🥉 3rd Place
                                    @else
                                        Position {{ $award->position }}
                                    @endif
                                </h6>
                                <p class="card-text">
                                    <small class="text-muted">{{ $award->tournament->name ?? 'N/A' }}</small><br>
                                    <span class="badge bg-info">{{ ucfirst($award->level) }} Level</span>
                                    @if($award->prize_amount)
                                        <br><span class="badge bg-success">KSh {{ number_format($award->prize_amount, 2) }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Tournament Registrations -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Tournament Registrations</h5>
                <form method="GET" class="d-flex">
                    <select name="tournament_status" class="form-control form-control-sm me-2" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="upcoming" {{ request('tournament_status') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="active" {{ request('tournament_status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('tournament_status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tournament</th>
                                <th>Registration Date</th>
                                <th>Payment Status</th>
                                <th>Registration Status</th>
                                <th>Tournament Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tournaments as $tournament)
                            <tr>
                                <td><strong>{{ $tournament->name }}</strong></td>
                                <td>{{ \Carbon\Carbon::parse($tournament->registration_date)->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $tournament->payment_status === 'completed' ? 'success' : 'warning' }}">
                                        {{ ucfirst($tournament->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $tournament->registration_status === 'registered' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($tournament->registration_status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $tournament->status === 'active' ? 'success' : ($tournament->status === 'completed' ? 'secondary' : 'info') }}">
                                        {{ ucfirst($tournament->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.tournaments.view', $tournament->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View Tournament
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No tournament registrations found</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Tournament Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $tournaments->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Matches History -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Match History</h5>
                <form method="GET" class="d-flex">
                    <select name="match_status" class="form-control form-control-sm me-2" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('match_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="scheduled" {{ request('match_status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="in_progress" {{ request('match_status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ request('match_status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Opponent</th>
                                <th>Tournament</th>
                                <th>Status</th>
                                <th>Result</th>
                                <th>Score</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($matches as $match)
                            <tr>
                                <td><strong>{{ $match->match_name ?? 'Match #' . $match->id }}</strong></td>
                                <td>
                                    @if($match->player_1_id == $player->id)
                                        {{ $match->player2->name ?? 'TBD' }}
                                    @else
                                        {{ $match->player1->name ?? 'TBD' }}
                                    @endif
                                </td>
                                <td>{{ $match->tournament->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $match->status === 'completed' ? 'success' : ($match->status === 'in_progress' ? 'primary' : 'warning') }}">
                                        {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($match->status === 'completed')
                                        @if($match->winner_id == $player->id)
                                            <span class="badge bg-success">Won</span>
                                        @elseif($match->winner_id)
                                            <span class="badge bg-danger">Lost</span>
                                        @else
                                            <span class="badge bg-secondary">Draw</span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($match->status === 'completed')
                                        {{ $match->player_1_points ?? 0 }} - {{ $match->player_2_points ?? 0 }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $match->created_at->format('M d, Y') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="showMatchDetails('{{ $match->id }}', '{{ $match->match_name ?? 'Match #' . $match->id }}', '{{ $match->tournament->name ?? 'N/A' }}', '{{ $match->player1->name ?? 'TBD' }}', '{{ $match->player2->name ?? 'TBD' }}', '{{ $match->status }}', '{{ $match->winner_id ? ($match->winner_id == $match->player_1_id ? ($match->player1->name ?? 'TBD') : ($match->player2->name ?? 'TBD')) : 'No winner' }}')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-gamepad fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No matches found</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Matches Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $matches->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Back Button -->
<div class="mt-4">
    <a href="{{ route('admin.players') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Players
    </a>
</div>
@endsection
