@extends('admin.layout')

@section('title', 'Tournament Details - CueSports Admin')
@section('page-title', 'Tournament: ' . $tournament->name)

@push('styles')
<style>
    /* Ensure the page fits within viewport */
    .container-fluid {
        max-width: 100%;
        padding-left: 15px;
        padding-right: 15px;
    }
    
    /* Make tables more responsive */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Compact form controls */
    .form-control {
        font-size: 0.875rem;
    }
    
    /* Button state indicators */
    .btn-state-indicator {
        position: relative;
    }
    
    .btn-state-indicator.completed::after {
        content: '✓';
        position: absolute;
        top: -5px;
        right: -5px;
        background: #28a745;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    
    .btn-state-indicator.has-matches {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        cursor: not-allowed;
    }
    
    .btn-state-indicator.all-matches-completed {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }
    
    /* Delete button styling */
    .btn-delete-matches {
        transition: all 0.2s ease-in-out;
    }
    
    .btn-delete-matches:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .btn-group {
            flex-direction: column;
            width: 100%;
        }
        
        .btn-group .btn {
            margin-bottom: 0.5rem;
            border-radius: 0.375rem !important;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .card-header {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .card-header .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<!-- Tournament Info Section -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tournament Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> {{ $tournament->name }}</p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-{{ $tournament->status === 'active' ? 'success' : ($tournament->status === 'completed' ? 'secondary' : ($tournament->status === 'upcoming' ? 'info' : 'warning')) }}">
                                {{ ucfirst($tournament->status) }}
                            </span>
                        </p>
                        <p><strong>Type:</strong> 
                            <span class="badge bg-{{ $isSpecial ? 'primary' : 'secondary' }}">
                                {{ $isSpecial ? 'Special Tournament' : 'Level-based Tournament' }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Start Date:</strong> {{ $tournament->start_date ? $tournament->start_date->format('M d, Y') : 'N/A' }}</p>
                        <p><strong>End Date:</strong> {{ $tournament->end_date ? $tournament->end_date->format('M d, Y') : 'N/A' }}</p>
                        @if (!$isSpecial)
                            <p><strong>Current Level:</strong> 
                                <span class="badge bg-info">{{ ucfirst($tournamentLevel) }}</span>
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tournament Progress (for non-special tournaments) -->
    @if (!$isSpecial)
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tournament Progress</h5>
            </div>
            <div class="card-body">
                @foreach (['community', 'county', 'regional', 'national'] as $level)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>{{ ucfirst($level) }} Level:</span>
                    @if ($levelProgress[$level]['completed'])
                        <span class="badge bg-success"><i class="fas fa-check"></i> Completed</span>
                    @elseif ($levelProgress[$level]['all_matches_completed'])
                        <span class="badge bg-info"><i class="fas fa-clock"></i> All Matches Played</span>
                    @elseif ($levelProgress[$level]['has_matches'])
                        <span class="badge bg-warning"><i class="fas fa-play"></i> In Progress</span>
                    @elseif ($levelProgress[$level]['can_initialize'])
                        <span class="badge bg-primary"><i class="fas fa-rocket"></i> Ready to Initialize</span>
                    @else
                        <span class="badge bg-secondary"><i class="fas fa-lock"></i> Pending</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Level Initialization Buttons (for non-special tournaments) -->
@if (!$isSpecial)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tournament Level Management</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3">
                    @foreach (['community', 'county', 'regional', 'national'] as $level)
                        @php
                            $buttonClass = 'btn-secondary';
                            $buttonText = 'Initialize ' . ucfirst($level) . ' Level';
                            $isDisabled = true;
                            $stateClasses = 'btn-state-indicator';
                            
                            if ($levelProgress[$level]['completed']) {
                                $buttonClass = 'btn-success';
                                $buttonText = ucfirst($level) . ' Completed';
                                $stateClasses .= ' completed';
                            } elseif ($levelProgress[$level]['all_matches_completed']) {
                                $buttonClass = 'btn-info';
                                $buttonText = ucfirst($level) . ' Matches Done';
                                $stateClasses .= ' all-matches-completed';
                            } elseif ($levelProgress[$level]['has_matches']) {
                                $buttonClass = 'btn-warning';
                                $buttonText = ucfirst($level) . ' In Progress';
                                $stateClasses .= ' has-matches';
                            } elseif ($levelProgress[$level]['can_initialize']) {
                                $buttonClass = 'btn-primary';
                                $buttonText = 'Initialize ' . ucfirst($level);
                                $isDisabled = false;
                            }
                        @endphp
                        
                        <form method="POST" action="{{ route('admin.tournaments.initialize', [$tournament, $level]) }}" class="d-inline">
                            @csrf
                            <button type="submit" 
                                    class="btn {{ $buttonClass }} {{ $stateClasses }}"
                                    {{ $isDisabled ? 'disabled' : '' }}
                                    title="{{ $levelProgress[$level]['completed'] ? 'Level completed with winners generated' : ($levelProgress[$level]['all_matches_completed'] ? 'All matches played, waiting for winners' : ($levelProgress[$level]['has_matches'] ? 'Matches in progress' : ($levelProgress[$level]['can_initialize'] ? 'Ready to initialize this level' : 'Prerequisites not met'))) }}">
                                @if ($levelProgress[$level]['completed'])
                                    <i class="fas fa-trophy"></i>
                                @elseif ($levelProgress[$level]['all_matches_completed'])
                                    <i class="fas fa-clock"></i>
                                @elseif ($levelProgress[$level]['has_matches'])
                                    <i class="fas fa-play"></i>
                                @elseif ($levelProgress[$level]['can_initialize'])
                                    <i class="fas fa-rocket"></i>
                                @else
                                    <i class="fas fa-lock"></i>
                                @endif
                                {{ $buttonText }}
                            </button>
                        </form>
                    @endforeach
                </div>
                
                @if ($tournament->start_date && !$tournament->start_date->isToday() && $tournament->start_date->isFuture())
                <div class="alert alert-info mt-3">
                    <i class="fas fa-calendar-alt"></i>
                    <strong>Tournament starts on {{ $tournament->start_date->format('M d, Y') }}.</strong><br>
                    Community level can be initialized on the start date.
                </div>
                @elseif ($tournament->start_date && $tournament->start_date->isToday())
                <div class="alert alert-success mt-3">
                    <i class="fas fa-calendar-check"></i>
                    <strong>Tournament starts today!</strong> Community level is ready for initialization.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

<!-- Special Tournament Initialization (for special tournaments) -->
@if ($isSpecial)
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Special Tournament Management</h5>
            </div>
            <div class="card-body">
                @php
                    $hasMatches = \App\Models\PoolMatch::where('tournament_id', $tournament->id)->exists();
                    $allMatchesCompleted = false;
                    $hasWinners = \App\Models\Winner::where('tournament_id', $tournament->id)->exists();
                    
                    if ($hasMatches) {
                        $totalMatches = \App\Models\PoolMatch::where('tournament_id', $tournament->id)->count();
                        $completedMatches = \App\Models\PoolMatch::where('tournament_id', $tournament->id)
                            ->where('status', 'completed')->count();
                        $allMatchesCompleted = $totalMatches > 0 && $totalMatches === $completedMatches;
                    }
                    
                    $canStart = $tournament->start_date && $tournament->start_date->isToday();
                    $canInitialize = $canStart && !$hasMatches;
                    
                    $buttonClass = 'btn-secondary';
                    $buttonText = 'Initialize Tournament';
                    $isDisabled = true;
                    $stateClasses = 'btn-state-indicator';
                    
                    if ($hasWinners) {
                        $buttonClass = 'btn-success';
                        $buttonText = 'Tournament Completed';
                        $stateClasses .= ' completed';
                    } elseif ($allMatchesCompleted) {
                        $buttonClass = 'btn-info';
                        $buttonText = 'All Matches Completed';
                        $stateClasses .= ' all-matches-completed';
                    } elseif ($hasMatches) {
                        $buttonClass = 'btn-warning';
                        $buttonText = 'Tournament In Progress';
                        $stateClasses .= ' has-matches';
                    } elseif ($canInitialize) {
                        $buttonClass = 'btn-primary';
                        $buttonText = 'Initialize Tournament';
                        $isDisabled = false;
                    }
                @endphp
                
                <div class="d-flex align-items-center gap-3">
                    <form method="POST" action="{{ route('admin.tournaments.initialize', [$tournament, 'special']) }}" class="d-inline">
                        @csrf
                        <button type="submit" 
                                class="btn {{ $buttonClass }} {{ $stateClasses }}"
                                {{ $isDisabled ? 'disabled' : '' }}
                                title="{{ $hasWinners ? 'Tournament completed with winners generated' : ($allMatchesCompleted ? 'All matches played, waiting for winners' : ($hasMatches ? 'Tournament matches in progress' : ($canInitialize ? 'Ready to initialize tournament' : 'Tournament starts on ' . ($tournament->start_date ? $tournament->start_date->format('M d, Y') : 'TBD')))) }}">
                            @if ($hasWinners)
                                <i class="fas fa-trophy"></i>
                            @elseif ($allMatchesCompleted)
                                <i class="fas fa-clock"></i>
                            @elseif ($hasMatches)
                                <i class="fas fa-play"></i>
                            @elseif ($canInitialize)
                                <i class="fas fa-rocket"></i>
                            @else
                                <i class="fas fa-calendar-alt"></i>
                            @endif
                            {{ $buttonText }}
                        </button>
                    </form>
                    
                    <div class="tournament-status">
                        @if ($hasWinners)
                            <span class="badge bg-success"><i class="fas fa-check"></i> Tournament Completed</span>
                        @elseif ($allMatchesCompleted)
                            <span class="badge bg-info"><i class="fas fa-clock"></i> All Matches Played</span>
                        @elseif ($hasMatches)
                            <span class="badge bg-warning"><i class="fas fa-play"></i> Tournament In Progress</span>
                        @elseif ($canInitialize)
                            <span class="badge bg-primary"><i class="fas fa-rocket"></i> Ready to Initialize</span>
                        @else
                            <span class="badge bg-secondary"><i class="fas fa-calendar-alt"></i> Waiting for Start Date</span>
                        @endif
                    </div>
                </div>
                
                @if ($tournament->start_date && !$tournament->start_date->isToday() && $tournament->start_date->isFuture())
                <div class="alert alert-info mt-3">
                    <i class="fas fa-calendar-alt"></i>
                    <strong>Special tournament starts on {{ $tournament->start_date->format('M d, Y') }}.</strong><br>
                    Tournament can be initialized on the start date.
                </div>
                @elseif ($tournament->start_date && $tournament->start_date->isToday())
                <div class="alert alert-success mt-3">
                    <i class="fas fa-calendar-check"></i>
                    <strong>Tournament starts today!</strong> Special tournament is ready for initialization.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

<!-- Unplayed Matches Warning -->
@if ($unplayedMatches > 0)
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Warning:</strong> There are {{ $unplayedMatches }} scheduled matches that haven't been played yet.
</div>
@endif

<!-- Matches Section -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Tournament Matches</h5>
        @if($tournament->matches()->count() > 0)
        <form method="POST" action="{{ route('admin.tournaments.delete-matches', $tournament) }}" class="d-inline" 
              onsubmit="return confirmDeleteAllMatches({{ $tournament->matches()->count() }}, '{{ $tournament->name }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm btn-delete-matches" 
                    title="Delete all matches for this tournament">
                <i class="fas fa-trash"></i> Delete All Matches ({{ $tournament->matches()->count() }})
            </button>
        </form>
        @endif
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4">
            @if (!$isSpecial)
            <div class="col-md-2">
                <label class="form-label">Level</label>
                <select name="level" class="form-control" onchange="this.form.submit()">
                    <option value="">All Levels</option>
                    <option value="community" {{ request('level') === 'community' ? 'selected' : '' }}>Community</option>
                    <option value="county" {{ request('level') === 'county' ? 'selected' : '' }}>County</option>
                    <option value="regional" {{ request('level') === 'regional' ? 'selected' : '' }}>Regional</option>
                    <option value="national" {{ request('level') === 'national' ? 'selected' : '' }}>National</option>
                </select>
            </div>
            @endif
            
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Round</label>
                <select name="round" class="form-control" onchange="this.form.submit()">
                    <option value="">All Rounds</option>
                    @foreach ($availableRounds as $round)
                        <option value="{{ $round }}" {{ request('round') === $round ? 'selected' : '' }}>
                            {{ $round }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <!-- Location filters based on selected level -->
            @if (!$isSpecial && request('level'))
                @if (request('level') === 'community')
                <div class="col-md-2">
                    <label class="form-label">Region</label>
                    <select name="region" class="form-control" data-filter="region">
                        <option value="">All Regions</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}" {{ request('region') == $region->id ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">County</label>
                    <select name="county" class="form-control" data-filter="county">
                        <option value="">All Counties</option>
                        @foreach ($counties as $county)
                            <option value="{{ $county->id }}" {{ request('county') == $county->id ? 'selected' : '' }}>
                                {{ $county->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Community</label>
                    <select name="community" class="form-control" data-filter="community">
                        <option value="">All Communities</option>
                        @foreach ($communities as $community)
                            <option value="{{ $community->id }}" {{ request('community') == $community->id ? 'selected' : '' }}>
                                {{ $community->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @elseif (request('level') === 'county')
                <div class="col-md-2">
                    <label class="form-label">Region</label>
                    <select name="region" class="form-control" data-filter="region">
                        <option value="">All Regions</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}" {{ request('region') == $region->id ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">County</label>
                    <select name="county" class="form-control" data-filter="county">
                        <option value="">All Counties</option>
                        @foreach ($counties as $county)
                            <option value="{{ $county->id }}" {{ request('county') == $county->id ? 'selected' : '' }}>
                                {{ $county->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @elseif (request('level') === 'regional')
                <div class="col-md-2">
                    <label class="form-label">Region</label>
                    <select name="region" class="form-control" data-filter="region">
                        <option value="">All Regions</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}" {{ request('region') == $region->id ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            @endif
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="{{ route('admin.tournaments.view', $tournament) }}" class="btn btn-secondary">
                    <i class="fas fa-refresh"></i> Clear
                </a>
            </div>
        </form>
        
        <!-- Matches Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Match</th>
                        <th>Players</th>
                        <th>Level</th>
                        <th>Round</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matches as $match)
                    <tr>
                        <td><strong>{{ $match->match_name ?? 'Match #' . $match->id }}</strong></td>
                        <td>
                            <div>{{ $match->player1->name ?? 'TBD' }}</div>
                            <small class="text-muted">vs</small>
                            <div>{{ $match->player2->name ?? 'TBD' }}</div>
                        </td>
                        <td>
                            @if (!$isSpecial)
                                <span class="badge bg-info">{{ ucfirst($match->level ?? 'N/A') }}</span>
                            @else
                                <span class="badge bg-primary">Special</span>
                            @endif
                        </td>
                        <td>{{ $match->round_name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $match->status === 'completed' ? 'success' : ($match->status === 'in_progress' ? 'primary' : 'warning') }}">
                                {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                            </span>
                        </td>
                        <td>
                            @if ($match->status === 'completed')
                                {{ $match->player_1_points ?? 0 }} - {{ $match->player_2_points ?? 0 }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="showMatchDetails('{{ $match->id }}', '{{ $match->match_name ?? 'Match #' . $match->id }}', '{{ $tournament->name }}', '{{ $match->player1->name ?? 'TBD' }}', '{{ $match->player2->name ?? 'TBD' }}', '{{ $match->status }}', '{{ $match->winner_id ? ($match->winner_id == $match->player_1_id ? ($match->player1->name ?? 'TBD') : ($match->player2->name ?? 'TBD')) : 'No winner' }}')">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-gamepad fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No matches found for the selected filters</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $matches->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

<!-- Back Button -->
<div class="mt-4">
    <a href="{{ route('admin.tournaments') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Tournaments
    </a>
</div>
@endsection

@push('scripts')
<script>
// Enhanced confirmation for deleting all matches
function confirmDeleteAllMatches(matchCount, tournamentName) {
    const message = `⚠️ DANGER: Delete All Matches\n\n` +
                   `You are about to delete ALL ${matchCount} matches from "${tournamentName}".\n\n` +
                   `This will:\n` +
                   `• Remove all match data permanently\n` +
                   `• Delete all match results and scores\n` +
                   `• Reset tournament progress\n` +
                   `• Cannot be undone\n\n` +
                   `Type "DELETE" to confirm:`;
    
    const userInput = prompt(message);
    return userInput === 'DELETE';
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle cascading dropdowns for tournament view
    const regionSelect = document.querySelector('select[name="region"]');
    const countySelect = document.querySelector('select[name="county"]');
    const communitySelect = document.querySelector('select[name="community"]');

    if (regionSelect && countySelect) {
        regionSelect.addEventListener('change', function(e) {
            const regionId = this.value;
            
            // Reset county and community dropdowns
            countySelect.innerHTML = '<option value="">All Counties</option>';
            if (communitySelect) {
                communitySelect.innerHTML = '<option value="">All Communities</option>';
            }

            if (regionId) {
                fetch(`/admin/api/counties/${regionId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(counties => {
                        counties.forEach(county => {
                            const option = document.createElement('option');
                            option.value = county.id;
                            option.textContent = county.name;
                            countySelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching counties:', error);
                    });
            }
        });
    }

    if (countySelect && communitySelect) {
        countySelect.addEventListener('change', function(e) {
            const countyId = this.value;
            
            // Reset community dropdown
            communitySelect.innerHTML = '<option value="">All Communities</option>';

            if (countyId) {
                fetch(`/admin/api/communities/${countyId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(communities => {
                        communities.forEach(community => {
                            const option = document.createElement('option');
                            option.value = community.id;
                            option.textContent = community.name;
                            communitySelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching communities:', error);
                    });
            }
        });
    }
});
</script>
@endpush
