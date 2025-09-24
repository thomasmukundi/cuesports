@extends('admin.layout')

@section('title', 'Dashboard - CueSports Admin')
@section('page-title', 'Dashboard')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-content">
                <h3>{{ $stats['total_tournaments'] ?? 0 }}</h3>
                <p>Total Tournaments</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-gamepad"></i>
            </div>
            <div class="stat-content">
                <h3>{{ $stats['total_matches'] ?? 0 }}</h3>
                <p>Total Matches</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>{{ $stats['total_users'] ?? 0 }}</h3>
                <p>Total Players</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <h3>{{ $stats['total_enrollments'] ?? 0 }}</h3>
                <p>Total Enrollments</p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tournament Status</h5>
            </div>
            <div class="card-body">
                <canvas id="tournamentChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Match Status</h5>
            </div>
            <div class="card-body">
                <canvas id="matchesChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- User Growth Chart -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User Growth</h5>
            </div>
            <div class="card-body">
                <canvas id="userChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tournament Chart
    const tournamentCtx = document.getElementById('tournamentChart').getContext('2d');
    new Chart(tournamentCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Completed', 'Pending', 'Upcoming'],
            datasets: [{
                data: [
                    {{ $stats['active_tournaments'] ?? 0 }},
                    {{ $stats['completed_tournaments'] ?? 0 }},
                    {{ $stats['pending_tournaments'] ?? 0 }},
                    {{ $stats['upcoming_tournaments'] ?? 0 }}
                ],
                backgroundColor: ['#10b981', '#6b7280', '#f59e0b', '#06b6d4']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Matches Chart
    const matchesCtx = document.getElementById('matchesChart').getContext('2d');
    new Chart(matchesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Ongoing', 'Completed'],
            datasets: [{
                data: [
                    {{ $stats['ongoing_matches'] ?? 0 }},
                    {{ $stats['completed_matches'] ?? 0 }}
                ],
                backgroundColor: ['#f59e0b', '#10b981']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // User Growth Chart
    const userCtx = document.getElementById('userChart').getContext('2d');
    new Chart(userCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($userGrowth['labels'] ?? []) !!},
            datasets: [{
                label: 'New Users',
                data: {!! json_encode($userGrowth['data'] ?? []) !!},
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>
@endpush
