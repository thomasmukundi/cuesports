<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .content {
            padding: 40px;
        }

        .config-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #6366f1;
        }

        .config-section h3 {
            color: #374151;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .config-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .config-item:last-child {
            border-bottom: none;
        }

        .config-label {
            font-weight: 600;
            color: #6b7280;
        }

        .config-value {
            color: #374151;
            font-family: 'Courier New', monospace;
        }

        .test-section {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6366f1;
        }

        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #5b21b6;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .results {
            margin-top: 30px;
            padding: 20px;
            border-radius: 10px;
            display: none;
        }

        .results.success {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
        }

        .results.error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #6366f1;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .result-item {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }

        .result-item.success {
            background: #d1fae5;
            border-left-color: #10b981;
        }

        .result-item.error {
            background: #fee2e2;
            border-left-color: #ef4444;
        }

        @media (max-width: 600px) {
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Test Center</h1>
            <p>Test email functionality for {{ config('app.name') }}</p>
        </div>

        <div class="content">
            <!-- Configuration Display -->
            <div class="config-section">
                <h3>📋 Current Mail Configuration</h3>
                <div class="config-grid">
                    <div class="config-item">
                        <span class="config-label">Mailer:</span>
                        <span class="config-value">{{ $config['mailer'] }}</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Host:</span>
                        <span class="config-value">{{ $config['host'] }}</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Port:</span>
                        <span class="config-value">{{ $config['port'] }}</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Username:</span>
                        <span class="config-value">{{ $config['username'] }}</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Encryption:</span>
                        <span class="config-value">{{ $config['encryption'] }}</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">From Address:</span>
                        <span class="config-value">{{ $config['from_address'] }}</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">From Name:</span>
                        <span class="config-value">{{ $config['from_name'] }}</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Environment:</span>
                        <span class="config-value">{{ $config['environment'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Email Test Form -->
            <div class="test-section">
                <h3>🧪 Email Testing</h3>
                <form id="emailTestForm">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" value="thomasngomono90@gmail.com" required>
                    </div>

                    <div class="form-group">
                        <label for="name">Recipient Name:</label>
                        <input type="text" id="name" name="name" value="Thomas Ngomono" required>
                    </div>

                    <div class="form-group">
                        <label for="type">Email Type:</label>
                        <select id="type" name="type">
                            <option value="test">Test Email</option>
                            <option value="verification">Verification Code</option>
                            <option value="password-reset">Password Reset</option>
                            <option value="welcome">Welcome Email</option>
                        </select>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Send Single Email</button>
                        <button type="button" id="testAllBtn" class="btn btn-success">Test All Email Types</button>
                        <a href="{{ url('/') }}" class="btn btn-secondary">Back to App</a>
                    </div>
                </form>

                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Sending email(s)...</p>
                </div>

                <div class="results" id="results"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('emailTestForm');
            const testAllBtn = document.getElementById('testAllBtn');
            const loading = document.getElementById('loading');
            const results = document.getElementById('results');

            // Single email test
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                sendEmail('single');
            });

            // Test all emails
            testAllBtn.addEventListener('click', function() {
                sendEmail('all');
            });

            function sendEmail(mode) {
                const formData = new FormData(form);
                const email = formData.get('email');
                const name = formData.get('name');
                const type = formData.get('type');

                if (!email || !name) {
                    alert('Please fill in email and name fields');
                    return;
                }

                showLoading();

                const url = mode === 'all' ? '/email-test/test-all' : '/email-test/send';
                const data = mode === 'all' ? {email, name} : {email, name, type};

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    showResults(data, mode);
                })
                .catch(error => {
                    hideLoading();
                    showError('Network error: ' + error.message);
                });
            }

            function showLoading() {
                loading.style.display = 'block';
                results.style.display = 'none';
            }

            function hideLoading() {
                loading.style.display = 'none';
            }

            function showResults(data, mode) {
                results.style.display = 'block';
                results.className = 'results ' + (data.success ? 'success' : 'error');

                if (mode === 'all') {
                    let html = `<h4>📊 Test Results Summary</h4>`;
                    html += `<p><strong>${data.message}</strong></p>`;
                    
                    if (data.results) {
                        html += `<div style="margin-top: 15px;">`;
                        for (const [type, result] of Object.entries(data.results)) {
                            html += `<div class="result-item ${result.success ? 'success' : 'error'}">`;
                            html += `<strong>${type.charAt(0).toUpperCase() + type.slice(1)}:</strong> ${result.message}`;
                            if (result.code) {
                                html += ` (Code: ${result.code})`;
                            }
                            html += `</div>`;
                        }
                        html += `</div>`;
                    }
                } else {
                    let html = `<h4>📧 Email Sent</h4>`;
                    html += `<p><strong>${data.message}</strong></p>`;
                    if (data.code) {
                        html += `<p><strong>Verification Code:</strong> ${data.code}</p>`;
                    }
                    html += `<p><strong>Sent to:</strong> ${data.email}</p>`;
                }

                results.innerHTML = html;
            }

            function showError(message) {
                results.style.display = 'block';
                results.className = 'results error';
                results.innerHTML = `<h4>❌ Error</h4><p>${message}</p>`;
            }
        });
    </script>
</body>
</html>
