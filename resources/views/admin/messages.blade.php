@extends('admin.layout')

@section('title', 'Support Messages - CueSports Admin')
@section('page-title', 'Support Messages')

@section('content')
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Support Messages</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.messages') }}" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search messages..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                        <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Unread</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.messages') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>From</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $message)
                    <tr>
                        <td><strong>{{ $message->subject }}</strong></td>
                        <td>{{ $message->user->name ?? $message->email }}</td>
                        <td>{{ ucfirst($message->type) }}</td>
                        <td>{{ $message->created_at->format('M d, Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $message->read_at ? 'success' : 'warning' }}">
                                {{ $message->read_at ? 'Read' : 'Unread' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" onclick="alert('View message details coming soon')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="alert('Mark as read coming soon')">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="alert('Delete message coming soon')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No messages found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($messages->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $messages->links('pagination::bootstrap-4') }}
        </div>
        @endif
    </div>
</div>
@endsection
