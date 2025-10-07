<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CueSports Admin Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sidebar-header h4 {
            color: #1e293b;
            font-weight: 700;
            font-size: 1.25rem;
            margin: 0;
        }
        
        .nav-menu {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin: 0.25rem 1rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.875rem 1rem;
            color: #64748b;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: #f1f5f9;
            color: #3b82f6;
        }
        
        .nav-link.active {
            background: #3b82f6;
            color: white;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            background: #f8fafc;
            padding: 1rem;
        }
        
        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            margin: -1rem -1rem 1rem -1rem;
        }
        
        .page-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .content-area {
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        .stat-number {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .content-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .btn {
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-outline-primary {
            border: 1px solid #3b82f6;
            color: #3b82f6;
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: #3b82f6;
            color: white;
        }
        
        .btn-outline-danger {
            border: 1px solid #ef4444;
            color: #ef4444;
            background: transparent;
        }
        
        .btn-outline-danger:hover {
            background: #ef4444;
            color: white;
        }
        
        .table {
            margin: 0;
        }
        
        .table thead th {
            background: #f8fafc;
            border: none;
            font-weight: 600;
            color: #374151;
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .table tbody td {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background: #f8fafc;
        }
        
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .badge.bg-success {
            background: #10b981 !important;
        }
        
        .badge.bg-warning {
            background: #f59e0b !important;
        }
        
        .badge.bg-secondary {
            background: #6b7280 !important;
        }
        
        .badge.bg-info {
            background: #06b6d4 !important;
        }
        
        .pagination {
            font-size: 14px !important;
        }
        
        .pagination .page-link {
            padding: 0.3rem 0.6rem !important;
            font-size: 14px !important;
            min-height: 28px !important;
            line-height: 1.2 !important;
        }
        
        .pagination .page-item:first-child .page-link,
        .pagination .page-item:last-child .page-link {
            font-size: 12px !important;
            padding: 0.3rem 0.5rem !important;
            min-width: 28px !important;
            height: 28px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* Hide SVG icons and use text instead */
        .pagination svg {
            display: none !important;
        }
        
        .pagination .page-item:first-child .page-link::before {
            content: "<" !important;
            font-weight: bold !important;
            font-size: 12px !important;
        }
        
        .pagination .page-item:last-child .page-link::before {
            content: ">" !important;
            font-weight: bold !important;
            font-size: 12px !important;
        }
        
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.625rem 0.875rem;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-select {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.625rem 0.875rem;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4>CueSports Admin</h4>
            </div>
            <div class="nav-menu">
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.tournaments*') ? 'active' : '' }}" href="{{ route('admin.tournaments') }}">
                        <i class="fas fa-trophy"></i>
                        Tournaments
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.matches*') ? 'active' : '' }}" href="{{ route('admin.matches') }}">
                        <i class="fas fa-gamepad"></i>
                        Matches
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.players*') ? 'active' : '' }}" href="{{ route('admin.players') }}">
                        <i class="fas fa-users"></i>
                        Players
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.messages*') ? 'active' : '' }}" href="{{ route('admin.messages') }}">
                        <i class="fas fa-envelope"></i>
                        Support
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.communications*') ? 'active' : '' }}" href="{{ route('admin.communications') }}">
                        <i class="fas fa-bullhorn"></i>
                        Communications
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.communities*') ? 'active' : '' }}" href="{{ route('admin.communities') }}">
                        <i class="fas fa-users-cog"></i>
                        Communities
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.winners*') ? 'active' : '' }}" href="{{ route('admin.winners') }}">
                        <i class="fas fa-medal"></i>
                        Winners
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.transactions*') ? 'active' : '' }}" href="{{ route('admin.transactions') }}">
                        <i class="fas fa-credit-card"></i>
                        Transactions
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.password*') ? 'active' : '' }}" href="{{ route('admin.password.form') }}">
                        <i class="fas fa-lock"></i>
                        Change Password
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
            
            <div class="content-area">
                @yield('content')
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Match Details Modal -->
    <div class="modal fade" id="matchDetailsModal" tabindex="-1" aria-labelledby="matchDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="matchDetailsModalLabel">Match Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Match Information</h6>
                            <p><strong>Match Name:</strong> <span id="modalMatchName"></span></p>
                            <p><strong>Tournament:</strong> <span id="modalTournament"></span></p>
                            <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Players</h6>
                            <p><strong>Player 1:</strong> <span id="modalPlayer1"></span></p>
                            <p><strong>Player 2:</strong> <span id="modalPlayer2"></span></p>
                            <p><strong>Winner:</strong> <span id="modalWinner"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        function confirmDelete(itemName, deleteUrl) {
            document.getElementById('deleteItemName').textContent = itemName;
            document.getElementById('deleteForm').action = deleteUrl;
            new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
        }

        function showMatchDetails(matchId, matchName, tournament, player1, player2, status, winner) {
            document.getElementById('modalMatchName').textContent = matchName;
            document.getElementById('modalTournament').textContent = tournament;
            document.getElementById('modalPlayer1').textContent = player1;
            document.getElementById('modalPlayer2').textContent = player2;
            document.getElementById('modalStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
            document.getElementById('modalWinner').textContent = winner;
            new bootstrap.Modal(document.getElementById('matchDetailsModal')).show();
        }

        // Cascading dropdown functionality
        function setupCascadingDropdowns() {
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
                                
                                // Auto-submit form after updating dropdowns if it has auto-submit class
                                const form = this.closest('form');
                                if (form && form.classList.contains('auto-submit')) {
                                    form.submit();
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching counties:', error);
                            });
                    } else {
                        // Submit form if region is cleared and form has auto-submit
                        const form = this.closest('form');
                        if (form && form.classList.contains('auto-submit')) {
                            form.submit();
                        }
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
                                
                                // Auto-submit form after updating dropdowns if it has auto-submit class
                                const form = this.closest('form');
                                if (form && form.classList.contains('auto-submit')) {
                                    form.submit();
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching communities:', error);
                            });
                    } else {
                        // Submit form if county is cleared and form has auto-submit
                        const form = this.closest('form');
                        if (form && form.classList.contains('auto-submit')) {
                            form.submit();
                        }
                    }
                });
            }
            
            // Auto-submit community selection for forms with auto-submit class
            if (communitySelect) {
                communitySelect.addEventListener('change', function(e) {
                    const form = this.closest('form');
                    if (form && form.classList.contains('auto-submit')) {
                        form.submit();
                    }
                });
            }
        }

        // Initialize cascading dropdowns when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupCascadingDropdowns();
        });
    </script>
    
    @stack('scripts')
</body>
</html>
