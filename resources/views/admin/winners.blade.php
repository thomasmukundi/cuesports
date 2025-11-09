@extends('admin.layout')

@section('title', 'Winners - CueSports Admin')
@section('page-title', 'Tournament Winners')

@section('content')
<!-- Success/Error Messages -->
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Tournament Winners</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.winners') }}" class="mb-3">
            <div class="row mb-2">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search winners..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="position">
                        <option value="">All Positions</option>
                        <option value="1" {{ request('position') == '1' ? 'selected' : '' }}>1st Place</option>
                        <option value="2" {{ request('position') == '2' ? 'selected' : '' }}>2nd Place</option>
                        <option value="3" {{ request('position') == '3' ? 'selected' : '' }}>3rd Place</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="region">
                        <option value="">All Regions</option>
                        <option value="nairobi" {{ request('region') == 'nairobi' ? 'selected' : '' }}>Nairobi</option>
                        <option value="central" {{ request('region') == 'central' ? 'selected' : '' }}>Central</option>
                        <option value="coast" {{ request('region') == 'coast' ? 'selected' : '' }}>Coast</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="county">
                        <option value="">All Counties</option>
                        <option value="kiambu" {{ request('county') == 'kiambu' ? 'selected' : '' }}>Kiambu</option>
                        <option value="nairobi" {{ request('county') == 'nairobi' ? 'selected' : '' }}>Nairobi</option>
                        <option value="thika" {{ request('county') == 'thika' ? 'selected' : '' }}>Thika</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="community">
                        <option value="">All Communities</option>
                        <option value="ruiru" {{ request('community') == 'ruiru' ? 'selected' : '' }}>Ruiru</option>
                        <option value="thika" {{ request('community') == 'thika' ? 'selected' : '' }}>Thika</option>
                        <option value="kiambu" {{ request('community') == 'kiambu' ? 'selected' : '' }}>Kiambu</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="specialMatches" name="special_matches" value="1" {{ request('special_matches') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="specialMatches">
                            Special Matches Only
                        </label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.winners') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Tournament</th>
                        <th>Position</th>
                        <th>Prize</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($winners as $winner)
                    <tr>
                        <td><strong>{{ $winner->player->name ?? 'N/A' }}</strong></td>
                        <td>{{ $winner->tournament->name ?? 'N/A' }}</td>
                        <td>
                            @php
                                $positionData = match($winner->position) {
                                    1 => ['class' => 'warning', 'text' => '🥇 1st'],
                                    2 => ['class' => 'secondary', 'text' => '🥈 2nd'],
                                    3 => ['class' => 'success', 'text' => '🥉 3rd'],
                                    default => ['class' => 'info', 'text' => "#{$winner->position}"]
                                };
                            @endphp
                            <span class="badge bg-{{ $positionData['class'] }}">
                                {{ $positionData['text'] }}
                            </span>
                        </td>
                        <td><strong>KSh {{ number_format($winner->prize_amount ?? 0, 2) }}</strong></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#winnerModal" 
                                        onclick="showWinnerDetails({{ json_encode([
                                            'id' => $winner->id,
                                            'player_name' => $winner->player->name ?? 'N/A',
                                            'player_id' => $winner->player->id ?? null,
                                            'tournament_name' => $winner->tournament->name ?? 'N/A',
                                            'tournament_id' => $winner->tournament->id ?? null,
                                            'position' => $winner->position,
                                            'position_text' => $positionData['text'],
                                            'points' => $winner->calculated_points ?? 0,
                                            'wins' => $winner->calculated_wins ?? 0,
                                            'prize_amount' => $winner->prize_amount ?? 0,
                                            'level' => $winner->level ?? 'N/A',
                                            'level_name' => $winner->level_name ?? 'N/A',
                                            'date' => $winner->created_at->format('M d, Y'),
                                            'community' => $winner->player->community->name ?? 'N/A',
                                            'county' => $winner->player->community->county->name ?? 'N/A',
                                            'region' => $winner->player->community->region->name ?? 'N/A',
                                            'is_special' => $winner->tournament->special ?? false,
                                            'tournament_area_scope' => $winner->tournament->area_scope ?? 'N/A'
                                        ]) }})">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="showDeleteConfirmation({{ $winner->id }}, '{{ addslashes($winner->player->name ?? 'N/A') }}', '{{ addslashes($winner->tournament->name ?? 'N/A') }}', {{ $winner->position }})">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No winners found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($winners->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $winners->links('pagination::bootstrap-4') }}
        </div>
        @endif
    </div>
</div>

