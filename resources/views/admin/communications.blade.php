@extends('layouts.admin')

@section('title', 'Communications')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📧 Player Communications</h3>
                </div>
                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row mb-4" id="stats-cards">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 id="total-users">-</h4>
                                            <p class="mb-0">Total Users</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-users fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 id="verified-users">-</h4>
                                            <p class="mb-0">Verified Users</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 id="active-users">-</h4>
                                            <p class="mb-0">Active Users (30d)</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-chart-line fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 id="community-users">-</h4>
                                            <p class="mb-0">Community Users</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-map-marker-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Communication Tabs -->
                    <ul class="nav nav-tabs" id="communicationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fas fa-bullhorn"></i> General Communication
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tournament-tab" data-bs-toggle="tab" data-bs-target="#tournament" type="button" role="tab">
                                <i class="fas fa-trophy"></i> Tournament Announcements
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="communicationTabsContent">
                        <!-- General Communication Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <form id="generalCommunicationForm">
                                @csrf
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="subject" class="form-label">Subject *</label>
                                            <input type="text" class="form-control" id="subject" name="subject" required maxlength="255">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="message" class="form-label">Message *</label>
                                            <textarea class="form-control" id="message" name="message" rows="8" required maxlength="5000"></textarea>
                                            <div class="form-text">Maximum 5000 characters</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="action_required" name="action_required">
                                                <label class="form-check-label" for="action_required">
                                                    Mark as requiring action (users will be prompted to check their app)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="target_audience" class="form-label">Target Audience *</label>
                                            <select class="form-select" id="target_audience" name="target_audience" required>
                                                <option value="all">All Users</option>
                                                <option value="verified">Verified Users Only</option>
                                                <option value="active">Active Users (Last 30 days)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-paper-plane"></i> Send Communication
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Tournament Announcements Tab -->
                        <div class="tab-pane fade" id="tournament" role="tabpanel">
                            <form id="tournamentAnnouncementForm">
                                @csrf
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="tournament_id" class="form-label">Select Tournament *</label>
                                            <select class="form-select" id="tournament_id" name="tournament_id" required>
                                                <option value="">Loading tournaments...</option>
                                            </select>
                                        </div>
                                        
                                        <div id="tournament-preview" class="alert alert-info" style="display: none;">
                                            <h6>Tournament Preview:</h6>
                                            <div id="tournament-details"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="tournament_target_audience" class="form-label">Target Audience *</label>
                                            <select class="form-select" id="tournament_target_audience" name="target_audience" required>
                                                <option value="all">All Users</option>
                                                <option value="eligible">Eligible Players Only</option>
                                                <option value="community">Community Level</option>
                                                <option value="county">County Level</option>
                                                <option value="region">Region Level</option>
                                            </select>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success btn-lg">
                                                <i class="fas fa-trophy"></i> Send Tournament Announcement
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div id="results" class="mt-4" style="display: none;">
                        <div class="alert" id="results-alert">
                            <h6 id="results-title"></h6>
                            <div id="results-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load statistics
    loadStats();
    
    // Load tournaments for announcement
    loadTournaments();
    
    // Form handlers
    document.getElementById('generalCommunicationForm').addEventListener('submit', handleGeneralCommunication);
    document.getElementById('tournamentAnnouncementForm').addEventListener('submit', handleTournamentAnnouncement);
    
    // Tournament selection handler
    document.getElementById('tournament_id').addEventListener('change', handleTournamentSelection);
});

async function loadStats() {
    try {
        const response = await fetch('/admin/communications/stats');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('total-users').textContent = data.stats.total_users;
            document.getElementById('verified-users').textContent = data.stats.verified_users;
            document.getElementById('active-users').textContent = data.stats.active_users;
            document.getElementById('community-users').textContent = data.stats.users_by_level.community;
        }
    } catch (error) {
        console.error('Failed to load stats:', error);
    }
}

async function loadTournaments() {
    try {
        const response = await fetch('/admin/api/tournaments');
        const data = await response.json();
        
        const select = document.getElementById('tournament_id');
        select.innerHTML = '<option value="">Select a tournament...</option>';
        
        if (data.success && data.tournaments) {
            data.tournaments.forEach(tournament => {
                const option = document.createElement('option');
                option.value = tournament.id;
                option.textContent = `${tournament.name} (${tournament.level})`;
                option.dataset.tournament = JSON.stringify(tournament);
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load tournaments:', error);
        document.getElementById('tournament_id').innerHTML = '<option value="">Failed to load tournaments</option>';
    }
}

function handleTournamentSelection(event) {
    const option = event.target.selectedOptions[0];
    const preview = document.getElementById('tournament-preview');
    const details = document.getElementById('tournament-details');
    
    if (option && option.dataset.tournament) {
        const tournament = JSON.parse(option.dataset.tournament);
        details.innerHTML = `
            <strong>${tournament.name}</strong><br>
            Level: ${tournament.level}<br>
            Entry Fee: KES ${tournament.entry_fee || 'Free'}<br>
            Registration Deadline: ${tournament.registration_deadline || 'TBD'}
        `;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}

async function handleGeneralCommunication(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    data.action_required = formData.has('action_required');
    
    showLoading('Sending communication...');
    
    try {
        const response = await fetch('/admin/communications/send-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        showResults(result, 'Communication Results');
        
        if (result.success) {
            event.target.reset();
        }
    } catch (error) {
        showResults({success: false, message: 'Network error: ' + error.message}, 'Communication Error');
    }
}

async function handleTournamentAnnouncement(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    showLoading('Sending tournament announcement...');
    
    try {
        const response = await fetch('/admin/communications/tournament-announcement', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        showResults(result, 'Tournament Announcement Results');
        
        if (result.success) {
            event.target.reset();
            document.getElementById('tournament-preview').style.display = 'none';
        }
    } catch (error) {
        showResults({success: false, message: 'Network error: ' + error.message}, 'Announcement Error');
    }
}

function showLoading(message) {
    const results = document.getElementById('results');
    const alert = document.getElementById('results-alert');
    const title = document.getElementById('results-title');
    const content = document.getElementById('results-content');
    
    alert.className = 'alert alert-info';
    title.textContent = 'Processing...';
    content.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${message}`;
    results.style.display = 'block';
}

function showResults(result, title) {
    const results = document.getElementById('results');
    const alert = document.getElementById('results-alert');
    const titleEl = document.getElementById('results-title');
    const content = document.getElementById('results-content');
    
    alert.className = result.success ? 'alert alert-success' : 'alert alert-danger';
    titleEl.textContent = title;
    
    let html = `<p>${result.message}</p>`;
    
    if (result.results) {
        html += `
            <div class="mt-2">
                <strong>Summary:</strong><br>
                Total Recipients: ${result.results.total}<br>
                Successfully Sent: ${result.results.sent}<br>
                Failed: ${result.results.failed}
            </div>
        `;
        
        if (result.results.failed > 0) {
            html += `
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Detailed error information has been logged to the system logs.
                    </small>
                </div>
            `;
        }
    }
    
    content.innerHTML = html;
    results.style.display = 'block';
}

</script>
@endsection
