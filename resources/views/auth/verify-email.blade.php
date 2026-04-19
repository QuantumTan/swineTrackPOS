<x-guest-layout>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <div class="login-brand-icon mx-auto mb-3">
                            <i class="bi bi-envelope-check"></i>
                        </div>
                        <h1 class="login-title mb-2">Verify Your Email</h1>
                        <p class="login-subtitle mb-0">
                            Before getting started, verify your email address using the link we sent. If you did not receive it, we can send another one.
                        </p>
                    </div>

                    @if (session('status') == 'verification-link-sent')
                        <div class="alert alert-success rounded-4">
                            A new verification link has been sent to the email address you provided during registration.
                        </div>
                    @endif

                    <div class="d-grid gap-3">
                        <form method="POST" action="{{ route('verification.send') }}" class="d-grid">
                            @csrf

                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Resend Verification Email
                            </button>
                        </form>

                        <form method="POST" action="{{ route('logout') }}" class="text-center">
                            @csrf

                            <button type="submit" class="btn btn-link login-link text-decoration-none">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
