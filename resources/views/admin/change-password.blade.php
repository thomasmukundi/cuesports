@extends('admin.layout')

@section('title', 'Change Password')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lock"></i> Change Admin Password
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            
                            <!-- Step 1: Verify Current Password -->
                            <div id="step1" class="password-step">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Step 1:</strong> Verify your current password to proceed.
                                </div>
                                
                                <form id="verifyPasswordForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password *</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Send Verification Code
                                    </button>
                                </form>
                            </div>

                            <!-- Step 2: Enter Verification Code and New Password -->
                            <div id="step2" class="password-step" style="display: none;">
                                <div class="alert alert-warning">
                                    <i class="fas fa-envelope"></i>
                                    <strong>Step 2:</strong> Check your email at <strong>mukundithomas8@gmail.com</strong> for the verification code.
                                </div>
                                
                                <form id="changePasswordForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="verification_code" class="form-label">Verification Code *</label>
                                        <input type="text" class="form-control" id="verification_code" name="verification_code" 
                                               maxlength="6" placeholder="Enter 6-digit code" required>
                                        <small class="form-text text-muted">Check your email for the 6-digit verification code</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password *</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" 
                                               minlength="8" required>
                                        <small class="form-text text-muted">Minimum 8 characters</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password_confirmation" class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" id="new_password_confirmation" 
                                               name="new_password_confirmation" minlength="8" required>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> Change Password
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="goBackToStep1()">
                                            <i class="fas fa-arrow-left"></i> Back
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Success Message -->
                            <div id="successMessage" class="password-step" style="display: none;">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>Success!</strong> Your password has been changed successfully.
                                </div>
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Processing...</p>
            </div>
        </div>
    </div>
</div>

<script>
// Step 1: Send verification code
document.getElementById('verifyPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    
    loadingModal.show();
    
    try {
        const response = await fetch('{{ route("admin.password.send-code") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        loadingModal.hide();
        
        if (result.success) {
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            showAlert('success', result.message);
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        loadingModal.hide();
        showAlert('danger', 'Network error. Please try again.');
    }
});

// Step 2: Change password
document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    
    // Check if passwords match
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('new_password_confirmation').value;
    
    if (newPassword !== confirmPassword) {
        showAlert('danger', 'Passwords do not match.');
        return;
    }
    
    loadingModal.show();
    
    try {
        const response = await fetch('{{ route("admin.password.change") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        loadingModal.hide();
        
        if (result.success) {
            document.getElementById('step2').style.display = 'none';
            document.getElementById('successMessage').style.display = 'block';
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        loadingModal.hide();
        showAlert('danger', 'Network error. Please try again.');
    }
});

function goBackToStep1() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    document.getElementById('verifyPasswordForm').reset();
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert alert at the top of the active step
    const activeStep = document.querySelector('.password-step:not([style*="display: none"])');
    activeStep.insertBefore(alertDiv, activeStep.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
@endsection
