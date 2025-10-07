@extends('admin.layout')

@section('title', 'Communities - CueSports Admin')
@section('page-title', 'Communities Management')

@section('content')
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Communities</h3>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-warning" onclick="replaceCommunitiesWithWards()" title="Replace communities with wards from wards.txt">
                <i class="fas fa-sync-alt me-1"></i>Replace with Wards
            </button>
            <a href="{{ route('admin.communities.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add Community
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.communities') }}" class="row g-3 mb-4">
            <div class="row">
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
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.communities') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Members</th>
                        <th>Tournaments</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($communities as $community)
                    <tr>
                        <td><strong>{{ $community->name }}</strong></td>
                        <td>
                            {{ $community->county->name ?? 'N/A' }}, {{ $community->region->name ?? 'N/A' }}
                        </td>
                        <td>{{ $community->members_count ?? 0 }}</td>
                        <td>{{ $community->tournaments_count ?? 0 }}</td>
                        <td>{{ $community->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.communities.view', $community) }}" class="btn btn-sm btn-info text-white" style="border: 2px solid #0dcaf0;" onclick="event.stopPropagation(); return true;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.communities.edit', $community) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('{{ $community->name }}', '{{ route('admin.communities.delete', $community) }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-users-cog fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No communities found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($communities->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $communities->links('pagination::bootstrap-4') }}
        </div>
        @endif
    </div>
</div>

<script>
function replaceCommunitiesWithWards() {
    if (!confirm('This will delete all communities that have no players and replace them with wards from wards.txt. Communities with players will be preserved. Are you sure you want to continue?')) {
        return;
    }
    
    // Show loading state
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';
    button.disabled = true;
    
    fetch('{{ route("admin.communities.replace-with-wards") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Success! ${data.message}\n\nDeleted: ${data.deleted_count} communities\nCreated: ${data.created_count} ward communities\nPreserved: ${data.preserved_count} communities with players`);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the request.');
    })
    .finally(() => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>
@endsection
