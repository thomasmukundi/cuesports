@extends('admin.layout')

@section('title', 'Community Details - CueSports Admin')
@section('page-title', 'Community: ' . $community->name)

@section('content')
<!-- Community Information Section -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Community Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> {{ $community->name }}</p>
                        <p><strong>Region:</strong> {{ $community->county->region->name ?? 'N/A' }}</p>
                        <p><strong>County:</strong> {{ $community->county->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Created:</strong> {{ $community->created_at->format('M d, Y') }}</p>
                        <p><strong>Last Updated:</strong> {{ $community->updated_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Community Statistics -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Community Statistics</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Total Players:</span>
                    <span class="badge bg-primary">{{ $stats['total_players'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Active Players:</span>
                    <span class="badge bg-success">{{ $stats['active_players'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Tournaments:</span>
                    <span class="badge bg-info">{{ $stats['tournaments_participated'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Total Matches:</span>
                    <span class="badge bg-warning">{{ $stats['total_matches'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Matches Won:</span>
                    <span class="badge bg-secondary">{{ $stats['matches_won'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Players Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Players (Latest 10)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPlayers as $player)
                            <tr>
                                <td><strong>{{ $player->name }}</strong></td>
                                <td>{{ $player->email }}</td>
                                <td>
                                    <span class="badge bg-{{ $player->email_verified_at ? 'success' : 'warning' }}">
                                        {{ $player->email_verified_at ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $player->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.players.view', $player) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No players found in this community</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tournament Participation -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tournament Participation</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tournament</th>
                                <th>Status</th>
                                <th>Community Participants</th>
                                <th>Start Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tournaments as $tournament)
                            <tr>
                                <td><strong>{{ $tournament->name }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $tournament->status === 'active' ? 'success' : ($tournament->status === 'completed' ? 'secondary' : 'info') }}">
                                        {{ ucfirst($tournament->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $tournament->community_participants }}</span>
                                </td>
                                <td>{{ $tournament->start_date ? \Carbon\Carbon::parse($tournament->start_date)->format('M d, Y') : 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('admin.tournaments.view', $tournament) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View Tournament
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No tournament participation found</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Tournament Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $tournaments->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Community Awards Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Community Awards & Achievements</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Player</th>
                                <th>Tournament</th>
                                <th>Position</th>
                                <th>Level</th>
                                <th>Prize</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($awards as $award)
                            <tr>
                                <td><strong>{{ $award->user->name ?? 'N/A' }}</strong></td>
                                <td>{{ $award->tournament->name ?? 'N/A' }}</td>
                                <td>
                                    @if($award->position == 1)
                                        🥇 1st Place
                                    @elseif($award->position == 2)
                                        🥈 2nd Place
                                    @elseif($award->position == 3)
                                        🥉 3rd Place
                                    @else
                                        Position {{ $award->position }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst($award->level) }}</span>
                                </td>
                                <td>
                                    @if($award->prize_amount)
                                        <span class="badge bg-success">KSh {{ number_format($award->prize_amount, 2) }}</span>
                                    @else
                                        <span class="text-muted">No prize</span>
                                    @endif
                                </td>
                                <td>{{ $award->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-medal fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No awards found for this community</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Awards Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $awards->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Back Button -->
<div class="mt-4">
    <a href="{{ route('admin.communities') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Communities
    </a>
</div>
@endsection
