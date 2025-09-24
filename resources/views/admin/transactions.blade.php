@extends('admin.layout')

@section('title', 'Transactions - CueSports Admin')
@section('page-title', 'Transaction Management')

@section('content')
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Transaction Records</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.transactions') }}" class="mb-3">
            <div class="row mb-2">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search transactions..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="payment_method">
                        <option value="">All Methods</option>
                        <option value="mpesa" {{ request('payment_method') == 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                        <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
                        <option value="bank" {{ request('payment_method') == 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_from" placeholder="From Date" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_to" placeholder="To Date" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <a href="{{ route('admin.transactions') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>

        <!-- Summary Stats -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body py-2">
                        <small>Total Revenue</small>
                        <h6 class="mb-0">KSh {{ number_format($transactions->where('status', 'completed')->sum('amount'), 2) }}</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body py-2">
                        <small>Completed</small>
                        <h6 class="mb-0">{{ $transactions->where('status', 'completed')->count() }}</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body py-2">
                        <small>Pending</small>
                        <h6 class="mb-0">{{ $transactions->where('status', 'pending')->count() }}</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body py-2">
                        <small>Failed</small>
                        <h6 class="mb-0">{{ $transactions->where('status', 'failed')->count() }}</h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Tournament</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                    <tr>
                        <td>
                            <div>
                                <strong>{{ $transaction->user->name ?? 'N/A' }}</strong><br>
                                <small class="text-muted">{{ $transaction->user->email ?? 'N/A' }}</small>
                            </div>
                        </td>
                        <td>{{ $transaction->tournament->name ?? 'N/A' }}</td>
                        <td><strong>KSh {{ number_format($transaction->amount, 2) }}</strong></td>
                        <td>
                            <span class="badge bg-{{ $transaction->status == 'completed' ? 'success' : ($transaction->status == 'pending' ? 'warning' : ($transaction->status == 'failed' ? 'danger' : 'secondary')) }}">
                                {{ ucfirst($transaction->status ?? 'Unknown') }}
                            </span>
                        </td>
                        <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewTransaction({{ $transaction->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($transaction->status == 'pending')
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="markCompleted({{ $transaction->id }})">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="markFailed({{ $transaction->id }})">
                                    <i class="fas fa-times"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No transactions found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transactions->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $transactions->links('pagination::bootstrap-4') }}
        </div>
        @endif
    </div>
</div>

<!-- Transaction Detail Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transactionDetails">
                <!-- Transaction details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewTransaction(id) {
    // Load transaction details via AJAX
    fetch(`/admin/transactions/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('transactionDetails').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Transaction Information</h6>
                        <p><strong>ID:</strong> ${data.transaction_id || 'N/A'}</p>
                        <p><strong>Amount:</strong> KSh ${parseFloat(data.amount).toLocaleString()}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${data.status == 'completed' ? 'success' : (data.status == 'pending' ? 'warning' : 'danger')}">${data.status}</span></p>
                        <p><strong>Payment Method:</strong> ${data.payment_method || 'N/A'}</p>
                        <p><strong>Reference:</strong> ${data.reference || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>User Information</h6>
                        <p><strong>Name:</strong> ${data.user?.name || 'N/A'}</p>
                        <p><strong>Email:</strong> ${data.user?.email || 'N/A'}</p>
                        <p><strong>Phone:</strong> ${data.user?.phone || 'N/A'}</p>
                        <h6>Tournament</h6>
                        <p><strong>Name:</strong> ${data.tournament?.name || 'N/A'}</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Timestamps</h6>
                        <p><strong>Created:</strong> ${new Date(data.created_at).toLocaleString()}</p>
                        <p><strong>Updated:</strong> ${new Date(data.updated_at).toLocaleString()}</p>
                    </div>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('transactionModal')).show();
        })
        .catch(error => {
            alert('Error loading transaction details');
        });
}

function markCompleted(id) {
    if (confirm('Mark this transaction as completed?')) {
        updateTransactionStatus(id, 'completed');
    }
}

function markFailed(id) {
    if (confirm('Mark this transaction as failed?')) {
        updateTransactionStatus(id, 'failed');
    }
}

function updateTransactionStatus(id, status) {
    fetch(`/admin/transactions/${id}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating transaction status');
        }
    })
    .catch(error => {
        alert('Error updating transaction status');
    });
}
</script>
@endsection
