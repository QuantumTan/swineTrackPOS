<x-guest-layout>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <div class="login-brand-icon mx-auto mb-3">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h1 class="login-title mb-1">SwineTrack POS</h1>
                        <p class="login-subtitle mb-0">Modern Point of Sale System</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="d-grid gap-3">
                        @csrf

                        <div>
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control form-control-lg @error('email') is-invalid @enderror"
                                placeholder="your@email.com"
                                required
                                autofocus
                                autocomplete="username"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="password" class="form-label fw-semibold mb-0">Password</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="login-link">Forgot password?</a>
                                @endif
                            </div>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control form-control-lg @error('password') is-invalid @enderror"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                            <label class="form-check-label text-secondary" for="remember">
                                Remember me
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100">
                            Sign In
                        </button>
                    </form>

                    <div class="login-footer text-center mt-4">
                        © 2026 SwineTrack POS. All rights reserved.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