<!-- Winner Details Modal -->
<div class="modal fade" id="winnerModal" tabindex="-1" aria-labelledby="winnerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="winnerModalLabel">
                    <i class="fas fa-trophy text-warning"></i> Winner Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Player Information -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-user"></i> Player Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Name:</strong>
                                    <span id="modal-player-name" class="text-primary fw-bold"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Community:</strong>
                                    <span id="modal-community" class="badge bg-info"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>County:</strong>
                                    <span id="modal-county" class="badge bg-secondary"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Region:</strong>
                                    <span id="modal-region" class="badge bg-success"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tournament Information -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-trophy"></i> Tournament Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Tournament:</strong>
                                    <span id="modal-tournament-name" class="fw-bold"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Level:</strong>
                                    <span id="modal-level" class="badge bg-primary"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Level Name:</strong>
                                    <span id="modal-level-name"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Tournament Type:</strong>
                                    <span id="modal-tournament-type" class="badge"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Area Scope:</strong>
                                    <span id="modal-area-scope" class="badge bg-warning"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <!-- Performance Stats -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Performance Stats</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 id="modal-position-display" class="text-primary mb-1"></h4>
                                            <small class="text-muted">Position</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 id="modal-points" class="text-success mb-1"></h4>
                                            <small class="text-muted">Points</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <h4 id="modal-wins" class="text-info mb-1"></h4>
                                        <small class="text-muted">Wins</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Prize & Date -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-gift"></i> Prize & Date</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Prize Amount:</strong>
                                    <span id="modal-prize" class="text-success fw-bold fs-5"></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Date Won:</strong>
                                    <span id="modal-date" class="text-muted"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Delete Winner
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>
                
                <p>You are about to delete the winner record for:</p>
                <ul class="list-unstyled">
                    <li><strong>Player:</strong> <span id="delete-player-name"></span></li>
                    <li><strong>Tournament:</strong> <span id="delete-tournament-name"></span></li>
                    <li><strong>Position:</strong> <span id="delete-position"></span></li>
                </ul>
                
                <div class="mt-4">
                    <label for="deleteConfirmationInput" class="form-label">
                        <strong>Type "DELETE" to confirm:</strong>
                    </label>
                    <input type="text" class="form-control" id="deleteConfirmationInput" 
                           placeholder="Type DELETE to confirm" autocomplete="off">
                    <div class="form-text text-danger">
                        This will permanently remove this winner record from the database.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled onclick="confirmDelete()">
                    <i class="fas fa-trash"></i> Delete Winner
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for deletion -->
<form id="deleteWinnerForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function showWinnerDetails(winner) {
    // Player Information
    document.getElementById('modal-player-name').textContent = winner.player_name;
    document.getElementById('modal-community').textContent = winner.community;
    document.getElementById('modal-county').textContent = winner.county;
    document.getElementById('modal-region').textContent = winner.region;
    
    // Tournament Information
    document.getElementById('modal-tournament-name').textContent = winner.tournament_name;
    document.getElementById('modal-level').textContent = winner.level.toUpperCase();
    document.getElementById('modal-level-name').textContent = winner.level_name;
    document.getElementById('modal-area-scope').textContent = winner.tournament_area_scope.toUpperCase();
    
    // Tournament Type
    const tournamentTypeElement = document.getElementById('modal-tournament-type');
    if (winner.is_special) {
        tournamentTypeElement.textContent = 'Special Tournament';
        tournamentTypeElement.className = 'badge bg-info';
    } else {
        tournamentTypeElement.textContent = 'Regular Tournament';
        tournamentTypeElement.className = 'badge bg-light text-dark';
    }
    
    // Performance Stats
    document.getElementById('modal-position-display').textContent = winner.position_text;
    document.getElementById('modal-points').textContent = winner.points;
    document.getElementById('modal-wins').textContent = winner.wins;
    
    // Prize & Date
    document.getElementById('modal-prize').textContent = 'KSh ' + new Intl.NumberFormat().format(winner.prize_amount);
    document.getElementById('modal-date').textContent = winner.date;
}

// Global variable to store winner ID for deletion
let winnerToDelete = null;

function showDeleteConfirmation(winnerId, playerName, tournamentName, position) {
    // Store the winner ID for deletion
    winnerToDelete = winnerId;
    
    // Populate modal with winner details
    document.getElementById('delete-player-name').textContent = playerName;
    document.getElementById('delete-tournament-name').textContent = tournamentName;
    document.getElementById('delete-position').textContent = getPositionText(position);
    
    // Reset the confirmation input and button
    document.getElementById('deleteConfirmationInput').value = '';
    document.getElementById('confirmDeleteBtn').disabled = true;
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    modal.show();
}

function getPositionText(position) {
    switch(position) {
        case 1: return '🥇 1st Place';
        case 2: return '🥈 2nd Place';
        case 3: return '🥉 3rd Place';
        default: return `#${position}`;
    }
}

function confirmDelete() {
    if (winnerToDelete && document.getElementById('deleteConfirmationInput').value === 'DELETE') {
        // Set the form action URL
        const form = document.getElementById('deleteWinnerForm');
        form.action = `/admin/winners/${winnerToDelete}`;
        
        // Submit the form
        form.submit();
    }
}

// Enable/disable delete button based on confirmation input
document.addEventListener('DOMContentLoaded', function() {
    const confirmationInput = document.getElementById('deleteConfirmationInput');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    if (confirmationInput && confirmDeleteBtn) {
        confirmationInput.addEventListener('input', function() {
            confirmDeleteBtn.disabled = this.value !== 'DELETE';
        });
        
        // Allow Enter key to trigger delete if confirmation is correct
        confirmationInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && this.value === 'DELETE') {
                confirmDelete();
            }
        });
    }
});
</script>
@endsection
