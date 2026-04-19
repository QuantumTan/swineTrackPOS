<x-guest-layout>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <div class="login-brand-icon mx-auto mb-3">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h1 class="login-title mb-2">Create Account</h1>
                        <p class="login-subtitle mb-0">
                            Set up your SwineTrack POS account to get started.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('register') }}" class="d-grid gap-3">
                        @csrf

                        <div>
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control form-control-lg @error('email') is-invalid @enderror"
                                placeholder="you@example.com"
                                required
                                autofocus
                                autocomplete="username"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control form-control-lg @error('password') is-invalid @enderror"
                                placeholder="Create a secure password"
                                required
                                autocomplete="new-password"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror"
                                placeholder="Repeat your password"
                                required
                                autocomplete="new-password"
                            >
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100">
                            Create Account
                        </button>

                        <div class="text-center pt-1">
                            <a href="{{ route('login') }}" class="login-link">
                                Already registered? Sign in
                            </a>
                        </div>
                    </form>

                    <div class="login-footer text-center mt-4">
                        Account access is limited to authorized staff.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
