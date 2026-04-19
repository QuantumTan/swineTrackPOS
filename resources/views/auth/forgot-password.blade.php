<x-guest-layout>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <div class="login-brand-icon mx-auto mb-3">
                            <i class="bi bi-envelope-paper-heart"></i>
                        </div>
                        <h1 class="login-title mb-2">Forgot Password?</h1>
                        <p class="login-subtitle mb-0">
                            Enter the email linked to your account and we&apos;ll send you a password reset link.
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success rounded-4">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="d-grid gap-3">
                        @csrf

                        <div>
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="you@example.com"
                                class="form-control form-control-lg @error('email') is-invalid @enderror"
                                required
                                autofocus
                                autocomplete="username"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100">
                            Send Reset Link
                        </button>

                        <div class="text-center pt-1">
                            <a href="{{ route('login') }}" class="login-link">
                                <i class="bi bi-arrow-left me-1"></i>
                                Back to Login
                            </a>
                        </div>
                    </form>

                    <div class="login-footer text-center mt-4">
                        Need help accessing your account? Contact your administrator.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
