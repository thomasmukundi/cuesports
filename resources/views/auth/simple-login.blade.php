<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name', 'CueSports') }}</title>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Nunito', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            font-weight: 400;
            line-height: 1.6;
            color: #212529;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .min-h-screen {
            min-height: 100vh;
        }

        .flex {
            display: flex;
        }

        .flex-col {
            flex-direction: column;
        }

        .items-center {
            align-items: center;
        }

        .justify-center {
            justify-content: center;
        }

        .pt-6 {
            padding-top: 1.5rem;
        }

        .sm\:pt-0 {
            padding-top: 0;
        }

        .bg-gray-100 {
            background-color: #f7fafc;
        }

        .w-full {
            width: 100%;
        }

        .sm\:max-w-md {
            max-width: 28rem;
        }

        .mt-6 {
            margin-top: 1.5rem;
        }

        .px-6 {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .py-4 {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .bg-white {
            background-color: #ffffff;
        }

        .shadow-md {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .overflow-hidden {
            overflow: hidden;
        }

        .sm\:rounded-lg {
            border-radius: 0.5rem;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .font-medium {
            font-weight: 500;
        }

        .text-green-600 {
            color: #38a169;
        }

        .block {
            display: block;
        }

        .text-gray-700 {
            color: #4a5568;
        }

        .mt-1 {
            margin-top: 0.25rem;
        }

        .form-input {
            appearance: none;
            background-color: #ffffff;
            border-color: #d2d6dc;
            border-width: 1px;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #374151;
            width: 100%;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-checkbox {
            appearance: none;
            background-color: #ffffff;
            border-color: #d2d6dc;
            border-width: 1px;
            border-radius: 0.25rem;
            color: #6366f1;
            display: inline-block;
            flex-shrink: 0;
            height: 1rem;
            width: 1rem;
            margin-right: 0.5rem;
            cursor: pointer;
        }

        .form-checkbox:checked {
            background-color: #6366f1;
            border-color: #6366f1;
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='m13.854 3.646-7.5 7.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6 10.293l7.146-7.147a.5.5 0 0 1 .708.708z'/%3e%3c/svg%3e");
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border: 1px solid transparent;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.25;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            width: 100%;
        }

        .btn-primary {
            color: #ffffff;
            background-color: #6366f1;
            border-color: #6366f1;
        }

        .btn-primary:hover {
            background-color: #5b21b6;
            border-color: #5b21b6;
        }

        .btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.5);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .text-red-600 {
            color: #e53e3e;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .flex-items-center {
            display: flex;
            align-items: center;
        }

        .ml-2 {
            margin-left: 0.5rem;
        }

        .text-indigo-600 {
            color: #6366f1;
        }

        .hover\:text-indigo-900:hover {
            color: #312e81;
        }

        .underline {
            text-decoration: underline;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 2rem;
            text-align: center;
        }

        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.375rem;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        @media (min-width: 640px) {
            .sm\:pt-0 {
                padding-top: 0;
            }
            .sm\:max-w-md {
                max-width: 28rem;
            }
            .sm\:rounded-lg {
                border-radius: 0.5rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h1>CueSports Login</h1>
        
        @if (isset($status) && $status)
            <div style="background-color: #d4edda; color: #155724; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; border: 1px solid #c3e6cb;">
                {{ $status }}
            </div>
        @endif
        
        @if ($errors->any())
            <div class="error">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn">Log in</button>
        </form>
    </div>
</body>
</html>
