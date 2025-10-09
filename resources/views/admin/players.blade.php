@extends('admin.layout')

@section('title', 'Players - CueSports Admin')
@section('page-title', 'Players Management')

@section('styles')
<style>
.fcm-token-cell {
    max-width: 200px;
    word-wrap: break-word;
}
.fcm-token-preview {
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    cursor: pointer;
}
.fcm-token-preview:hover {
    background-color: #f8f9fa;
}
</style>
@endsection

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
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <span class="me-3">
                        <span id="selected-count">0</span> player(s) selected on this page
                        <span id="all-pages-indicator" style="display: none;" class="text-info">
                            (All {{ $players->total() - DB::table('users')->where('is_admin', true)->count() }} non-admin players across all pages)
                        </span>
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="select-all-pages-btn" class="btn btn-outline-info btn-sm" onclick="selectAllPages()">
                        <i class="fas fa-check-double"></i> Select All Pages
                    </button>
                    <form id="bulk-delete-form" method="POST" action="{{ route('admin.players.bulk-destroy') }}" onsubmit="return confirmBulkDelete()" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <!-- Hidden inputs for current filters -->
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="region" value="{{ request('region') }}">
                        <input type="hidden" name="county" value="{{ request('county') }}">
                        <input type="hidden" name="community" value="{{ request('community') }}">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <input type="hidden" id="select-all-pages-input" name="select_all_pages" value="false">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> Delete Selected
                        </button>
                    </form>
                </div>
            </div>
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
                        {{-- <th>FCM Token</th> --}}
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
                        {{-- <td class="fcm-token-cell">
                            @if($player->fcm_token)
                                <div class="mb-1">
                                    <span class="badge bg-success" title="FCM Token Active">
                                        <i class="fas fa-bell"></i> Active
                                    </span>
                                </div>
                                <div class="fcm-token-preview" 
                                     title="Click to copy full token: {{ $player->fcm_token }}"
                                     onclick="copyToClipboard('{{ $player->fcm_token }}', this)">
                                    {{ Str::limit($player->fcm_token, 25) }}...
                                </div>
                                @if($player->fcm_token_updated_at)
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-clock"></i> {{ $player->fcm_token_updated_at->format('M d, H:i') }}
                                </small>
                                @endif
                            @else
                                <span class="badge bg-secondary">
                                    <i class="fas fa-bell-slash"></i> No Token
                                </span>
                            @endif
                        </td> --}}
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
    const allPagesIndicator = document.getElementById('all-pages-indicator');
    const selectAllPagesBtn = document.getElementById('select-all-pages-btn');
    const selectAllPagesInput = document.getElementById('select-all-pages-input');
    
    let isAllPagesSelected = false;

    // Select all functionality (current page)
    selectAllCheckbox.addEventListener('change', function() {
        if (isAllPagesSelected) {
            resetAllPagesSelection();
        }
        
        playerCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Individual checkbox functionality
    playerCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (isAllPagesSelected) {
                resetAllPagesSelection();
            }
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
        
        if (count > 0 || isAllPagesSelected) {
            bulkActions.style.display = 'block';
            
            if (isAllPagesSelected) {
                selectedCountSpan.textContent = 'All';
                allPagesIndicator.style.display = 'inline';
                selectAllPagesBtn.style.display = 'none';
            } else {
                selectedCountSpan.textContent = count;
                allPagesIndicator.style.display = 'none';
                selectAllPagesBtn.style.display = 'inline-block';
            }
            
            // Add selected checkboxes to the bulk delete form (only if not all pages selected)
            if (!isAllPagesSelected) {
                const existingInputs = bulkDeleteForm.querySelectorAll('input[name="player_ids[]"]');
                existingInputs.forEach(input => input.remove());
                
                checkedBoxes.forEach(checkbox => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'player_ids[]';
                    hiddenInput.value = checkbox.value;
                    bulkDeleteForm.appendChild(hiddenInput);
                });
            }
        } else {
            bulkActions.style.display = 'none';
            selectAllPagesBtn.style.display = 'inline-block';
        }
    }

    function resetAllPagesSelection() {
        isAllPagesSelected = false;
        selectAllPagesInput.value = 'false';
        allPagesIndicator.style.display = 'none';
        selectAllPagesBtn.style.display = 'inline-block';
        selectAllPagesBtn.innerHTML = '<i class="fas fa-check-double"></i> Select All Pages';
        selectAllPagesBtn.className = 'btn btn-outline-info btn-sm';
    }

    // Global function for select all pages
    window.selectAllPages = function() {
        if (!isAllPagesSelected) {
            // Select all pages
            isAllPagesSelected = true;
            selectAllPagesInput.value = 'true';
            
            // Update UI
            selectAllPagesBtn.innerHTML = '<i class="fas fa-times"></i> Cancel All Pages';
            selectAllPagesBtn.className = 'btn btn-outline-warning btn-sm';
            
            // Clear individual selections
            playerCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            
            updateBulkActions();
        } else {
            // Cancel all pages selection
            resetAllPagesSelection();
            updateBulkActions();
        }
    };
});

function confirmBulkDelete() {
    const isAllPages = document.getElementById('select-all-pages-input').value === 'true';
    
    if (isAllPages) {
        const totalCount = {{ $players->total() - DB::table('users')->where('is_admin', true)->count() }};
        return confirm(`Are you sure you want to delete ALL ${totalCount} non-admin players across all pages? This action cannot be undone and will delete players matching the current filters.`);
    } else {
        const checkedBoxes = document.querySelectorAll('.player-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count === 0) {
            alert('Please select at least one player to delete.');
            return false;
        }
        
        return confirm(`Are you sure you want to delete ${count} selected player(s) on this page? This action cannot be undone.`);
    }
}

function copyToClipboard(text, element) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success feedback
        const originalContent = element.innerHTML;
        element.innerHTML = '<i class="fas fa-check text-success"></i> Copied!';
        element.style.backgroundColor = '#d4edda';
        
        // Reset after 2 seconds
        setTimeout(function() {
            element.innerHTML = originalContent;
            element.style.backgroundColor = '';
        }, 2000);
    }).catch(function(err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        // Show success feedback
        const originalContent = element.innerHTML;
        element.innerHTML = '<i class="fas fa-check text-success"></i> Copied!';
        element.style.backgroundColor = '#d4edda';
        
        // Reset after 2 seconds
        setTimeout(function() {
            element.innerHTML = originalContent;
            element.style.backgroundColor = '';
        }, 2000);
    });
}
</script>

@endsection
