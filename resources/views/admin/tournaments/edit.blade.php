@extends('admin.layout')

@section('title', 'Edit Tournament - CueSports Admin')
@section('page-title', 'Edit Tournament')

@section('content')
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Edit Tournament: {{ $tournament->name }}</h3>
        <a href="{{ route('admin.tournaments') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Tournaments
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.tournaments.update', $tournament) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Tournament Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $tournament->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="">Select Status</option>
                            <option value="upcoming" {{ old('status', $tournament->status) == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                            <option value="pending" {{ old('status', $tournament->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="active" {{ old('status', $tournament->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ old('status', $tournament->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="special" class="form-label">Tournament Type *</label>
                        <select class="form-select @error('special') is-invalid @enderror" id="special" name="special" required>
                            <option value="0" {{ old('special', $tournament->special) == '0' ? 'selected' : '' }}>Regular Tournament</option>
                            <option value="1" {{ old('special', $tournament->special) == '1' ? 'selected' : '' }}>Special Tournament</option>
                        </select>
                        <div class="form-text">Special tournaments can trigger round robin at any level</div>
                        @error('special')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="automation_mode" class="form-label">Automation Mode *</label>
                        <select class="form-select @error('automation_mode') is-invalid @enderror" id="automation_mode" name="automation_mode" required>
                            <option value="automatic" {{ old('automation_mode', $tournament->automation_mode) == 'automatic' ? 'selected' : '' }}>Automatic</option>
                            <option value="manual" {{ old('automation_mode', $tournament->automation_mode) == 'manual' ? 'selected' : '' }}>Manual</option>
                        </select>
                        <div class="form-text">Automatic mode will progress tournament levels automatically</div>
                        @error('automation_mode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date *</label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                               id="start_date" name="start_date" value="{{ old('start_date', $tournament->start_date ? $tournament->start_date->format('Y-m-d') : '') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                               id="end_date" name="end_date" value="{{ old('end_date', $tournament->end_date ? $tournament->end_date->format('Y-m-d') : '') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="registration_deadline" class="form-label">Registration Deadline *</label>
                        <input type="date" class="form-control @error('registration_deadline') is-invalid @enderror" 
                               id="registration_deadline" name="registration_deadline" value="{{ old('registration_deadline', $tournament->registration_deadline ? $tournament->registration_deadline->format('Y-m-d') : '') }}" required>
                        @error('registration_deadline')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="max_participants" class="form-label">Max Participants</label>
                        <input type="number" class="form-control @error('max_participants') is-invalid @enderror" 
                               id="max_participants" name="max_participants" value="{{ old('max_participants', $tournament->max_participants) }}" min="1">
                        @error('max_participants')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="winners" class="form-label">Number of Winners</label>
                        <input type="number" class="form-control @error('winners') is-invalid @enderror" 
                               id="winners" name="winners" value="{{ old('winners', $tournament->winners) }}" min="1" max="50">
                        <div class="form-text">Leave empty or set to 3 for default knockout format. Set higher values to enable round robin for final rounds.</div>
                        @error('winners')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="area_scope" class="form-label">Area Scope</label>
                        <select class="form-select @error('area_scope') is-invalid @enderror" id="area_scope" name="area_scope">
                            <option value="">Select Scope (Optional)</option>
                            <option value="community" {{ old('area_scope', $tournament->area_scope) == 'community' ? 'selected' : '' }}>Community</option>
                            <option value="county" {{ old('area_scope', $tournament->area_scope) == 'county' ? 'selected' : '' }}>County</option>
                            <option value="regional" {{ old('area_scope', $tournament->area_scope) == 'regional' ? 'selected' : '' }}>Regional</option>
                            <option value="national" {{ old('area_scope', $tournament->area_scope) == 'national' ? 'selected' : '' }}>National</option>
                        </select>
                        @error('area_scope')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="area_name" class="form-label">Area Name</label>
                        <input type="text" class="form-control @error('area_name') is-invalid @enderror" 
                               id="area_name" name="area_name" value="{{ old('area_name', $tournament->area_name) }}" 
                               placeholder="Optional: Specific area name">
                        <div class="form-text">Optional: Specify the exact area name if scope is selected</div>
                        @error('area_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="entry_fee" class="form-label">Entry Fee (KES)</label>
                        <input type="number" step="0.01" class="form-control @error('entry_fee') is-invalid @enderror" 
                               id="entry_fee" name="entry_fee" value="{{ old('entry_fee', $tournament->entry_fee) }}" min="0">
                        @error('entry_fee')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="tournament_charge" class="form-label">Tournament Charge (KES)</label>
                        <input type="number" step="0.01" class="form-control @error('tournament_charge') is-invalid @enderror" 
                               id="tournament_charge" name="tournament_charge" value="{{ old('tournament_charge', $tournament->tournament_charge) }}" min="0">
                        @error('tournament_charge')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="community_prize" class="form-label">Community Prize (KES)</label>
                        <input type="number" step="0.01" class="form-control @error('community_prize') is-invalid @enderror" 
                               id="community_prize" name="community_prize" value="{{ old('community_prize', $tournament->community_prize) }}" min="0">
                        @error('community_prize')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="county_prize" class="form-label">County Prize (KES)</label>
                        <input type="number" step="0.01" class="form-control @error('county_prize') is-invalid @enderror" 
                               id="county_prize" name="county_prize" value="{{ old('county_prize', $tournament->county_prize) }}" min="0">
                        @error('county_prize')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="regional_prize" class="form-label">Regional Prize (KES)</label>
                        <input type="number" step="0.01" class="form-control @error('regional_prize') is-invalid @enderror" 
                               id="regional_prize" name="regional_prize" value="{{ old('regional_prize', $tournament->regional_prize) }}" min="0">
                        @error('regional_prize')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="national_prize" class="form-label">National Prize (KES)</label>
                        <input type="number" step="0.01" class="form-control @error('national_prize') is-invalid @enderror" 
                               id="national_prize" name="national_prize" value="{{ old('national_prize', $tournament->national_prize) }}" min="0">
                        @error('national_prize')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="4">{{ old('description', $tournament->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.tournaments') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update Tournament
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
