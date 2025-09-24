@extends('admin.layout')

@section('title', 'Players - CueSports Admin')
@section('page-title', 'Players Management')

@section('content')
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Players</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4" action="{{ route('admin.players') }}">
            <div class="row mb-2">
                <div class="col-md-3">
                    <select name="region" class="form-control">
                        <option value="">All Regions</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" {{ request('region') == $region->id ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="county" class="form-control">
                        <option value="">All Counties</option>
                        @foreach($counties as $county)
                            <option value="{{ $county->id }}" {{ request('county') == $county->id ? 'selected' : '' }}>
                                {{ $county->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="community" class="form-control">
                        <option value="">All Communities</option>
                        @foreach($communities as $community)
                            <option value="{{ $community->id }}" {{ request('community') == $community->id ? 'selected' : '' }}>
                                {{ $community->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.players') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>

        <!-- Bulk Actions -->
        <div id="bulk-actions" class="mb-3" style="display: none;">
            <form id="bulk-delete-form" method="POST" action="{{ route('admin.players.bulk-destroy') }}" onsubmit="return confirmBulkDelete()">
                @csrf
                @method('DELETE')
                <div class="d-flex align-items-center">
                    <span class="me-3"><span id="selected-count">0</span> player(s) selected</span>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="select-all" class="form-check-input">
                        </th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Tournaments</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($players as $player)
                    <tr>
                        <td>
                            @if(!$player->is_admin)
                            <input type="checkbox" name="player_ids[]" value="{{ $player->id }}" class="form-check-input player-checkbox">
                            @endif
                        </td>
                        <td><strong>{{ $player->name }}</strong></td>
                        <td>{{ $player->email }}</td>
                        <td>{{ $player->phone ?? 'N/A' }}</td>
                        <td>{{ $player->tournaments_count ?? 0 }}</td>
                        <td>{{ $player->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.players.view', $player) }}" class="btn btn-sm btn-outline-primary" title="View Player">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(!$player->is_admin)
                                <form method="POST" action="{{ route('admin.players.destroy', $player) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this player? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Player">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No players found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($players->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $players->links('pagination::bootstrap-4') }}
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const playerCheckboxes = document.querySelectorAll('.player-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');

    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        playerCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Individual checkbox functionality
    playerCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllState();
            updateBulkActions();
        });
    });

    function updateSelectAllState() {
        const checkedBoxes = document.querySelectorAll('.player-checkbox:checked');
        const totalBoxes = playerCheckboxes.length;
        
        if (checkedBoxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedBoxes.length === totalBoxes) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        }
    }

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.player-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count > 0) {
            bulkActions.style.display = 'block';
            selectedCountSpan.textContent = count;
            
            // Add selected checkboxes to the bulk delete form
            const existingInputs = bulkDeleteForm.querySelectorAll('input[name="player_ids[]"]');
            existingInputs.forEach(input => input.remove());
            
            checkedBoxes.forEach(checkbox => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'player_ids[]';
                hiddenInput.value = checkbox.value;
                bulkDeleteForm.appendChild(hiddenInput);
            });
        } else {
            bulkActions.style.display = 'none';
        }
    }
});

function confirmBulkDelete() {
    const checkedBoxes = document.querySelectorAll('.player-checkbox:checked');
    const count = checkedBoxes.length;
    
    if (count === 0) {
        alert('Please select at least one player to delete.');
        return false;
    }
    
    return confirm(`Are you sure you want to delete ${count} selected player(s)? This action cannot be undone.`);
}
</script>

@endsection
