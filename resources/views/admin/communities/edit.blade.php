@extends('admin.layout')

@section('title', 'Edit Community - CueSports Admin')
@section('page-title', 'Edit Community')

@section('content')
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Edit Community: {{ $community->name }}</h3>
        <a href="{{ route('admin.communities') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Communities
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.communities.update', $community) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Community Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $community->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="region_id" class="form-label">Region *</label>
                        <select class="form-select @error('region_id') is-invalid @enderror" id="region_id" name="region_id" required>
                            <option value="">Select Region</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}" {{ old('region_id', $community->region_id) == $region->id ? 'selected' : '' }}>
                                    {{ $region->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('region_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="county_id" class="form-label">County *</label>
                        <select class="form-select @error('county_id') is-invalid @enderror" id="county_id" name="county_id" required>
                            <option value="">Select County</option>
                            @foreach($counties as $county)
                                <option value="{{ $county->id }}" {{ old('county_id', $community->county_id) == $county->id ? 'selected' : '' }}>
                                    {{ $county->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('county_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.communities') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update Community
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
