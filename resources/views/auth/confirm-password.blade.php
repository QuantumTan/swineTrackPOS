<x-guest-layout>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <div class="login-brand-icon mx-auto mb-3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h1 class="login-title mb-2">Confirm Password</h1>
                        <p class="login-subtitle mb-0">
                            This is a secure area. Enter your password again before continuing.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('password.confirm') }}" class="d-grid gap-3">
                        @csrf

                        <div>
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control form-control-lg @error('password') is-invalid @enderror"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100">
                            Confirm Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
